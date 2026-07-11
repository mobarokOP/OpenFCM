<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $apps = Application::where('account_id', $request->user()->account_id)
            ->withCount(['devices', 'users'])
            ->latest()
            ->get()
            ->map(fn ($a) => $this->present($a));

        return $this->item($apps);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'package_name' => ['nullable', 'string', 'max:190'],
            'fcm_project_id' => ['nullable', 'string', 'max:190'],
            'fcm_service_account' => ['nullable'],
            'rate_limit' => ['nullable', 'integer', 'min:1'],
        ]);

        $serviceAccount = $data['fcm_service_account'] ?? null;
        if (is_array($serviceAccount)) {
            $serviceAccount = json_encode($serviceAccount);
        }

        $app = Application::create([
            'account_id' => $request->user()->account_id,
            'name' => $data['name'],
            'package_name' => $data['package_name'] ?? null,
            'fcm_project_id' => $data['fcm_project_id'] ?? null,
            'fcm_service_account' => $serviceAccount,
            'rate_limit' => $data['rate_limit'] ?? 1000,
        ]);

        AuditLog::record('application.created', $request->user(), ['name' => $app->name], 'application', $app->id);

        return $this->item($this->present($app), 201);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $app = $this->appForAdmin($request, $id)->loadCount(['devices', 'users']);

        return $this->item($this->present($app));
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $app = $this->appForAdmin($request, $id);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:120'],
            'package_name' => ['nullable', 'string', 'max:190'],
            'fcm_project_id' => ['nullable', 'string', 'max:190'],
            'fcm_service_account' => ['nullable'],
            'status' => ['sometimes', 'in:active,paused'],
            'rate_limit' => ['sometimes', 'integer', 'min:1'],
        ]);

        if (isset($data['fcm_service_account']) && is_array($data['fcm_service_account'])) {
            $data['fcm_service_account'] = json_encode($data['fcm_service_account']);
        }

        $app->update($data);
        AuditLog::record('application.updated', $request->user(), [], 'application', $app->id);

        return $this->item($this->present($app->loadCount(['devices', 'users'])));
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $app = $this->appForAdmin($request, $id);
        $app->delete();
        AuditLog::record('application.deleted', $request->user(), [], 'application', $id);

        return response()->json(null, 204);
    }

    private function present(Application $app): array
    {
        $sent30d = $app->notifications()
            ->where('created_at', '>=', now()->subDays(30))
            ->sum('sent_count');

        return [
            'id' => $app->id,
            'name' => $app->name,
            'package_name' => $app->package_name,
            'fcm_project_id' => $app->fcm_project_id,
            'has_service_account' => (bool) $app->fcm_service_account,
            'status' => $app->status,
            'rate_limit' => $app->rate_limit,
            'created_at' => $app->created_at,
            'stats' => [
                'devices' => $app->devices_count ?? $app->devices()->count(),
                'users' => $app->users_count ?? $app->users()->count(),
                'sent_30d' => (int) $sent30d,
            ],
        ];
    }
}
