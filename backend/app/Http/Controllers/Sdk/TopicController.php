<?php

namespace App\Http\Controllers\Sdk;

use App\Http\Controllers\Controller;
use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);
        $data = $this->validated($request);

        $device = $app->devices()->findOrFail($data['device_id']);
        $topic = Topic::firstOrCreate(['application_id' => $app->id, 'name' => $data['topic']]);
        $device->topics()->syncWithoutDetaching([$topic->id]);

        return response()->json(null, 204);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);
        $data = $this->validated($request);

        $device = $app->devices()->findOrFail($data['device_id']);
        $topic = Topic::where('application_id', $app->id)->where('name', $data['topic'])->first();
        if ($topic) {
            $device->topics()->detach($topic->id);
        }

        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'device_id' => ['required', 'uuid'],
            'topic' => ['required', 'string', 'max:120'],
        ]);
    }
}
