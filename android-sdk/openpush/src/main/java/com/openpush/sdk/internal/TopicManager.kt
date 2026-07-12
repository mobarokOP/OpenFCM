package com.openpush.sdk.internal

import com.openpush.sdk.api.TopicRequest
import com.openpush.sdk.storage.OpenPushStore

/**
 * Topic subscription: `POST /v1/topics/subscribe` and `/unsubscribe`.
 */
internal class TopicManager(
    private val store: OpenPushStore,
) {

    suspend fun subscribe(topic: String) = change(topic, subscribe = true)

    suspend fun unsubscribe(topic: String) = change(topic, subscribe = false)

    private suspend fun change(topic: String, subscribe: Boolean) {
        require(topic.isNotBlank()) { "topic must not be blank" }
        val appId = store.appId
        val deviceId = store.deviceId
        if (appId == null || deviceId == null) {
            Logger.w("Topic change ignored: device not registered yet.")
            return
        }
        val request = TopicRequest(appId = appId, deviceId = deviceId, topic = topic)
        val path = if (subscribe) "v1/topics/subscribe" else "v1/topics/unsubscribe"
        Dispatcher.guarded(
            body = request,
            serializer = TopicRequest.serializer(),
            path = path,
            method = "POST",
            call = {
                if (subscribe) OpenPushCore.requireApi().subscribeTopic(it)
                else OpenPushCore.requireApi().unsubscribeTopic(it)
            },
        )
    }
}
