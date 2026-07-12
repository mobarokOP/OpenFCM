# Consumer ProGuard/R8 rules — automatically applied to any app that depends
# on the OpenPush AAR. Keeps the public API and serialization models intact.

# Keep the public facade and its callbacks (accessed reflectively by hosts / Java).
-keep public class com.openpush.sdk.OpenPush { public *; }
-keep public class com.openpush.sdk.OpenPushConfig { public *; }
-keep public interface com.openpush.sdk.** { *; }

# Keep the Firebase messaging service (instantiated by the framework by name).
-keep class com.openpush.sdk.messaging.OpenPushFirebaseMessagingService { *; }

# kotlinx.serialization — keep generated serializers for our @Serializable models.
-keepattributes *Annotation*, InnerClasses
-dontnote kotlinx.serialization.**
-keepclassmembers class com.openpush.sdk.api.** {
    *** Companion;
}
-keepclasseswithmembers class com.openpush.sdk.api.** {
    kotlinx.serialization.KSerializer serializer(...);
}
-keep,includedescriptorclasses class com.openpush.sdk.api.**$$serializer { *; }
