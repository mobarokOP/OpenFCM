plugins {
    alias(libs.plugins.android.library)
    alias(libs.plugins.kotlin.android)
    alias(libs.plugins.kotlin.serialization)
    `maven-publish`
}

// Published coordinates for JitPack: com.github.mobarokOP.OneFCM:openfcm:<tag>
// Version is injected by JitPack via -Pversion / VERSION env; falls back for local builds.
group = "com.github.mobarokOP.OneFCM"
version = (findProperty("version") as? String)
    ?.takeUnless { it == "unspecified" }
    ?: System.getenv("VERSION")
    ?: "1.1.0"

android {
    namespace = "com.openfcm.sdk"
    compileSdk = 34

    defaultConfig {
        minSdk = 24

        // The OneFCM cloud endpoint, baked in so `OpenFCM.init(context, appId)`
        // works with no further config. Self-hosted servers override it at
        // runtime via `baseUrl` in OpenFCM.init(...).
        buildConfigField("String", "OPENFCM_DEFAULT_BASE_URL", "\"https://api.onefcm.com\"")
        buildConfigField("String", "OPENFCM_SDK_VERSION", "\"1.1.0\"")

        consumerProguardFiles("consumer-rules.pro")
        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
    }

    buildTypes {
        release {
            isMinifyEnabled = false
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro"
            )
        }
    }

    buildFeatures {
        buildConfig = true
    }

    // Expose a single publishable "release" component with sources.
    publishing {
        singleVariant("release") {
            withSourcesJar()
        }
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }

    kotlinOptions {
        jvmTarget = "17"
        freeCompilerArgs += "-opt-in=kotlinx.serialization.ExperimentalSerializationApi"
    }

    testOptions {
        unitTests.isReturnDefaultValues = true
    }
}

dependencies {
    // Firebase Cloud Messaging (delivery transport)
    api(platform(libs.firebase.bom))
    api(libs.firebase.messaging)

    // Coroutines
    implementation(libs.kotlinx.coroutines.core)
    implementation(libs.kotlinx.coroutines.android)
    implementation(libs.kotlinx.coroutines.play.services)

    // Networking + JSON
    implementation(libs.okhttp)
    implementation(libs.okhttp.logging)
    implementation(libs.kotlinx.serialization.json)

    // AndroidX
    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.lifecycle.process)
    implementation(libs.androidx.work.runtime.ktx)
    implementation(libs.androidx.datastore.preferences)

    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
}

publishing {
    publications {
        register<MavenPublication>("release") {
            groupId = "com.github.mobarokOP.OneFCM"
            artifactId = "openfcm"
            version = project.version.toString()

            afterEvaluate {
                from(components["release"])
            }

            pom {
                name.set("OpenFCM Android SDK")
                description.set("Android client SDK for the OpenFCM notification platform (FCM).")
                url.set("https://github.com/mobarokOP/OneFCM")
                licenses {
                    license {
                        name.set("MIT License")
                        url.set("https://opensource.org/licenses/MIT")
                    }
                }
            }
        }
    }
}
