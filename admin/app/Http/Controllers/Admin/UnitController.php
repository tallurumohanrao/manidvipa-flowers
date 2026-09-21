<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUnitRequest;
use App\Traits\RedirectTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class UnitController extends Controller
{
    use RedirectTrait;

    private string $module = 'units';

    public function __construct()
    {
        View::share('module', $this->module);
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('units_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $perPage = $request->input('per_page') ?: config('PER_PAGE');
        $query = DB::table('measurement_units');
        if ($request->filled('q')) {
            $term = '%'.$request->input('q').'%';
            $query->where(function ($search) use ($term) {
                $search->where('code', 'like', $term)
                    ->orWhere('singular_name', 'like', $term)
                    ->orWhere('plural_name', 'like', $term)
                    ->orWhere('type', 'like', $term);
            });
        }
        $data = $query->orderBy('priority')->orderBy('singular_name')
            ->paginate($perPage)->withQueryString();

        return view('admin.units.index', compact('data'));
    }

    public function create()
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        return view('admin.units.create', ['row' => null, 'baseUnits' => $this->baseUnits(), 'isUsed' => false]);
    }

    public function store(StoreUnitRequest $request)
    {
        abort_if(Gate::denies('units_create'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $input = $request->validated();
        $input = $this->normalizeConversion($input);
        $input['allows_decimal'] = $request->boolean('allows_decimal');
        $input['created_at'] = now();
        $input['updated_at'] = now();
        $id = DB::table('measurement_units')->insertGetId($input);
        $this->clearStorefrontCache();

        return $this->redirectAfterSave($request->FormButton, $id);
    }

    public function edit($id)
    {
        abort_if(Gate::denies('units_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $row = DB::table('measurement_units')->where('id', $id)->first();
        abort_if(! $row, Response::HTTP_NOT_FOUND);

        return view('admin.units.edit', [
            'row' => $row,
            'baseUnits' => $this->baseUnits(),
            'isUsed' => $this->isUsed((int) $id),
        ]);
    }

    public function update(StoreUnitRequest $request, $id)
    {
        abort_if(Gate::denies('units_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $row = DB::table('measurement_units')->where('id', $id)->first();
        abort_if(! $row, Response::HTTP_NOT_FOUND);
        $input = $request->validated();
        $input['code'] = $row->code;
        if ($this->isUsed((int) $id)) {
            $input['type'] = $row->type;
            $input['base_code'] = $row->base_code;
            $input['conversion_factor'] = $row->conversion_factor;
            $input['allows_decimal'] = $row->allows_decimal;
        } else {
            $input = $this->normalizeConversion($input);
            $input['allows_decimal'] = $request->boolean('allows_decimal');
        }
        $input['updated_at'] = now();
        DB::table('measurement_units')->where('id', $id)->update($input);
        $this->clearStorefrontCache();

        return $this->redirectAfterSave($request->FormButton, $id);
    }

    public function updateStatus(Request $request, $id)
    {
        abort_if(Gate::denies('units_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $validated = $request->validate(['status' => ['required', 'boolean']]);
        $updated = DB::table('measurement_units')->where('id', $id)->update([
            'status' => (int) $validated['status'],
            'updated_at' => now(),
        ]);
        $this->clearStorefrontCache();

        return response()->json([
            'status' => $updated ? 'success' : 'info',
            'message' => $updated ? 'Unit status updated successfully.' : 'No unit status change was needed.',
        ]);
    }

    public function destroy($id)
    {
        abort_if(Gate::denies('units_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        if ($this->isUsed((int) $id)) {
            return response()->json(['success' => false, 'message' => 'This unit is used by products. Disable it instead of deleting it.'], 422);
        }
        $deleted = DB::table('measurement_units')->where('id', $id)->delete();
        $this->clearStorefrontCache();

        return response()->json(['success' => (bool) $deleted, 'message' => $deleted ? 'Unit deleted successfully.' : 'Unit not found.'], $deleted ? 200 : 404);
    }

    public function massDestroy(Request $request)
    {
        abort_if(Gate::denies('units_delete'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');
        $ids = collect(explode(',', (string) $request->ids))->map(fn ($id) => (int) $id)->filter()->unique();
        $usedIds = DB::table('product_inventory_pools')->whereIn('unit_id', $ids)->pluck('unit_id')
            ->merge(DB::table('product_weights')->whereIn('unit_id', $ids)->pluck('unit_id'))->unique();
        $dependentBaseCodes = DB::table('measurement_units')->whereIn('base_code', function ($query) use ($ids) {
            $query->select('code')->from('measurement_units')->whereIn('id', $ids);
        })->whereNotIn('id', $ids)->pluck('base_code');
        $usedIds = $usedIds->merge(DB::table('measurement_units')->whereIn('code', $dependentBaseCodes)->pluck('id'))->unique();
        $deletable = $ids->diff($usedIds);
        $deleted = $deletable->isEmpty() ? 0 : DB::table('measurement_units')->whereIn('id', $deletable)->delete();
        $this->clearStorefrontCache();

        return response()->json([
            'success' => $deleted > 0,
            'message' => $usedIds->isNotEmpty()
                ? $deleted.' unit(s) deleted. '.$usedIds->count().' used unit(s) were kept; disable them instead.'
                : $deleted.' unit(s) deleted successfully.',
        ], $deleted > 0 || $usedIds->isNotEmpty() ? 200 : 422);
    }

    private function baseUnits(): array
    {
        return DB::table('measurement_units')->whereColumn('base_code', 'code')
            ->orderBy('priority')->pluck('singular_name', 'code')->toArray();
    }

    private function isUsed(int $id): bool
    {
        return DB::table('product_inventory_pools')->where('unit_id', $id)->exists()
            || DB::table('product_weights')->where('unit_id', $id)->exists()
            || DB::table('measurement_units as dependent')->join('measurement_units as base', 'base.code', '=', 'dependent.base_code')
                ->where('base.id', $id)->whereColumn('dependent.id', '<>', 'base.id')->exists();
    }

    private function normalizeConversion(array $input): array
    {
        if (empty($input['base_code'])) {
            $input['base_code'] = $input['code'];
            $input['conversion_factor'] = 1;

            return $input;
        }

        $base = DB::table('measurement_units')->where('code', $input['base_code'])->first();
        if (! $base || $base->base_code !== $base->code) {
            throw ValidationException::withMessages(['base_code' => 'Choose an independent base unit.']);
        }
        if ($base->type !== $input['type']) {
            throw ValidationException::withMessages(['base_code' => 'The base unit must have the same unit type.']);
        }

        return $input;
    }

    private function clearStorefrontCache(): void
    {
        Cache::forget('api_featured_products');
        Cache::forget('api_home_sections');
        Cache::forget('home');
    }
}
