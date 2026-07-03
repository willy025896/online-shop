<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Concerns\ValidatesProductRequest;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use League\Csv\Reader;
use League\Csv\Writer;

class ProductController extends Controller
{
    use ValidatesProductRequest;

    public function index(Request $request)
    {
        $lowStockThreshold = (int) config('inventory.low_stock_threshold');

        $products = auth()->user()->shop->products()
            ->with('primaryImage', 'category')
            ->when($request->boolean('low_stock'), fn ($q) => $q->lowStock($lowStockThreshold))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Seller/Products/Index', [
            'products' => $products,
            'filters' => ['low_stock' => $request->boolean('low_stock')],
            'lowStockThreshold' => $lowStockThreshold,
        ]);
    }

    public function create()
    {
        return Inertia::render('Seller/Products/Create', [
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->productValidationRules());

        $validated['shop_id'] = auth()->user()->shop->id;
        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);

        $product = Product::create($validated);

        return redirect()->route('seller.products.edit', $product)
            ->with('success', 'Product created.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $product->load('images');

        return Inertia::render('Seller/Products/Edit', [
            'product' => $product,
            'categories' => Category::active()->orderBy('sort_order')->get(),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $validated = $request->validate($this->productValidationRules());

        $product->update($validated);

        return back()->with('success', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        $this->authorize('delete', $product);

        $product->delete();

        return redirect()->route('seller.products.index')
            ->with('success', 'Product deleted.');
    }

    /**
     * Stream the seller's own products as a CSV download. Chunked so a large
     * catalog never has to be fully materialized in memory.
     */
    public function export()
    {
        return response()->streamDownload(function () {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->insertOne(['name', 'description', 'category', 'price', 'compare_price', 'stock', 'status', 'is_featured']);

            auth()->user()->shop->products()
                ->with('category:id,name')
                ->orderBy('id')
                ->chunk(200, function ($products) use ($csv) {
                    foreach ($products as $product) {
                        $csv->insertOne([
                            $product->name,
                            $product->description,
                            $product->category?->name,
                            $product->price,
                            $product->compare_price,
                            $product->stock,
                            $product->status,
                            $product->is_featured ? 1 : 0,
                        ]);
                    }
                });
        }, 'products.csv', ['Content-Type' => 'text/csv']);
    }

    public function importForm()
    {
        return Inertia::render('Seller/Products/Import');
    }

    /**
     * Upsert products from an uploaded CSV, matched by name within the
     * seller's own shop (see ADR-009). Each row validates and saves
     * independently — a bad row is recorded as a failure without rolling
     * back the rows around it.
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $shop = auth()->user()->shop;
        $categoryIdsByName = Category::pluck('id', 'name');

        $csv = Reader::createFromPath($request->file('file')->getRealPath());
        $csv->setHeaderOffset(0);

        $created = 0;
        $updated = 0;
        $failed = [];
        $rowNumber = 1;

        foreach ($csv->getRecords() as $record) {
            $rowNumber++;

            try {
                $data = [
                    'name' => trim($record['name'] ?? ''),
                    'description' => ($record['description'] ?? '') !== '' ? $record['description'] : null,
                    'category_id' => $categoryIdsByName[$record['category'] ?? ''] ?? null,
                    'price' => $record['price'] ?? null,
                    'compare_price' => ($record['compare_price'] ?? '') !== '' ? $record['compare_price'] : null,
                    'stock' => $record['stock'] ?? null,
                    'status' => $record['status'] ?? null,
                    'is_featured' => filter_var($record['is_featured'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];

                $validated = Validator::make($data, $this->productValidationRules())->validate();

                $existing = $shop->products()->where('name', $validated['name'])->first();

                if ($existing !== null) {
                    $existing->update($validated);
                    $updated++;
                } else {
                    $validated['shop_id'] = $shop->id;
                    $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);
                    Product::create($validated);
                    $created++;
                }
            } catch (ValidationException $e) {
                $failed[] = [
                    'row' => $rowNumber,
                    'reason' => collect($e->errors())->flatten()->implode(' '),
                ];
            }
        }

        return Inertia::render('Seller/Products/Import', [
            'result' => compact('created', 'updated', 'failed'),
        ]);
    }
}
