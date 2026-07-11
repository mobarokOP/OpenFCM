<?php

namespace App\Http\Controllers\Sdk;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'fcm_token' => ['required', 'string'],
            'external_id' => ['nullable', 'string', 'max:190'],
            'platform' => ['nullable', 'in:android'],
            'app_version' => ['nullable', 'string', 'max:40'],
            'os_version' => ['nullable', 'string', 'max:40'],
            'language' => ['nullable', 'string', 'max:8'],
            'country' => ['nullable', 'string', 'max:4'],
            'timezone' => ['nullable', 'string', 'max:64'],
            'notification_permission' => ['nullable', 'boolean'],
        ]);

        $user = null;
        if (! empty($data['external_id'])) {
            $user = User::firstOrCreate(
                ['application_id' => $app->id, 'external_id' => $data['external_id']],
                ['last_seen_at' => now()],
            );
        }

        // Upsert by (app, token) so re-installs / re-registrations are idempotent.
        $device = Device::updateOrCreate(
            ['application_id' => $app->id, 'fcm_token' => $data['fcm_token']],
            [
                'user_id' => $user?->id,
                'external_id' => $data['external_id'] ?? null,
                'platform' => $data['platform'] ?? 'android',
                'app_version' => $data['app_version'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'language' => $data['language'] ?? null,
                'country' => $data['country'] ?? null,
                'timezone' => $data['timezone'] ?? null,
                'notification_permission' => $data['notification_permission'] ?? true,
                'active' => true,
                'last_active_at' => now(),
            ],
        );

        return $this->item(['device_id' => $device->id], 201);
    }

    public function updateToken(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'fcm_token' => ['required', 'string'],
        ]);

        $device = $app->devices()->findOrFail($data['device_id']);
        $device->update(['fcm_token' => $data['fcm_token'], 'last_active_at' => now(), 'active' => true]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, string $deviceId): JsonResponse
    {
        $app = $this->currentApp($request);
        $app->devices()->where('id', $deviceId)->update(['active' => false]);

        return response()->json(null, 204);
    }
}
