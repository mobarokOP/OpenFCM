<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiKeyController extends Controller
{
    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $keys = $app->apiKeys()->latest()->get()->map(fn ($k) => [
            'id' => $k->id,
            'name' => $k->name,
            'prefix' => $k->prefix,
            'last_used_at' => $k->last_used_at,
            'revoked_at' => $k->revoked_at,
            'created_at' => $k->created_at,
        ]);

        return $this->item($keys);
    }

    public function store(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);

        [$key, $plaintext] = ApiKey::generate($app, $data['name']);
        AuditLog::record('apikey.created', $request->user(), ['name' => $key->name], 'api_key', $key->id);

        return $this->item([
            'id' => $key->id,
            'name' => $key->name,
            'prefix' => $key->prefix,
            'key' => $plaintext, // shown only once
            'created_at' => $key->created_at,
        ], 201);
    }

    public function destroy(Request $request, string $appId, string $keyId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $key = $app->apiKeys()->findOrFail($keyId);
        $key->update(['revoked_at' => now()]);
        AuditLog::record('apikey.revoked', $request->user(), [], 'api_key', $key->id);

        return response()->json(null, 204);
    }
}
