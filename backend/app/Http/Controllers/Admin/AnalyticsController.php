<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AnalyticsEvent;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function overview(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $since = now()->subDays(30);

        $sent = (int) $app->notifications()->where('created_at', '>=', $since)->sum('sent_count');
        $delivered = (int) $app->notifications()->where('created_at', '>=', $since)->sum('delivered_count');
        $opened = (int) $app->notifications()->where('created_at', '>=', $since)->sum('opened_count');

        return $this->item([
            'devices' => $app->devices()->where('active', true)->count(),
            'users' => $app->users()->count(),
            'sent_30d' => $sent,
            'delivered_30d' => $delivered,
            'ctr_30d' => $delivered > 0 ? round($opened / $delivered * 100, 2) : 0,
            'timeseries' => $this->timeseries($app, $since, now()),
            'recent' => $app->notifications()->latest()->limit(5)->get(['id', 'title', 'status', 'sent_count', 'created_at']),
        ]);
    }

    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $from = $request->query('from') ? Carbon::parse($request->query('from')) : now()->subDays(30);
        $to = $request->query('to') ? Carbon::parse($request->query('to')) : now();

        $totals = fn (string $type) => AnalyticsEvent::where('application_id', $app->id)
            ->whereBetween('occurred_at', [$from, $to])
            ->where('type', $type)->count();

        $delivered = $totals('delivered');
        $opened = $totals('opened');

        return $this->item([
            'sent' => (int) $app->notifications()->whereBetween('created_at', [$from, $to])->sum('sent_count'),
            'delivered' => $delivered,
            'failed' => $totals('failed'),
            'opened' => $opened,
            'ctr' => $delivered > 0 ? round($opened / $delivered * 100, 2) : 0,
            'timeseries' => $this->timeseries($app, $from, $to),
            'by_country' => $this->breakdown($app, 'country', $from, $to),
            'by_os' => $this->breakdown($app, 'os_version', $from, $to),
        ]);
    }

    private function timeseries(Application $app, Carbon $from, Carbon $to): array
    {
        $rows = AnalyticsEvent::where('application_id', $app->id)
            ->whereBetween('occurred_at', [$from, $to])
            ->selectRaw('DATE(occurred_at) as date, type, COUNT(*) as c')
            ->groupBy('date', 'type')
            ->get();

        $byDate = [];
        foreach ($rows as $r) {
            $byDate[$r->date] ??= ['date' => $r->date, 'delivered' => 0, 'opened' => 0, 'failed' => 0];
            if (isset($byDate[$r->date][$r->type])) {
                $byDate[$r->date][$r->type] = (int) $r->c;
            }
        }

        ksort($byDate);

        return array_values($byDate);
    }

    private function breakdown(Application $app, string $field, Carbon $from, Carbon $to): array
    {
        return AnalyticsEvent::where('application_id', $app->id)
            ->where('type', 'delivered')
            ->whereBetween('occurred_at', [$from, $to])
            ->whereNotNull($field)
            ->select($field, DB::raw('COUNT(*) as count'))
            ->groupBy($field)
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(fn ($r) => ['label' => $r->$field, 'count' => (int) $r->count])
            ->all();
    }
}
