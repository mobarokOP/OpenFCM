<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Device;
use App\Models\Segment;
use Illuminate\Database\Eloquent\Builder;

/**
 * Translates an audience spec ({type, value}) into a query over deliverable
 * devices (active + notification permission) for an application.
 */
class AudienceResolver
{
    private const DEVICE_FIELDS = ['country', 'language', 'os_version', 'app_version', 'timezone', 'external_id'];

    public function query(Application $app, array $audience): Builder
    {
        $base = Device::query()
            ->where('application_id', $app->id)
            ->where('active', true)
            ->where('notification_permission', true);

        $type = $audience['type'] ?? 'all';
        $value = $audience['value'] ?? null;

        return match ($type) {
            'all' => $base,
            'user_ids' => $base->whereIn('external_id', (array) $value),
            'device_ids' => $base->whereIn('id', (array) $value),
            'topic' => $base->whereHas('topics', fn ($q) => $q->where('name', $value)),
            'tags' => $this->applyFilters($base, $app, (array) $value),
            'segment' => $this->applySegment($base, $app, $value),
            default => $base->whereRaw('1 = 0'),
        };
    }

    public function count(Application $app, array $audience): int
    {
        return $this->query($app, $audience)->count();
    }

    private function applySegment(Builder $base, Application $app, ?string $segmentId): Builder
    {
        $segment = Segment::where('application_id', $app->id)->find($segmentId);

        if (! $segment) {
            return $base->whereRaw('1 = 0');
        }

        return $this->applyFilters($base, $app, $segment->filters ?? []);
    }

    /**
     * @param  array<int,array{field:string,op:string,value?:mixed}>  $filters
     */
    private function applyFilters(Builder $base, Application $app, array $filters): Builder
    {
        foreach ($filters as $filter) {
            $field = $filter['field'] ?? null;
            $op = $filter['op'] ?? 'eq';
            $val = $filter['value'] ?? null;

            if (! $field) {
                continue;
            }

            if (in_array($field, self::DEVICE_FIELDS, true)) {
                $this->applyDeviceFieldFilter($base, $field, $op, $val);
            } else {
                $this->applyTagFilter($base, $field, $op, $val);
            }
        }

        return $base;
    }

    private function applyDeviceFieldFilter(Builder $q, string $field, string $op, mixed $val): void
    {
        match ($op) {
            'neq' => $q->where($field, '!=', $val),
            'exists' => $q->whereNotNull($field),
            'contains' => $q->where($field, 'like', '%'.$val.'%'),
            default => $q->where($field, $val),
        };
    }

    private function applyTagFilter(Builder $q, string $key, string $op, mixed $val): void
    {
        $q->whereHas('tags', function ($tagQuery) use ($key, $op, $val) {
            $tagQuery->where('tags.key', $key);

            match ($op) {
                'neq' => $tagQuery->where('device_tags.value', '!=', $val),
                'exists' => $tagQuery, // key present is enough
                'contains' => $tagQuery->where('device_tags.value', 'like', '%'.$val.'%'),
                default => $tagQuery->where('device_tags.value', $val),
            };
        });
    }
}
