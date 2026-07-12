<?php

namespace App\Http\Controllers\Sdk;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EventController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'notification_id' => ['nullable', 'uuid'],
            'type' => ['required', 'in:received,opened,clicked'],
            'occurred_at' => ['nullable', 'date'],
        ]);

        $device = $app->devices()->find($data['device_id']);

        AnalyticsEvent::create([
            'application_id' => $app->id,
            'notification_id' => $data['notification_id'] ?? null,
            'device_id' => $data['device_id'],
            'type' => $data['type'],
            'country' => $device?->country,
            'os_version' => $device?->os_version,
            'occurred_at' => isset($data['occurred_at']) ? Carbon::parse($data['occurred_at']) : now(),
        ]);

        // Roll up opens onto the notification counter. Only 'opened' counts —
        // a tap fires both 'clicked' and 'opened', so counting both would
        // double the CTR.
        if ($data['type'] === 'opened' && ! empty($data['notification_id'])) {
            Notification::where('id', $data['notification_id'])
                ->where('application_id', $app->id)
                ->increment('opened_count');
        }

        return response()->json(null, 204);
    }
}
