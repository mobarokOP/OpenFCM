package com.openpush.sdk.internal

import com.openpush.sdk.api.ApiResult
import com.openpush.sdk.api.UserLoginRequest
import com.openpush.sdk.api.UserLogoutRequest
import com.openpush.sdk.storage.OpenPushStore

/**
 * External-id association: `POST /v1/users/login` and `POST /v1/users/logout`.
 */
internal class UserManager(
    private val store: OpenPushStore,
    private val deviceManager: DeviceManager,
) {

    suspend fun login(externalId: String) {
        require(externalId.isNotBlank()) { "externalId must not be blank" }
        store.setExternalId(externalId)

        val appId = store.appId ?: return
        val deviceId = store.deviceId ?: run {
            // Ensure the device exists first; register() carries the external id.
            deviceManager.registerOrRefresh()
            store.deviceId ?: return
        }

        val request = UserLoginRequest(appId = appId, deviceId = deviceId, externalId = externalId)
        when (val result = OpenPushCore.requireApi().userLogin(request)) {
            is ApiResult.Success -> {
                store.setUserId(result.value.userId)
                Logger.i("User logged in: $externalId")
            }
            is ApiResult.Failure -> {
                Logger.w("User login failed: ${result.message}")
                if (result.retryable) {
                    OpenPushCore.scheduler.enqueue(
                        path = "v1/users/login",
                        method = "POST",
                        body = Dispatcher.json.encodeToString(UserLoginRequest.serializer(), request),
                        uniqueKey = "openpush-login",
                    )
                }
            }
        }
    }

    suspend fun logout() {
        val appId = store.appId ?: return
        val deviceId = store.deviceId
        store.clearUser()

        if (deviceId == null) return
        val request = UserLogoutRequest(appId = appId, deviceId = deviceId)
        Dispatcher.guarded(
            body = request,
            serializer = UserLogoutRequest.serializer(),
            path = "v1/users/logout",
            uniqueKey = "openpush-logout",
            call = { OpenPushCore.requireApi().userLogout(it) },
        ).onSuccess { Logger.i("User logged out.") }
    }
}
