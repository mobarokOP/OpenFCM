// Top-level build file. Plugins are declared here (apply false) so that the
// version catalog resolves them once for all sub-modules.
plugins {
    alias(libs.plugins.android.application) apply false
    alias(libs.plugins.android.library) apply false
    alias(libs.plugins.kotlin.android) apply false
    alias(libs.plugins.kotlin.serialization) apply false
    alias(libs.plugins.kotlin.compose) apply false
    alias(libs.plugins.google.services) apply false
    // The library module uses Gradle's built-in `maven-publish` (JitPack-friendly);
    // no third-party publish plugin required.
}
