<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $query = $app->devices()->with('user')->latest('last_active_at');

        if ($search = $request->query('search')) {
            $query->where(fn ($q) => $q
                ->where('external_id', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%")
                ->orWhere('os_version', 'like', "%{$search}%"));
        }

        if ($request->boolean('active_only')) {
            $query->where('active', true);
        }

        $paginator = $query->paginate($request->integer('per_page', 20))
            ->through(fn (Device $d) => [
                'id' => $d->id,
                'external_id' => $d->external_id,
                'fcm_token' => $d->masked_token,
                'platform' => $d->platform,
                'app_version' => $d->app_version,
                'os_version' => $d->os_version,
                'language' => $d->language,
                'country' => $d->country,
                'timezone' => $d->timezone,
                'notification_permission' => $d->notification_permission,
                'active' => $d->active,
                'last_active_at' => $d->last_active_at,
                'created_at' => $d->created_at,
            ]);

        return $this->collection($paginator);
    }
}
