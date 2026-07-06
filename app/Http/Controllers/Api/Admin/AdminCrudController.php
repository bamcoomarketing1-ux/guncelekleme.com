<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class AdminCrudController extends Controller
{
    abstract protected function modelClass(): string;

    abstract protected function listKey(): string;

    abstract protected function singularKey(): string;

    protected function listQuery()
    {
        return ($this->modelClass())::query()->orderBy('sort_order')->orderByDesc('id');
    }

    protected function transform(Model $m): array
    {
        return $m->toArray();
    }

    public function index(): JsonResponse
    {
        $items = $this->listQuery()->get()->map(fn ($m) => $this->transform($m));
        $key = $this->listKey();
        if ($key === 'data') {
            return response()->json(['status' => 'success', 'data' => $items]);
        }
        return response()->json([$key => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $model = ($this->modelClass())::create($this->validated($request));
        return response()->json([
            $this->singularKey() => $this->transform($model),
            'message' => 'Kayıt eklendi.',
        ], 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $model = ($this->modelClass())::findOrFail($id);
        $model->update($this->validated($request, $model));
        return response()->json(['status' => 'success', 'data' => $this->transform($model->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        ($this->modelClass())::findOrFail($id)->delete();
        return response()->json(['status' => 'success', 'message' => 'Silindi.']);
    }

    public function reorder(Request $request): JsonResponse
    {
        foreach ($request->input('order', []) as $i => $id) {
            ($this->modelClass())::where('id', $id)->update(['sort_order' => $i + 1]);
        }
        return response()->json(['status' => 'success', 'message' => 'Sıralama güncellendi.']);
    }

    protected function validated(Request $request, ?Model $model = null): array
    {
        return $request->except(['_token', '_method']);
    }
}
