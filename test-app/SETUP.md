# OpenFCM Test App — Setup Guide (বাংলা)

এই ছোট Android অ্যাপটা দিয়ে তুমি যাচাই করতে পারবে — OpenFCM থেকে পাঠানো notification আসলেই তোমার Android ডিভাইসে পৌঁছাচ্ছে কিনা।

> **কীভাবে কাজ করে:** অ্যাপ চালু হলে OpenFCM SDK নিজে নিজে ডিভাইসটা তোমার backend-এ register করে। তারপর তুমি dashboard/API থেকে notification পাঠালে সেটা **Firebase (FCM)** হয়ে ডিভাইসে আসে।

---

## ⚙️ যা যা লাগবে

- **Android Studio** (latest)
- একটা **Android ডিভাইস** (অথবা Google Play আছে এমন emulator) — Android **8.0+**
- একটা **Firebase project** (ফ্রি)
- OpenFCM dashboard-এ একটা **Application** ([beta.kathgolap.online](https://beta.kathgolap.online))

---

## 🔑 সবচেয়ে জরুরি কথা (একবার পড়ো)

Notification ডিভাইসে যেতে **দুইটা** Firebase জিনিস লাগে — আর দুইটাই **একই Firebase project-এর** হতে হবে:

| কী | কোথায় বসবে | কাজ |
|----|-----------|-----|
| `google-services.json` | এই অ্যাপের `app/` ফোল্ডারে | অ্যাপকে FCM-এ যুক্ত করে (client) |
| Service account JSON | OpenFCM dashboard → App Settings → FCM | server-কে তোমার হয়ে পাঠানোর অনুমতি দেয় |

> ⚠️ Dashboard-এ service account না দিলে notification **"sent" দেখাবে কিন্তু ডিভাইসে আসবে না** (simulated mode)। এটাই সবচেয়ে common ভুল।

---

## 🚀 ধাপে ধাপে

### ধাপ ১ — Firebase project + google-services.json

1. [Firebase Console](https://console.firebase.google.com) → **Add project** (বা আগের project নাও)।
2. **Add app → Android** চাপো।
3. **Package name** দাও ঠিক এইটা: `com.example.openfcmtest`
4. **Download `google-services.json`**।
5. ফাইলটা এই অ্যাপের **`test-app/app/`** ফোল্ডারে রাখো (নাম হুবহু `google-services.json`)।

### ধাপ ২ — Service account → OpenFCM dashboard

1. Firebase Console → ⚙️ **Project settings → Service accounts**।
2. **Generate new private key** → একটা JSON download হবে।
3. [OpenFCM dashboard](https://beta.kathgolap.online) → তোমার **Application → Settings**।
4. **FCM Project ID** দাও (Firebase project-এর id) এবং ঐ **service account JSON** upload/paste করো। Save।

### ধাপ ৩ — App ID বসাও

1. Dashboard → **Applications** → তোমার app → **App ID** কপি করো।
2. এই অ্যাপে `app/src/main/java/com/example/openfcmtest/TestApp.kt` খোলো।
3. এই লাইন ঠিক করো:
   ```kotlin
   const val OPENFCM_APP_ID = "PASTE_YOUR_APP_ID_HERE"   // 👈 তোমার App ID
   const val OPENFCM_BASE_URL = "https://admin.beta.kathgolap.online"
   ```

### ধাপ ৪ — Build + Install

1. Android Studio → **Open** → `test-app` ফোল্ডার সিলেক্ট করো।
2. Gradle sync শেষ হতে দাও।
3. ডিভাইস কানেক্ট করে **Run ▶️** চাপো।
4. অ্যাপ খুললে **"Enable notifications"** চাপো → permission দাও (Android 13+)।
5. কয়েক সেকেন্ড পর **Device ID** দেখাবে (না দেখালে **Refresh** চাপো)।

> ✅ যাচাই: dashboard → **Devices** পেজে নতুন ডিভাইসটা দেখা যাবে। তাহলে registration ঠিক আছে।

### ধাপ ৫ — Notification পাঠাও

**Dashboard দিয়ে:** New notification → Title + Body লেখো → Audience **All** (বা topic `all`, বা tag) → **Send**।

**অথবা API দিয়ে** (API key: App → API Keys):
```bash
curl -X POST https://admin.beta.kathgolap.online/v1/notifications \
  -H "Authorization: Bearer op_live_YOUR_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "app_id": "YOUR_APP_ID",
    "title": "Hello 👋",
    "body": "OpenFCM test notification",
    "audience": { "type": "all" }
  }'
```

### ধাপ ৬ — ডিভাইসে দেখো 🎉

- **অ্যাপ background/বন্ধ থাকলে:** notification সরাসরি status bar-এ আসবে।
- **অ্যাপ খোলা থাকলে:** SDK নিজে notification দেখাবে + tap করলে Toast আসবে।
- Dashboard → **Delivery Logs**-এ status `sent`/`delivered` দেখাবে।

---

## 🧪 অ্যাপের বাটনগুলো

| বাটন | কাজ |
|------|-----|
| Enable notifications | Android 13+ permission চায় |
| Login as test_user | ডিভাইসকে `test_user` external id দেয় (user-target টেস্টের জন্য) |
| Add tag premium=true | tag বসায় (tag-target টেস্ট) |
| Subscribe topic 'all' | topic subscribe (topic-target টেস্ট) |
| Copy Device ID | device id কপি (device-target টেস্ট) |
| Refresh | status রিফ্রেশ |

---

## 🩺 Notification আসছে না? — Checklist

1. **Dashboard-এ service account দেওয়া আছে?** না থাকলে simulated mode — ডিভাইসে আসবে না। *(সবচেয়ে common)*
2. **google-services.json আর service account একই Firebase project-এর?** আলাদা হলে কাজ করবে না।
3. **FCM Project ID** dashboard-এ ঠিক দেওয়া আছে?
4. ডিভাইসে **notification permission** দেওয়া আছে? (Settings → Apps → OpenFCM Test → Notifications)
5. **Device ID** দেখাচ্ছে? না দেখালে registration হয়নি — internet + App ID + baseUrl চেক করো।
6. Dashboard → **Devices**-এ ডিভাইস **active**? আর **Delivery Logs**-এ error আছে কিনা দেখো।
7. **Logcat** খোলো, filter `OpenFCM` — এখানে register/token/receive-এর লগ দেখা যাবে (`enableDebugLogging = true`)।
8. Emulator হলে **Google Play আছে এমন image** ব্যবহার করো (FCM লাগে)।

---

## ℹ️ টুকিটাকি

- Package name বদলাতে চাইলে `applicationId` (app/build.gradle.kts), `namespace`, ফোল্ডার, আর Firebase-এর package — সব একসাথে বদলাও।
- SDK version: `com.github.mobarokOP:OpenFCM:2.0.1` (app/build.gradle.kts)। নতুন version এলে এখানেই বদলাবে।
- SDK-র পুরো API: রুট রিপোর [README](../README.md) দেখো।
