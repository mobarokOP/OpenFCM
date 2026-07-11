<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Segment;
use App\Services\AudienceResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SegmentController extends Controller
{
    public function __construct(private readonly AudienceResolver $resolver) {}

    public function index(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);

        $segments = $app->segments()->latest()->get()
            ->map(fn (Segment $s) => $this->present($s));

        return $this->item($segments);
    }

    public function store(Request $request, string $appId): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $data = $this->validated($request);

        $segment = $app->segments()->create($data);

        return $this->item($this->present($segment), 201);
    }

    public function update(Request $request, string $appId, string $id): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $segment = $app->segments()->findOrFail($id);
        $segment->update($this->validated($request));

        return $this->item($this->present($segment));
    }

    public function destroy(Request $request, string $appId, string $id): JsonResponse
    {
        $app = $this->appForAdmin($request, $appId);
        $app->segments()->findOrFail($id)->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:dynamic,static'],
            'filters' => ['array'],
            'filters.*.field' => ['required', 'string'],
            'filters.*.op' => ['required', 'in:eq,neq,exists,contains'],
            'filters.*.value' => ['nullable'],
        ]);
    }

    private function present(Segment $s): array
    {
        return [
            'id' => $s->id,
            'name' => $s->name,
            'type' => $s->type,
            'filters' => $s->filters ?? [],
            'count' => $this->resolver->count($s->application, ['type' => 'segment', 'value' => $s->id]),
            'created_at' => $s->created_at,
        ];
    }
}
