package com.openpush.sdk.internal

import com.openpush.sdk.api.TagsDeleteRequest
import com.openpush.sdk.api.TagsUpsertRequest
import com.openpush.sdk.storage.OpenPushStore

/**
 * Device tag CRUD: `POST /v1/tags` (upsert) and `DELETE /v1/tags`.
 */
internal class TagManager(
    private val store: OpenPushStore,
) {

    suspend fun addTags(tags: Map<String, String>) {
        if (tags.isEmpty()) return
        val (appId, deviceId) = identity() ?: return
        val request = TagsUpsertRequest(appId = appId, deviceId = deviceId, tags = tags)
        Dispatcher.guarded(
            body = request,
            serializer = TagsUpsertRequest.serializer(),
            path = "v1/tags",
            method = "POST",
            call = { OpenPushCore.requireApi().upsertTags(it) },
        )
    }

    suspend fun removeTags(keys: List<String>) {
        if (keys.isEmpty()) return
        val (appId, deviceId) = identity() ?: return
        val request = TagsDeleteRequest(appId = appId, deviceId = deviceId, keys = keys)
        Dispatcher.guarded(
            body = request,
            serializer = TagsDeleteRequest.serializer(),
            path = "v1/tags",
            method = "DELETE",
            call = { OpenPushCore.requireApi().deleteTags(it) },
        )
    }

    private fun identity(): Pair<String, String>? {
        val appId = store.appId
        val deviceId = store.deviceId
        if (appId == null || deviceId == null) {
            Logger.w("Tag change ignored: device not registered yet.")
            return null
        }
        return appId to deviceId
    }
}
