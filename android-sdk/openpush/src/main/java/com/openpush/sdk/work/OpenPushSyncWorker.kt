package com.openpush.sdk.work

import android.content.Context
import androidx.work.CoroutineWorker
import androidx.work.WorkerParameters
import com.openpush.sdk.internal.Logger
import com.openpush.sdk.internal.OpenPushCore

/**
 * Generic offline-retry worker. When a network call fails transiently, the
 * originating manager persists the request's path/method/body and enqueues this
 * worker with a network constraint + exponential backoff. On execution it simply
 * replays the request.
 *
 * Because it stores the fully-serialized body, one worker handles every endpoint
 * (register, token, tags, topics, events, user login/logout) uniformly.
 */
internal class OpenPushSyncWorker(
    appContext: Context,
    params: WorkerParameters,
) : CoroutineWorker(appContext, params) {

    override suspend fun doWork(): Result {
        val path = inputData.getString(KEY_PATH) ?: return Result.failure()
        val method = inputData.getString(KEY_METHOD) ?: "POST"
        val body = inputData.getString(KEY_BODY)

        // Bootstrap the SDK from persisted state (survives process death).
        if (!OpenPushCore.ensureInitialized(applicationContext)) {
            Logger.w("SyncWorker: no app id available yet, retrying later.")
            return Result.retry()
        }

        val api = OpenPushCore.apiOrNull() ?: return Result.retry()

        val result = api.sendRaw(path, method, body)
        return result.fold(
            onSuccess = {
                Logger.d("SyncWorker replayed $method $path")
                Result.success()
            },
            onRetryable = {
                Logger.w("SyncWorker will retry $method $path: ${it.message}")
                if (runAttemptCount >= MAX_ATTEMPTS) Result.failure() else Result.retry()
            },
            onPermanent = {
                Logger.e("SyncWorker dropping $method $path (permanent): ${it.message}")
                Result.failure()
            },
        )
    }

    private inline fun <T> com.openpush.sdk.api.ApiResult<T>.fold(
        onSuccess: (T) -> Result,
        onRetryable: (com.openpush.sdk.api.ApiResult.Failure) -> Result,
        onPermanent: (com.openpush.sdk.api.ApiResult.Failure) -> Result,
    ): Result = when (this) {
        is com.openpush.sdk.api.ApiResult.Success -> onSuccess(value)
        is com.openpush.sdk.api.ApiResult.Failure ->
            if (retryable) onRetryable(this) else onPermanent(this)
    }

    companion object {
        const val KEY_PATH = "op_path"
        const val KEY_METHOD = "op_method"
        const val KEY_BODY = "op_body"
        private const val MAX_ATTEMPTS = 8
    }
}
