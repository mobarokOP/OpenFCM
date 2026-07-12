package com.openpush.sdk.work

import android.content.Context
import androidx.work.BackoffPolicy
import androidx.work.Constraints
import androidx.work.Data
import androidx.work.ExistingWorkPolicy
import androidx.work.NetworkType
import androidx.work.OneTimeWorkRequestBuilder
import androidx.work.WorkManager
import com.openpush.sdk.internal.Logger
import java.util.concurrent.TimeUnit

/**
 * Enqueues [OpenPushSyncWorker] retries with a network constraint and
 * exponential backoff. Managers call [enqueue] whenever a live API call fails
 * with a retryable error so the request is not lost while offline.
 */
internal class OpenPushWorkScheduler(context: Context) {

    private val workManager = WorkManager.getInstance(context.applicationContext)

    /**
     * @param uniqueKey collapses duplicate pending work (e.g. repeated token
     *   updates coalesce to the latest). Pass null for events that must all fire.
     */
    fun enqueue(path: String, method: String, body: String?, uniqueKey: String? = null) {
        val data = Data.Builder()
            .putString(OpenPushSyncWorker.KEY_PATH, path)
            .putString(OpenPushSyncWorker.KEY_METHOD, method)
            .putString(OpenPushSyncWorker.KEY_BODY, body)
            .build()

        val constraints = Constraints.Builder()
            .setRequiredNetworkType(NetworkType.CONNECTED)
            .build()

        val request = OneTimeWorkRequestBuilder<OpenPushSyncWorker>()
            .setInputData(data)
            .setConstraints(constraints)
            .setBackoffCriteria(BackoffPolicy.EXPONENTIAL, 30, TimeUnit.SECONDS)
            .addTag(TAG)
            .build()

        if (uniqueKey != null) {
            workManager.enqueueUniqueWork(uniqueKey, ExistingWorkPolicy.REPLACE, request)
        } else {
            workManager.enqueue(request)
        }
        Logger.d("Queued offline retry for $method $path (key=$uniqueKey)")
    }

    fun cancelAll() {
        workManager.cancelAllWorkByTag(TAG)
    }

    companion object {
        private const val TAG = "openpush-sync"
        const val KEY_TOKEN = "openpush-token"
        const val KEY_REGISTER = "openpush-register"
    }
}
