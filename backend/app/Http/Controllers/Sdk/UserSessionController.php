<?php

namespace App\Http\Controllers\Sdk;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserSessionController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate([
            'device_id' => ['required', 'uuid'],
            'external_id' => ['required', 'string', 'max:190'],
        ]);

        $user = User::updateOrCreate(
            ['application_id' => $app->id, 'external_id' => $data['external_id']],
            ['last_seen_at' => now()],
        );

        $app->devices()->where('id', $data['device_id'])->update([
            'user_id' => $user->id,
            'external_id' => $data['external_id'],
            'last_active_at' => now(),
        ]);

        return $this->item(['user_id' => $user->id]);
    }

    public function logout(Request $request): JsonResponse
    {
        $app = $this->currentApp($request);

        $data = $request->validate(['device_id' => ['required', 'uuid']]);

        $app->devices()->where('id', $data['device_id'])->update([
            'user_id' => null,
            'external_id' => null,
        ]);

        return response()->json(null, 204);
    }
}
