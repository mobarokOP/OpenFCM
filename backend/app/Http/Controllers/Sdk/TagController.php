<?php

namespace App\Http\Controllers\Sdk;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'tags' => ['required', 'array'],
        ]);

        $device = $app->devices()->findOrFail($data['device_id']);

        foreach ($data['tags'] as $key => $value) {
            $tag = Tag::firstOrCreate(['application_id' => $app->id, 'key' => (string) $key]);
            $device->tags()->syncWithoutDetaching([
                $tag->id => ['value' => is_scalar($value) ? (string) $value : json_encode($value)],
            ]);
        }

        return response()->json(null, 204);
    }

    public function destroy(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'keys' => ['required', 'array'],
        ]);

        $device = $app->devices()->findOrFail($data['device_id']);
        $tagIds = Tag::where('application_id', $app->id)->whereIn('key', $data['keys'])->pluck('id');
        $device->tags()->detach($tagIds);

        return response()->json(null, 204);
    }
}
