package com.openpush.sdk

/**
 * Optional configuration for [OpenPush.init]. Use the DSL builder for the common
 * cases; every field has a sensible default.
 *
 * ```kotlin
 * OpenPush.init(context, appId) {
 *     baseUrl = "https://push.example.com"
 *     enableDebugLogging = true
 *     defaultChannelId = "marketing"
 * }
 * ```
 */
class OpenPushConfig private constructor(
    val baseUrl: String?,
    val enableDebugLogging: Boolean,
    val defaultChannelId: String?,
    val defaultChannelName: String?,
    val smallIconResId: Int?,
    val accentColor: Int?,
    val autoRegister: Boolean,
    val trackSessions: Boolean,
) {

    class Builder {
        /** Overrides the backend base URL (default: BuildConfig.OPENPUSH_DEFAULT_BASE_URL). */
        var baseUrl: String? = null

        /** Enables verbose logging and OkHttp body logging. Keep false in release. */
        var enableDebugLogging: Boolean = false

        /** Notification channel used when a push omits `channel_id`. */
        var defaultChannelId: String? = null
        var defaultChannelName: String? = null

        /** Small (status bar) icon. Falls back to the app launcher icon if unset. */
        var smallIconResId: Int? = null

        /** Notification accent color (ARGB int). */
        var accentColor: Int? = null

        /** Register the device automatically during init. Default true. */
        var autoRegister: Boolean = true

        /** Track foreground session time and report it on background. Default true. */
        var trackSessions: Boolean = true

        fun build(): OpenPushConfig = OpenPushConfig(
            baseUrl = baseUrl,
            enableDebugLogging = enableDebugLogging,
            defaultChannelId = defaultChannelId,
            defaultChannelName = defaultChannelName,
            smallIconResId = smallIconResId,
            accentColor = accentColor,
            autoRegister = autoRegister,
            trackSessions = trackSessions,
        )
    }

    companion object {
        fun default(): OpenPushConfig = Builder().build()
    }
}
