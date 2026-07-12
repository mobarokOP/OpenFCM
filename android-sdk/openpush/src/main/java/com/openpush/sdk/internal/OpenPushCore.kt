package com.openpush.sdk.internal

import android.content.Context
import com.openpush.sdk.BuildConfig
import com.openpush.sdk.OpenPushConfig
import com.openpush.sdk.api.OpenPushApi
import com.openpush.sdk.messaging.NotificationOpenHandler
import com.openpush.sdk.messaging.NotificationPayload
import com.openpush.sdk.storage.OpenPushStore
import com.openpush.sdk.work.OpenPushWorkScheduler
import kotlinx.coroutines.CoroutineScope
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.SupervisorJob
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock

/**
 * Process-wide singleton holding the SDK's shared collaborators. Both the public
 * [com.openpush.sdk.OpenPush] facade and background components (FCM service,
 * WorkManager workers) resolve their dependencies from here.
 *
 * The core is resilient to process death: [ensureInitialized] rehydrates state
 * from [OpenPushStore] so a worker that runs before the app calls `init()` can
 * still function using the last-known app id / base URL.
 */
internal object OpenPushCore {

    @Volatile
    private var appContext: Context? = null

    @Volatile
    private var api: OpenPushApi? = null

    @Volatile
    var config: OpenPushConfig = OpenPushConfig.default()
        private set

    @Volatile
    var openHandler: NotificationOpenHandler? = null

    /** Buffers a notification opened before a handler was registered. */
    @Volatile
    var pendingOpen: NotificationPayload? = null

    val scope: CoroutineScope = CoroutineScope(SupervisorJob() + Dispatchers.Default)

    private val initMutex = Mutex()

    lateinit var store: OpenPushStore
        private set

    lateinit var scheduler: OpenPushWorkScheduler
        private set

    val isInitialized: Boolean get() = appContext != null && this::store.isInitialized

    fun context(): Context =
        appContext ?: error("OpenPush is not initialized. Call OpenPush.init(context, appId) first.")

    fun apiOrNull(): OpenPushApi? = api

    fun requireApi(): OpenPushApi =
        api ?: error("OpenPush has no app id configured yet.")

    /** Full initialization triggered by [com.openpush.sdk.OpenPush.init]. */
    suspend fun initialize(context: Context, appId: String, cfg: OpenPushConfig) = initMutex.withLock {
        val ctx = context.applicationContext
        appContext = ctx
        config = cfg
        Logger.debug = cfg.enableDebugLogging

        if (!this::store.isInitialized) store = OpenPushStore(ctx)
        if (!this::scheduler.isInitialized) scheduler = OpenPushWorkScheduler(ctx)

        store.hydrate()
        store.setAppId(appId)
        val baseUrl = cfg.baseUrl ?: store.baseUrl ?: BuildConfig.OPENPUSH_DEFAULT_BASE_URL
        store.setBaseUrl(baseUrl)
        rebuildApi(appId, baseUrl)
    }

    /**
     * Lazily bootstraps the core from persisted state. Used by the FCM service
     * and workers, which may run after process death before `init()` is called.
     * Returns true if an API client is available afterwards.
     */
    suspend fun ensureInitialized(context: Context): Boolean = initMutex.withLock {
        val ctx = context.applicationContext
        if (appContext == null) appContext = ctx
        if (!this::store.isInitialized) store = OpenPushStore(ctx)
        if (!this::scheduler.isInitialized) scheduler = OpenPushWorkScheduler(ctx)
        if (api == null) {
            store.hydrate()
            val appId = store.appId
            val baseUrl = store.baseUrl ?: BuildConfig.OPENPUSH_DEFAULT_BASE_URL
            if (appId != null) rebuildApi(appId, baseUrl)
        }
        api != null
    }

    private fun rebuildApi(appId: String, baseUrl: String) {
        api = OpenPushApi(
            baseUrl = baseUrl,
            appId = appId,
            userAgent = "OpenPush-Android/${BuildConfig.OPENPUSH_SDK_VERSION}",
        )
    }
}
