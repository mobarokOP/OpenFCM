package com.example.openfcmtest

import android.Manifest
import android.content.ClipData
import android.content.ClipboardManager
import android.content.Context
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.widget.Toast
import androidx.activity.result.contract.ActivityResultContracts
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import com.example.openfcmtest.databinding.ActivityMainBinding
import com.openfcm.sdk.OpenFCM

class MainActivity : AppCompatActivity() {

    private lateinit var binding: ActivityMainBinding

    private val permissionLauncher =
        registerForActivityResult(ActivityResultContracts.RequestPermission()) { granted ->
            toast(if (granted) "Notifications enabled ✅" else "Permission denied ❌")
            refresh()
        }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        binding = ActivityMainBinding.inflate(layoutInflater)
        setContentView(binding.root)

        // Show a toast when the user taps a delivered notification.
        OpenFCM.setNotificationOpenHandler { payload ->
            runOnUiThread {
                toast("Opened: ${payload.title ?: "(no title)"}")
            }
        }

        binding.btnPermission.setOnClickListener { askPermission() }
        binding.btnLogin.setOnClickListener {
            OpenFCM.login("test_user")
            toast("Logged in as test_user")
            binding.root.postDelayed(::refresh, 800)
        }
        binding.btnTag.setOnClickListener {
            OpenFCM.addTag("premium", "true")
            toast("Tag premium=true added")
        }
        binding.btnTopic.setOnClickListener {
            OpenFCM.subscribeTopic("all")
            toast("Subscribed to topic 'all'")
        }
        binding.btnCopy.setOnClickListener { copyDeviceId() }
        binding.btnRefresh.setOnClickListener { refresh() }

        // Ask for notification permission on first launch (Android 13+).
        askPermission()
        // Device registration is async; give it a moment then show the id.
        binding.root.postDelayed(::refresh, 1500)
    }

    private fun askPermission() {
        if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
            val granted = ContextCompat.checkSelfPermission(
                this, Manifest.permission.POST_NOTIFICATIONS,
            ) == PackageManager.PERMISSION_GRANTED
            if (!granted) {
                permissionLauncher.launch(Manifest.permission.POST_NOTIFICATIONS)
                return
            }
        }
        toast("Notifications already enabled ✅")
    }

    private fun refresh() {
        val deviceId = OpenFCM.deviceId
        binding.txtDeviceId.text = deviceId ?: "registering… tap Refresh"
        binding.txtExternalId.text = OpenFCM.externalId ?: "—"
        binding.txtPermission.text =
            if (OpenFCM.areNotificationsEnabled()) "Enabled ✅" else "Disabled ❌"
    }

    private fun copyDeviceId() {
        val id = OpenFCM.deviceId
        if (id == null) {
            toast("Device id not ready yet")
            return
        }
        val clip = getSystemService(Context.CLIPBOARD_SERVICE) as ClipboardManager
        clip.setPrimaryClip(ClipData.newPlainText("device_id", id))
        toast("Device ID copied")
    }

    override fun onResume() {
        super.onResume()
        refresh()
    }

    private fun toast(msg: String) = Toast.makeText(this, msg, Toast.LENGTH_SHORT).show()
}
