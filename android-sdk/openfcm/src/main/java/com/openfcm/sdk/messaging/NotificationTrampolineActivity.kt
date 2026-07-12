package com.openfcm.sdk.messaging

import android.app.Activity
import android.content.Intent
import android.net.Uri
import android.os.Bundle
import com.openfcm.sdk.internal.EventTracker
import com.openfcm.sdk.internal.Logger
import com.openfcm.sdk.internal.OpenFCMCore
import kotlinx.coroutines.launch

/**
 * Invisible trampoline launched when the user taps (or dismisses) a notification.
 * Responsibilities:
 *  1. Fire the `opened`/`clicked` tracking event.
 *  2. Invoke the host's [NotificationOpenHandler].
 *  3. Route the deep link (or open the app's launcher activity).
 *
 * Using a trampoline (rather than a direct PendingIntent to the host) guarantees
 * click tracking runs exactly once regardless of the app's current state.
 */
class NotificationTrampolineActivity : Activity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        val extras = intent?.extras
        if (extras == null) {
            finish()
            return
        }
        val payload = NotificationPayload.fromBundle(extras)

        when (intent.action) {
            ACTION_DISMISS -> {
                Logger.d("Notification dismissed: ${payload.notificationId}")
                finish()
                return
            }
            else -> handleOpen(payload)
        }
        finish()
    }

    private fun handleOpen(payload: NotificationPayload) {
        // Bootstrap + track. Runs on the SDK scope so it survives this Activity.
        OpenFCMCore.scope.launch {
            OpenFCMCore.ensureInitialized(applicationContext)
            val tracker = EventTracker(OpenFCMCore.store)
            // "opened" = app brought to foreground; "clicked" = user interaction.
            tracker.track(payload.notificationId, EventTracker.Type.CLICKED)
            tracker.track(payload.notificationId, EventTracker.Type.OPENED)
        }

        // Dispatch to the host handler (or buffer if not yet set).
        val handler = OpenFCMCore.openHandler
        if (handler != null) {
            runCatching { handler.onOpened(payload) }
                .onFailure { Logger.e("openHandler threw", it) }
        } else {
            OpenFCMCore.pendingOpen = payload
        }

        launchTarget(payload)
    }

    private fun launchTarget(payload: NotificationPayload) {
        val deepLink = payload.deepLink

        if (!deepLink.isNullOrBlank()) {
            val uri = runCatching { Uri.parse(deepLink) }.getOrNull()
            if (uri != null) {
                // 1. Prefer an in-app activity that handles this link (app links /
                //    custom schemes declared by the host app).
                val inApp = Intent(Intent.ACTION_VIEW, uri).apply {
                    setPackage(packageName)
                    addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                    putExtras(payload.toBundle())
                }
                if (inApp.resolveActivity(packageManager) != null &&
                    runCatching { startActivity(inApp) }.isSuccess
                ) {
                    return
                }

                // 2. Otherwise hand the link to the system (browser, maps, …) —
                //    e.g. a plain https:// URL with no in-app intent-filter.
                val external = Intent(Intent.ACTION_VIEW, uri).apply {
                    addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
                }
                if (runCatching { startActivity(external) }.isSuccess) {
                    return
                }
                Logger.w("No activity could handle deep link '$deepLink'; opening launcher.")
            }
        }

        // 3. Fall back to the app's launcher activity.
        packageManager.getLaunchIntentForPackage(packageName)?.let { fallback ->
            fallback.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
            fallback.putExtras(payload.toBundle())
            runCatching { startActivity(fallback) }
        }
    }

    companion object {
        const val ACTION_OPEN = "com.openfcm.sdk.action.OPEN"
        const val ACTION_DISMISS = "com.openfcm.sdk.action.DISMISS"
    }
}
