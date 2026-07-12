package com.openpush.sdk

import android.app.Activity
import android.content.Context
import com.openpush.sdk.internal.DeviceManager
import com.openpush.sdk.internal.Logger
import com.openpush.sdk.internal.OpenPushCore
import com.openpush.sdk.internal.PermissionHelper
import com.openpush.sdk.internal.SessionManager
import com.openpush.sdk.internal.TagManager
import com.openpush.sdk.internal.TopicManager
import com.openpush.sdk.internal.UserManager
import com.openpush.sdk.messaging.NotificationOpenHandler
import com.openpush.sdk.messaging.NotificationPayload
import kotlinx.coroutines.CompletableDeferred
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import kotlinx.coroutines.Dispatchers

/**
 * OpenPush Android SDK — public facade.
 *
 * ```kotlin
 * OpenPush.init(context, "YOUR_APP_ID")
 * OpenPush.login("user123")
 * OpenPush.addTag("premium", "true")
 * OpenPush.subscribeTopic("writer_25")
 * OpenPush.setNotificationOpenHandler { payload -> /* route deep link */ }
 * ```
 *
 * All methods are safe to call from the main thread and before initialization
 * completes: operations are dispatched onto an internal coroutine scope and
 * suspend until the SDK is ready. Nothing blocks the UI thread.
 */
object OpenPush {

    @Volatile
    private var ready: CompletableDeferred<Unit> = CompletableDeferred()

    // ---- Lifecycle -----------------------------------------------------------

    /**
     * Initializes the SDK: persists [appId], establishes the API client,
     * registers the device, syncs the FCM token, and starts session tracking.
     * Idempotent — calling again re-applies config and refreshes registration.
     *
     * @param appId the public application id from the OpenPush dashboard.
     */
    @JvmStatic
    @JvmOverloads
    fun init(context: Context, appId: String, config: OpenPushConfig = OpenPushConfig.default()) {
        require(appId.isNotBlank()) { "appId must not be blank" }
        val ctx = context.applicationContext
        // Reset the readiness gate for this (re-)initialization.
        val signal = CompletableDeferred<Unit>()
        ready = signal

        OpenPushCore.scope.launch {
            OpenPushCore.initialize(ctx, appId, config)
            signal.complete(Unit)
            Logger.i("OpenPush initialized (appId=$appId).")

            if (config.autoRegister) {
                DeviceManager(OpenPushCore.store).registerOrRefresh()
            }
            if (config.trackSessions) {
                withContext(Dispatchers.Main) { startSessionTracking() }
            }
            // Flush any notification tap that arrived before init finished.
            drainPendingOpen()
        }
    }

    /** Builder-style init: `OpenPush.init(context, appId) { enableDebugLogging = true }`. */
    @JvmSynthetic
    fun init(context: Context, appId: String, block: OpenPushConfig.Builder.() -> Unit) {
        val cfg = OpenPushConfig.Builder().apply(block).build()
        init(context, appId, cfg)
    }

    // ---- Identity ------------------------------------------------------------

    /** Associates the current device with an external user id. */
    @JvmStatic
    fun login(externalId: String) = dispatch {
        UserManager(OpenPushCore.store, DeviceManager(OpenPushCore.store)).login(externalId)
    }

    /** Clears the external user association from this device. */
    @JvmStatic
    fun logout() = dispatch {
        UserManager(OpenPushCore.store, DeviceManager(OpenPushCore.store)).logout()
    }

    // ---- Tags ----------------------------------------------------------------

    @JvmStatic
    fun addTag(key: String, value: String) = dispatch {
        TagManager(OpenPushCore.store).addTags(mapOf(key to value))
    }

    @JvmStatic
    fun addTags(tags: Map<String, String>) = dispatch {
        TagManager(OpenPushCore.store).addTags(tags)
    }

    @JvmStatic
    fun removeTag(key: String) = dispatch {
        TagManager(OpenPushCore.store).removeTags(listOf(key))
    }

    @JvmStatic
    fun removeTags(keys: List<String>) = dispatch {
        TagManager(OpenPushCore.store).removeTags(keys)
    }

    // ---- Topics --------------------------------------------------------------

    @JvmStatic
    fun subscribeTopic(topic: String) = dispatch {
        TopicManager(OpenPushCore.store).subscribe(topic)
    }

    @JvmStatic
    fun unsubscribeTopic(topic: String) = dispatch {
        TopicManager(OpenPushCore.store).unsubscribe(topic)
    }

    // ---- Notifications -------------------------------------------------------

    /**
     * Registers a callback fired when the user opens an OpenPush notification.
     * If a notification was opened before this was set, the buffered payload is
     * delivered immediately.
     */
    @JvmStatic
    fun setNotificationOpenHandler(handler: NotificationOpenHandler) {
        OpenPushCore.openHandler = handler
        OpenPushCore.pendingOpen?.let {
            OpenPushCore.pendingOpen = null
            runCatching { handler.onOpened(it) }
        }
    }

    /** Kotlin-friendly lambda overload. */
    @JvmSynthetic
    fun setNotificationOpenHandler(handler: (NotificationPayload) -> Unit) =
        setNotificationOpenHandler(NotificationOpenHandler { handler(it) })

    // ---- Permission helpers --------------------------------------------------

    /** True when the app can currently display notifications. */
    @JvmStatic
    fun areNotificationsEnabled(): Boolean =
        OpenPushCore.isInitialized && PermissionHelper.areNotificationsEnabled(OpenPushCore.context())

    /** Requests the Android 13+ POST_NOTIFICATIONS runtime permission. */
    @JvmStatic
    fun requestNotificationPermission(activity: Activity) =
        PermissionHelper.requestNotificationPermission(activity)

    // ---- Introspection -------------------------------------------------------

    /** The backend-assigned device id, or null before registration completes. */
    @JvmStatic
    val deviceId: String?
        get() = if (OpenPushCore.isInitialized) OpenPushCore.store.deviceId else null

    /** The currently logged-in external id, if any. */
    @JvmStatic
    val externalId: String?
        get() = if (OpenPushCore.isInitialized) OpenPushCore.store.externalId else null

    // ---- Internals -----------------------------------------------------------

    private var sessionManager: SessionManager? = null

    private fun startSessionTracking() {
        if (sessionManager != null) return
        sessionManager = SessionManager(
            store = OpenPushCore.store,
            onForeground = { DeviceManager(OpenPushCore.store).registerOrRefresh() },
        ).also { it.start() }
    }

    private fun drainPendingOpen() {
        val handler = OpenPushCore.openHandler ?: return
        OpenPushCore.pendingOpen?.let {
            OpenPushCore.pendingOpen = null
            runCatching { handler.onOpened(it) }
        }
    }

    /** Runs [block] after initialization completes, on the SDK scope. */
    private fun dispatch(block: suspend () -> Unit) {
        val gate = ready
        OpenPushCore.scope.launch {
            gate.await()
            runCatching { block() }.onFailure { Logger.e("OpenPush operation failed", it) }
        }
    }
}
