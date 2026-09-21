<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;
use View;

class HomepageSectionController extends Controller
{
    private string $module = 'home_sections';

    public function __construct()
    {
        View::share('module', $this->module);
    }

    public function index(Request $request)
    {
        $this->authorizeAbility('view');

        $data = DB::table('homepage_sections as sections')
            ->leftJoin('categories', 'categories.id', '=', 'sections.category_id')
            ->select('sections.*', 'categories.title as category_title', 'categories.slug as category_slug')
            ->when($request->filled('q'), function ($query) use ($request) {
                $term = '%'.$request->input('q').'%';
                $query->where(function ($search) use ($term) {
                    $search->where('sections.title', 'like', $term)
                        ->orWhere('sections.section_key', 'like', $term)
                        ->orWhere('categories.title', 'like', $term);
                });
            })
            ->orderBy('sections.priority')
            ->orderByDesc('sections.id')
            ->paginate($request->input('per_page') ?: config('PER_PAGE'))
            ->withQueryString();

        return view('admin.home_sections.index', compact('data'));
    }

    public function create()
    {
        $this->authorizeAbility('create');

        return view('admin.home_sections.create', [
            'row' => [],
            'categories' => $this->categories(),
            'products' => $this->products(),
            'isSystem' => false,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeAbility('create');
        $validated = $this->validateSection($request);

        $id = DB::table('homepage_sections')->insertGetId(array_merge($validated, [
            'created_at' => now(),
            'updated_at' => now(),
        ]));
        $this->clearCache();

        return $this->afterSaveRedirect($request, $id, 'Homepage section created successfully.');
    }

    public function edit($homepage_section)
    {
        $this->authorizeAbility('edit');
        $row = DB::table('homepage_sections')->where('id', $homepage_section)->first();
        abort_if(! $row, Response::HTTP_NOT_FOUND);

        return view('admin.home_sections.edit', [
            'row' => $row,
            'categories' => $this->categories(),
            'products' => $this->products(),
            'isSystem' => (bool) $row->is_system,
        ]);
    }

    public function update(Request $request, $homepage_section)
    {
        $this->authorizeAbility('edit');
        $exists = DB::table('homepage_sections')->where('id', $homepage_section)->exists();
        abort_if(! $exists, Response::HTTP_NOT_FOUND);

        $row = DB::table('homepage_sections')->where('id', $homepage_section)->first();
        if ($row->is_system) {
            $validated = $request->validate([
                'priority' => ['required', 'integer', 'min:0', 'max:9999'],
                'status' => ['required', 'boolean'],
            ]);
            $validated['priority'] = (int) $validated['priority'];
            $validated['status'] = (int) $validated['status'];
        } else {
            $validated = $this->validateSection($request);
        }

        $updated = DB::table('homepage_sections')
            ->where('id', $homepage_section)
            ->update(array_merge($validated, ['updated_at' => now()]));
        $this->clearCache();

        return $this->afterSaveRedirect($request, $homepage_section, 'Homepage section updated successfully.');
    }

    public function destroy($homepage_section)
    {
        $this->authorizeAbility('delete');
        $deleted = DB::table('homepage_sections')->where('id', $homepage_section)->delete();
        $this->clearCache();

        return response()->json([
            'success' => (bool) $deleted,
            'message' => $deleted ? 'Homepage section deleted successfully.' : 'Section was not found.',
        ], $deleted ? 200 : 404);
    }

    public function massDestroy(Request $request)
    {
        $this->authorizeAbility('delete');
        $ids = collect(explode(',', (string) $request->input('ids')))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->values();
        $deleted = $ids->isEmpty() ? 0 : DB::table('homepage_sections')->whereIn('id', $ids)->delete();
        $this->clearCache();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $deleted > 0 ? 'Homepage sections deleted successfully.' : 'Select at least one section.',
        ], $deleted > 0 ? 200 : 422);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->authorizeAbility('edit');
        $status = (int) $request->input('status');
        abort_unless(in_array($status, [0, 1], true), Response::HTTP_UNPROCESSABLE_ENTITY);
        $exists = DB::table('homepage_sections')->where('id', $id)->exists();
        abort_if(! $exists, Response::HTTP_NOT_FOUND);
        $updated = DB::table('homepage_sections')->where('id', $id)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);
        $this->clearCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Section status updated successfully.',
        ], 200);
    }

    public function reorder(Request $request)
    {
        $this->authorizeAbility('edit');
        $order = $request->validate([
            'order' => ['required', 'array', 'min:1'],
            'order.*' => ['integer', 'distinct', 'exists:homepage_sections,id'],
        ])['order'];

        DB::transaction(function () use ($order) {
            foreach ($order as $index => $id) {
                DB::table('homepage_sections')->where('id', $id)->update([
                    'priority' => ($index + 1) * 10,
                    'updated_at' => now(),
                ]);
            }
        });
        $this->clearCache();

        return response()->json([
            'status' => 'success',
            'message' => 'Homepage section order saved successfully.',
        ]);
    }

    private function authorizeAbility(string $ability): void
    {
        abort_if(Gate::denies($this->module.'_'.$ability), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
    }

    private function categories()
    {
        return DB::table('categories')
            ->where('status', 1)
            ->orderByRaw('COALESCE(parent_id, 0)')
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    private function validateSection(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'subtitle' => ['nullable', 'string', 'max:300'],
            'section_type' => ['required', 'in:category_products,featured_products'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id', 'required_if:section_type,category_products'],
            'product_ids' => ['nullable', 'array', 'required_if:section_type,featured_products'],
            'product_ids.*' => ['integer', 'exists:products,id'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'max_items' => ['required', 'integer', 'min:1', 'max:12'],
            'priority' => ['required', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', 'boolean'],
        ]);

        $validated['status'] = (int) $validated['status'];
        $validated['max_items'] = (int) $validated['max_items'];
        $validated['priority'] = (int) $validated['priority'];
        $validated['category_id'] = $validated['category_id'] ?? null;
        if ($validated['section_type'] === 'featured_products') {
            $validated['category_id'] = null;
        }
        if ($validated['section_type'] === 'category_products') {
            $validated['product_ids'] = [];
        }
        $validated['product_ids'] = json_encode(array_values($validated['product_ids'] ?? []));

        return $validated;
    }

    private function products()
    {
        return DB::table('products')
            ->where('status', 1)
            ->orderBy('title')
            ->get(['id', 'title', 'sku']);
    }

    private function clearCache(): void
    {
        Cache::forget('api_home_sections');
        Cache::forget('api_home_sections_v1');
    }

    private function afterSaveRedirect(Request $request, int $id, string $message)
    {
        $redirect = match ($request->input('FormButton')) {
            'SAVEEDIT' => redirect()->route('admin.home_sections.edit', $id),
            'SAVENEW' => redirect()->route('admin.home_sections.create'),
            default => redirect()->route('admin.home_sections.index'),
        };

        return $redirect->with('success', $message);
    }
}
