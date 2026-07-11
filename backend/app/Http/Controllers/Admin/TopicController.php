<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $topics = $app->topics()->withCount('devices')->orderBy('name')->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'subscribers' => $t->devices_count,
                'created_at' => $t->created_at,
            ]);

        return $this->item($topics);
    }
}
