<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\Response;

class DailyPriceController extends Controller
{
    private string $module = 'dailyprices';

    public function __construct()
    {
        View::share('module', $this->module);
    }

    public function index(Request $request)
    {
        abort_if(Gate::denies('dailyprices_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $perPage = (int) ($request->per_page ?: 50);
        $perPage = in_array($perPage, [25, 50, 100, 200], true) ? $perPage : 50;

        $query = $this->basePriceQuery();
        $this->applyFilters($query, $request);

        $data = $query
            ->orderBy('p.title')
            ->orderByRaw($this->weightOrderSql())
            ->paginate($perPage)
            ->withQueryString();

        $categories = DB::table('categories')
            ->select('id', 'title', 'name', 'parent_id', 'priority', 'status')
            ->orderByRaw('COALESCE(parent_id, id)')
            ->orderBy('parent_id')
            ->orderBy('priority')
            ->orderBy('title')
            ->get();

        $parentCategories = $categories
            ->filter(fn ($category) => empty($category->parent_id))
            ->values();

        $weightOptions = DB::table('weights')
            ->select('name')
            ->orderBy('name')
            ->get()
            ->pluck('name')
            ->filter()
            ->values();

        $latestLogs = Schema::hasTable('price_update_logs')
            ? DB::table('price_update_logs')->orderByDesc('id')->limit(12)->get()
            : collect();

        return view('admin.dailyprices.index', compact(
            'data',
            'categories',
            'parentCategories',
            'weightOptions',
            'latestLogs',
            'perPage'
        ));
    }

    public function update(Request $request)
    {
        abort_if(Gate::denies('dailyprices_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if ($request->filled('bulk_action') && $request->bulk_action !== 'none') {
            return $this->bulkUpdate($request);
        }

        $prices = $request->input('prices', []);

        if (! is_array($prices) || empty($prices)) {
            return back()->with('fail', 'No prices submitted.');
        }

        $weightIds = array_values(array_filter(array_map('intval', array_keys($prices))));

        if (empty($weightIds)) {
            return back()->with('fail', 'No valid price rows submitted.');
        }

        $rows = $this->basePriceQuery()
            ->whereIn('pw.id', $weightIds)
            ->get()
            ->keyBy('weight_id');

        $updated = 0;
        $autoUpdated = 0;
        $syncProductIds = [];
        $kgUpdates = [];

        DB::transaction(function () use (
            $request,
            $prices,
            $rows,
            &$updated,
            &$autoUpdated,
            &$syncProductIds,
            &$kgUpdates
        ) {
            foreach ($prices as $weightId => $payload) {
                $weightId = (int) $weightId;
                $row = $rows->get($weightId);

                if (! $row) {
                    continue;
                }

                $newSellPrice = $this->moneyValue($payload['sell_price'] ?? null);
                $newListPrice = $this->moneyValue($payload['list_price'] ?? null);

                if ($newSellPrice === null) {
                    continue;
                }

                $newListPrice = $newListPrice ?? (float) $row->list_price;

                if ($newSellPrice < 0 || $newListPrice < 0) {
                    continue;
                }

                if (! $this->priceChanged($row, $newSellPrice, $newListPrice)) {
                    continue;
                }

                $this->applyPriceUpdate($row, $newSellPrice, $newListPrice, 'manual', 'Daily admin price update');
                $updated++;
                $syncProductIds[$row->product_id] = $row->product_id;

                if ($request->boolean('auto_calculate_loose') && $this->weightFactor($row->weight_name) === 1.0) {
                    $kgUpdates[$row->product_id] = [
                        'source_weight_id' => $row->weight_id,
                        'sell_price' => $newSellPrice,
                        'list_price' => $newListPrice,
                    ];
                }
            }

            foreach ($kgUpdates as $productId => $kgUpdate) {
                $autoUpdated += $this->autoCalculateLooseWeights(
                    (int) $productId,
                    (int) $kgUpdate['source_weight_id'],
                    (float) $kgUpdate['sell_price'],
                    (float) $kgUpdate['list_price']
                );
                $syncProductIds[$productId] = $productId;
            }

            foreach ($syncProductIds as $productId) {
                $this->syncProductBasePrice((int) $productId);
            }
        });

        $message = $updated.' price row'.($updated === 1 ? '' : 's').' updated.';

        if ($autoUpdated > 0) {
            $message .= ' '.$autoUpdated.' loose weight row'.($autoUpdated === 1 ? '' : 's').' auto-calculated from 1KG.';
        }

        return redirect()
            ->route('admin.dailyprices.index', $this->filterRedirectParams($request))
            ->with($updated || $autoUpdated ? 'success' : 'fail', $updated || $autoUpdated ? $message : 'No price changes found.');
    }

    public function export(Request $request)
    {
        abort_if(Gate::denies('dailyprices_view'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $query = $this->basePriceQuery();
        $this->applyFilters($query, $request);

        $rows = $query
            ->orderBy('p.title')
            ->orderByRaw($this->weightOrderSql())
            ->get();

        $filename = 'daily-price-update-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'product_weight_id',
                'product_id',
                'sku',
                'product_slug',
                'product_title',
                'categories',
                'weight_name',
                'sell_price',
                'list_price',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->weight_id,
                    $row->product_id,
                    $row->sku,
                    $row->slug,
                    $row->product_title,
                    $row->category_titles,
                    $row->weight_name,
                    number_format((float) $row->sell_price, 2, '.', ''),
                    number_format((float) $row->list_price, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function import(Request $request)
    {
        abort_if(Gate::denies('dailyprices_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $request->validate([
            'price_file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:5120'],
        ]);

        $rows = $this->parsePriceImportFile($request->file('price_file'));

        if (empty($rows)) {
            return back()->with('fail', 'No valid rows found in the uploaded file.');
        }

        $updated = 0;
        $skipped = 0;
        $autoUpdated = 0;
        $syncProductIds = [];
        $kgUpdates = [];

        DB::transaction(function () use (
            $request,
            $rows,
            &$updated,
            &$skipped,
            &$autoUpdated,
            &$syncProductIds,
            &$kgUpdates
        ) {
            foreach ($rows as $rowNumber => $importRow) {
                $row = $this->findImportPriceRow($importRow);

                if (! $row) {
                    $skipped++;
                    continue;
                }

                $newSellPrice = $this->moneyValue($importRow['sell_price'] ?? null);
                $newListPrice = $this->moneyValue($importRow['list_price'] ?? null);

                if ($newSellPrice === null || $newSellPrice < 0) {
                    $skipped++;
                    continue;
                }

                $newListPrice = $newListPrice ?? (float) $row->list_price;

                if ($newListPrice < 0 || ! $this->priceChanged($row, $newSellPrice, $newListPrice)) {
                    $skipped++;
                    continue;
                }

                $this->applyPriceUpdate($row, $newSellPrice, $newListPrice, 'import', 'Imported daily price file row #'.($rowNumber + 2));
                $updated++;
                $syncProductIds[$row->product_id] = $row->product_id;

                if ($request->boolean('auto_calculate_loose_file') && $this->weightFactor($row->weight_name) === 1.0) {
                    $kgUpdates[$row->product_id] = [
                        'source_weight_id' => $row->weight_id,
                        'sell_price' => $newSellPrice,
                        'list_price' => $newListPrice,
                    ];
                }
            }

            foreach ($kgUpdates as $productId => $kgUpdate) {
                $autoUpdated += $this->autoCalculateLooseWeights(
                    (int) $productId,
                    (int) $kgUpdate['source_weight_id'],
                    (float) $kgUpdate['sell_price'],
                    (float) $kgUpdate['list_price']
                );
                $syncProductIds[$productId] = $productId;
            }

            foreach ($syncProductIds as $productId) {
                $this->syncProductBasePrice((int) $productId);
            }
        });

        $message = $updated.' imported price row'.($updated === 1 ? '' : 's').' updated.';

        if ($autoUpdated > 0) {
            $message .= ' '.$autoUpdated.' loose weight row'.($autoUpdated === 1 ? '' : 's').' auto-calculated.';
        }

        if ($skipped > 0) {
            $message .= ' '.$skipped.' row'.($skipped === 1 ? '' : 's').' skipped.';
        }

        return redirect()
            ->route('admin.dailyprices.index', $this->filterRedirectParams($request))
            ->with($updated || $autoUpdated ? 'success' : 'fail', $updated || $autoUpdated ? $message : 'No import price changes found.');
    }

    public function updateWeight(Request $request, $id)
    {
        abort_if(Gate::denies('dailyprices_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        $row = $this->basePriceQuery()->where('pw.id', $id)->first();

        if (! $row) {
            return response()->json([
                'success' => false,
                'message' => 'Price row not found.',
            ], 404);
        }

        $newSellPrice = $this->moneyValue($request->input('sell_price'));
        $newListPrice = $this->moneyValue($request->input('list_price'));

        if ($newSellPrice === null || $newSellPrice < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid selling price.',
            ], 422);
        }

        $newListPrice = $newListPrice ?? (float) $row->list_price;

        if ($newListPrice < 0) {
            return response()->json([
                'success' => false,
                'message' => 'Enter a valid list price.',
            ], 422);
        }

        if (! $this->priceChanged($row, $newSellPrice, $newListPrice)) {
            return response()->json([
                'success' => true,
                'message' => 'No price change.',
                'sell_price' => number_format((float) $row->sell_price, 2, '.', ''),
                'list_price' => number_format((float) $row->list_price, 2, '.', ''),
            ]);
        }

        DB::transaction(function () use ($row, $newSellPrice, $newListPrice) {
            $this->applyPriceUpdate($row, $newSellPrice, $newListPrice, 'ajax', 'Inline AJAX daily price update');
            $this->syncProductBasePrice((int) $row->product_id);
        });

        return response()->json([
            'success' => true,
            'message' => 'Saved.',
            'sell_price' => number_format($newSellPrice, 2, '.', ''),
            'list_price' => number_format($newListPrice, 2, '.', ''),
            'display_sell_price' => '₹'.number_format($newSellPrice, 2),
            'display_list_price' => '₹'.number_format($newListPrice, 2),
        ]);
    }

    public function rollback(Request $request, $id)
    {
        abort_if(Gate::denies('dailyprices_edit'), Response::HTTP_FORBIDDEN, 'THIS ACTION IS UNAUTHORIZED.');

        if (! Schema::hasTable('price_update_logs')) {
            return back()->with('fail', 'Price history table is not available.');
        }

        $log = DB::table('price_update_logs')->where('id', $id)->first();

        if (! $log) {
            return back()->with('fail', 'Price update log not found.');
        }

        $row = $this->basePriceQuery()
            ->where('pw.id', $log->product_weight_id)
            ->first();

        if (! $row) {
            return back()->with('fail', 'Original product weight row is not available.');
        }

        DB::transaction(function () use ($row, $log) {
            $this->applyPriceUpdate(
                $row,
                (float) $log->old_sell_price,
                (float) $log->old_list_price,
                'rollback',
                'Rolled back price update log #'.$log->id
            );

            $this->syncProductBasePrice((int) $row->product_id);
        });

        return redirect()
            ->route('admin.dailyprices.index', $this->filterRedirectParams($request))
            ->with('success', 'Rolled back '.$row->product_title.' - '.$row->weight_name.' to the previous price.');
    }

    private function bulkUpdate(Request $request)
    {
        $action = $request->bulk_action;
        $value = $this->moneyValue($request->bulk_value);
        $selectedIds = array_values(array_filter(array_map('intval', (array) $request->input('selected_weights', []))));

        if (empty($selectedIds)) {
            return back()->with('fail', 'Select at least one price row for bulk update.');
        }

        if ($value === null || $value < 0) {
            return back()->with('fail', 'Enter a valid bulk value.');
        }

        $allowedActions = [
            'set_sell_price',
            'increase_percent',
            'decrease_percent',
            'increase_fixed',
            'decrease_fixed',
        ];

        if (! in_array($action, $allowedActions, true)) {
            return back()->with('fail', 'Invalid bulk price action.');
        }

        $rows = $this->basePriceQuery()
            ->whereIn('pw.id', $selectedIds)
            ->get();

        $updated = 0;
        $syncProductIds = [];
        $applyListPrice = $request->boolean('bulk_apply_list_price');

        DB::transaction(function () use ($rows, $action, $value, $applyListPrice, &$updated, &$syncProductIds) {
            foreach ($rows as $row) {
                $newSellPrice = $this->applyBulkFormula((float) $row->sell_price, $action, $value);
                $newListPrice = $applyListPrice
                    ? $this->applyBulkFormula((float) $row->list_price, $action, $value)
                    : (float) $row->list_price;

                if ($newSellPrice < 0 || $newListPrice < 0) {
                    continue;
                }

                if (! $this->priceChanged($row, $newSellPrice, $newListPrice)) {
                    continue;
                }

                $this->applyPriceUpdate($row, $newSellPrice, $newListPrice, 'bulk', 'Daily admin bulk price update: '.$action);
                $updated++;
                $syncProductIds[$row->product_id] = $row->product_id;
            }

            foreach ($syncProductIds as $productId) {
                $this->syncProductBasePrice((int) $productId);
            }
        });

        return redirect()
            ->route('admin.dailyprices.index', $this->filterRedirectParams($request))
            ->with($updated ? 'success' : 'fail', $updated ? $updated.' selected price row'.($updated === 1 ? '' : 's').' updated.' : 'No selected price changes found.');
    }

    private function parsePriceImportFile($file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            $sheets = Excel::toArray(new class implements ToArray {
                public function array(array $array)
                {
                    return $array;
                }
            }, $file);

            $rawRows = $sheets[0] ?? [];
        } else {
            $rawRows = $this->parseDelimitedPriceFile($file->getRealPath());
        }

        return $this->normalizeImportRows($rawRows);
    }

    private function parseDelimitedPriceFile(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if (! $lines) {
            return [];
        }

        $firstLine = $lines[0] ?? '';
        $delimiterCounts = [
            "\t" => substr_count($firstLine, "\t"),
            ',' => substr_count($firstLine, ','),
            ';' => substr_count($firstLine, ';'),
        ];
        arsort($delimiterCounts);
        $delimiter = reset($delimiterCounts) > 0 ? array_key_first($delimiterCounts) : ',';
        $rows = [];

        foreach ($lines as $line) {
            $rows[] = str_getcsv($line, $delimiter);
        }

        return $rows;
    }

    private function normalizeImportRows(array $rawRows): array
    {
        $rawRows = array_values(array_filter($rawRows, function ($row) {
            return is_array($row) && count(array_filter($row, fn ($value) => $value !== null && $value !== '')) > 0;
        }));

        if (count($rawRows) < 2) {
            return [];
        }

        $headers = array_map(fn ($header) => $this->normalizeImportKey($header), array_shift($rawRows));
        $rows = [];

        foreach ($rawRows as $row) {
            $normalizedRow = [];

            foreach ($headers as $index => $key) {
                if (! $key) {
                    continue;
                }

                $normalizedRow[$key] = $row[$index] ?? null;
            }

            $normalizedRow = $this->normalizeImportAliases($normalizedRow);

            if (! empty(array_filter($normalizedRow, fn ($value) => $value !== null && $value !== ''))) {
                $rows[] = $normalizedRow;
            }
        }

        return $rows;
    }

    private function normalizeImportKey($key): string
    {
        $key = strtolower(trim((string) $key));
        $key = preg_replace('/[^a-z0-9]+/', '_', $key);

        return trim((string) $key, '_');
    }

    private function normalizeImportAliases(array $row): array
    {
        $aliases = [
            'product_weight_id' => ['product_weight_id', 'weight_id', 'price_row_id', 'product_weight'],
            'product_id' => ['product_id'],
            'sku' => ['sku'],
            'product_slug' => ['product_slug', 'slug'],
            'product_title' => ['product_title', 'product_name', 'title', 'name'],
            'weight_name' => ['weight_name', 'weight', 'unit', 'quantity'],
            'sell_price' => ['sell_price', 'selling_price', 'sale_price', 'price', 'new_sell_price'],
            'list_price' => ['list_price', 'mrp', 'strike_price', 'old_price', 'new_list_price'],
        ];

        $normalized = [];

        foreach ($aliases as $targetKey => $sourceKeys) {
            foreach ($sourceKeys as $sourceKey) {
                if (array_key_exists($sourceKey, $row) && $row[$sourceKey] !== null && $row[$sourceKey] !== '') {
                    $normalized[$targetKey] = $row[$sourceKey];
                    break;
                }
            }
        }

        return $normalized;
    }

    private function findImportPriceRow(array $importRow)
    {
        $query = $this->basePriceQuery();

        $weightId = (int) ($importRow['product_weight_id'] ?? 0);

        if ($weightId > 0) {
            return $query->where('pw.id', $weightId)->first();
        }

        $weightName = trim((string) ($importRow['weight_name'] ?? ''));

        if ($weightName === '') {
            return null;
        }

        $query->where('pw.name', $weightName);

        if (! empty($importRow['product_id'])) {
            return $query->where('p.id', (int) $importRow['product_id'])->first();
        }

        if (! empty($importRow['product_slug'])) {
            return $query->where('p.slug', trim((string) $importRow['product_slug']))->first();
        }

        if (! empty($importRow['sku'])) {
            return $query->where('p.sku', trim((string) $importRow['sku']))->first();
        }

        if (! empty($importRow['product_title'])) {
            return $query->where('p.title', trim((string) $importRow['product_title']))->first();
        }

        return null;
    }

    private function basePriceQuery()
    {
        return DB::table('product_weights as pw')
            ->join('products as p', 'p.id', '=', 'pw.product_id')
            ->select([
                'pw.id as weight_id',
                'pw.product_id',
                'pw.name as weight_name',
                'pw.sell_price',
                'pw.list_price',
                'pw.cost_price',
                'pw.qty',
                'pw.stock',
                'pw.status as weight_status',
                'p.title as product_title',
                'p.sku',
                'p.slug',
                'p.status as product_status',
                DB::raw("(SELECT GROUP_CONCAT(DISTINCT c.title ORDER BY c.parent_id, c.priority, c.title SEPARATOR ', ') FROM category_product cp JOIN categories c ON c.id = cp.category_id WHERE cp.product_id = p.id) as category_titles"),
                DB::raw("(SELECT pi.name FROM product_images pi WHERE pi.product_id = p.id AND pi.status = 1 ORDER BY pi.priority ASC, pi.id ASC LIMIT 1) as image_name"),
            ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($subQuery) use ($search) {
                $subQuery
                    ->where('p.title', 'like', '%'.$search.'%')
                    ->orWhere('p.sku', 'like', '%'.$search.'%')
                    ->orWhere('pw.name', 'like', '%'.$search.'%');
            });
        }

        if ($request->filled('parent_category_id')) {
            $parentCategoryId = (int) $request->parent_category_id;
            $query->whereExists(function ($subQuery) use ($parentCategoryId) {
                $subQuery->select(DB::raw(1))
                    ->from('category_product as cp_parent')
                    ->join('categories as c_parent', 'c_parent.id', '=', 'cp_parent.category_id')
                    ->whereRaw('cp_parent.product_id = p.id')
                    ->where(function ($categoryQuery) use ($parentCategoryId) {
                        $categoryQuery
                            ->where('c_parent.id', $parentCategoryId)
                            ->orWhere('c_parent.parent_id', $parentCategoryId);
                    });
            });
        }

        if ($request->filled('category_id')) {
            $categoryId = (int) $request->category_id;
            $query->whereExists(function ($subQuery) use ($categoryId) {
                $subQuery->select(DB::raw(1))
                    ->from('category_product as cp_child')
                    ->whereRaw('cp_child.product_id = p.id')
                    ->where('cp_child.category_id', $categoryId);
            });
        }

        if ($request->filled('weight_name')) {
            $query->where('pw.name', $request->weight_name);
        }

        if ($request->filled('product_status')) {
            if ((string) $request->product_status === '1') {
                $query->where('p.status', 1);
            } else {
                $query->where(function ($statusQuery) {
                    $statusQuery->where('p.status', '<>', 1)->orWhereNull('p.status');
                });
            }
        }

        if ($request->filled('weight_status')) {
            if ((string) $request->weight_status === '1') {
                $query->where('pw.status', 1);
            } else {
                $query->where(function ($statusQuery) {
                    $statusQuery->where('pw.status', '<>', 1)->orWhereNull('pw.status');
                });
            }
        }
    }

    private function moneyValue($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $cleanValue = preg_replace('/[^\d.\-]/', '', (string) $value);

        if ($cleanValue === '' || ! is_numeric($cleanValue)) {
            return null;
        }

        return round((float) $cleanValue, 2);
    }

    private function priceChanged($row, float $newSellPrice, float $newListPrice): bool
    {
        return round((float) $row->sell_price, 2) !== round($newSellPrice, 2)
            || round((float) $row->list_price, 2) !== round($newListPrice, 2);
    }

    private function applyPriceUpdate($row, float $newSellPrice, float $newListPrice, string $source, string $notes): void
    {
        DB::table('product_weights')
            ->where('id', $row->weight_id)
            ->update([
                'sell_price' => $newSellPrice,
                'list_price' => $newListPrice,
                'updated_at' => now(),
            ]);

        $this->insertPriceLog($row, $newSellPrice, $newListPrice, $source, $notes);
    }

    private function insertPriceLog($row, float $newSellPrice, float $newListPrice, string $source, string $notes): void
    {
        if (! Schema::hasTable('price_update_logs')) {
            return;
        }

        DB::table('price_update_logs')->insert([
            'product_id' => $row->product_id,
            'product_weight_id' => $row->weight_id,
            'product_title' => $row->product_title,
            'weight_name' => $row->weight_name,
            'old_sell_price' => $row->sell_price,
            'new_sell_price' => $newSellPrice,
            'old_list_price' => $row->list_price,
            'new_list_price' => $newListPrice,
            'update_source' => $source,
            'updated_by' => auth()->id(),
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function autoCalculateLooseWeights(int $productId, int $sourceWeightId, float $kgSellPrice, float $kgListPrice): int
    {
        $rows = $this->basePriceQuery()
            ->where('pw.product_id', $productId)
            ->where('pw.id', '<>', $sourceWeightId)
            ->get();

        $updated = 0;

        foreach ($rows as $row) {
            $factor = $this->weightFactor($row->weight_name);

            if ($factor === null || $factor <= 0 || $factor >= 1) {
                continue;
            }

            $newSellPrice = $this->roundLooseFlowerPrice($kgSellPrice * $factor);
            $newListPrice = $this->roundLooseFlowerPrice($kgListPrice * $factor);

            if (! $this->priceChanged($row, $newSellPrice, $newListPrice)) {
                continue;
            }

            $this->applyPriceUpdate($row, $newSellPrice, $newListPrice, 'auto_kg', 'Auto-calculated from 1KG price');
            $updated++;
        }

        return $updated;
    }

    private function weightFactor(?string $weightName): ?float
    {
        $name = strtolower((string) $weightName);
        $name = preg_replace('/[\._-]+/', ' ', $name);
        $name = preg_replace('/\s+/', ' ', trim($name));

        if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|kgs|kilogram|kilograms)\b/', $name, $matches)) {
            return round((float) $matches[1], 4);
        }

        if (preg_match('/(\d+(?:\.\d+)?)\s*(gram|grams|gm|gms|grm|g)\b/', $name, $matches)) {
            return round(((float) $matches[1]) / 1000, 4);
        }

        return null;
    }

    private function roundLooseFlowerPrice(float $price): float
    {
        if ($price <= 0) {
            return 0;
        }

        return round(max(5, round($price / 5) * 5), 2);
    }

    private function applyBulkFormula(float $currentPrice, string $action, float $value): float
    {
        return match ($action) {
            'set_sell_price' => round($value, 2),
            'increase_percent' => round($currentPrice + (($currentPrice * $value) / 100), 2),
            'decrease_percent' => round($currentPrice - (($currentPrice * $value) / 100), 2),
            'increase_fixed' => round($currentPrice + $value, 2),
            'decrease_fixed' => round($currentPrice - $value, 2),
            default => $currentPrice,
        };
    }

    private function syncProductBasePrice(int $productId): void
    {
        if (! Schema::hasTable('products')) {
            return;
        }

        $productColumns = Schema::getColumnListing('products');
        $allowedColumns = array_flip($productColumns);
        $baseWeight = DB::table('product_weights')
            ->where('product_id', $productId)
            ->where('status', 1)
            ->orderByRaw('CAST(sell_price AS DECIMAL(10,2)) ASC')
            ->orderBy('id')
            ->first();

        if (! $baseWeight) {
            return;
        }

        $payload = [
            'sell_price' => $baseWeight->sell_price,
            'list_price' => $baseWeight->list_price,
            'weight' => $baseWeight->name,
            'updated_at' => now(),
        ];

        $payload = array_intersect_key($payload, $allowedColumns);

        if (! empty($payload)) {
            DB::table('products')->where('id', $productId)->update($payload);
        }
    }

    private function weightOrderSql(): string
    {
        return "CASE
            WHEN LOWER(pw.name) REGEXP '100[[:space:]._-]*(grams|gram|gms|gm|grm|g)' THEN 10
            WHEN LOWER(pw.name) REGEXP '250[[:space:]._-]*(grams|gram|gms|gm|grm|g)' THEN 20
            WHEN LOWER(pw.name) REGEXP '500[[:space:]._-]*(grams|gram|gms|gm|grm|g)' THEN 30
            WHEN LOWER(pw.name) REGEXP '1[[:space:]._-]*(kg|kgs|kilogram|kilograms)' THEN 40
            ELSE 90
        END";
    }

    private function filterRedirectParams(Request $request): array
    {
        return $request->only([
            'search',
            'parent_category_id',
            'category_id',
            'weight_name',
            'product_status',
            'weight_status',
            'per_page',
        ]);
    }
}
