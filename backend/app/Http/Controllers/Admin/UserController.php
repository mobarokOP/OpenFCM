<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $query = $app->users()->withCount('devices')->latest('last_seen_at');

        if ($search = $request->query('search')) {
            $query->where('external_id', 'like', "%{$search}%");
        }

        $paginator = $query->paginate($request->integer('per_page', 20))
            ->through(fn (User $u) => [
                'id' => $u->id,
                'external_id' => $u->external_id,
                'devices_count' => $u->devices_count,
                'last_seen_at' => $u->last_seen_at,
                'created_at' => $u->created_at,
            ]);

        return $this->collection($paginator);
    }
}
