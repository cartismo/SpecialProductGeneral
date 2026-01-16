<?php

namespace Modules\SpecialProductGeneral\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Traits\HasMultiStoreModuleSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\SpecialProductGeneral\Models\SpecialProduct;

class SettingsController extends Controller
{
    use HasMultiStoreModuleSettings;

    protected function getModuleSlug(): string
    {
        return 'special-product-general';
    }

    protected function getDefaultSettings(): array
    {
        return [
            'enabled' => true,
            'title' => 'Special Offers',
            'max_products' => 12,
            'sort_order' => 0,
        ];
    }

    public function index(): Response
    {
        $data = $this->getMultiStoreData();

        $specialProducts = SpecialProduct::with('product.translations')
            ->ordered()
            ->get();

        $products = Product::with('translations')
            ->where('is_active', true)
            ->orderBy('sku')
            ->get(['id', 'sku', 'price', 'image']);

        $data['specialProducts'] = $specialProducts;
        $data['products'] = $products;

        return Inertia::render('SpecialProductGeneral::Admin/Settings', $data);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'is_enabled' => 'boolean',
            'settings.enabled' => 'boolean',
            'settings.title' => 'required|string|max:255',
            'settings.max_products' => 'required|integer|min:1|max:50',
            'settings.sort_order' => 'integer|min:0',
        ]);

        return $this->saveStoreSettings($request);
    }

    public function addProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'badge_text' => 'nullable|string|max:50',
            'badge_color' => 'nullable|string|max:20',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
        ]);

        $maxOrder = SpecialProduct::max('sort_order') ?? 0;

        SpecialProduct::updateOrCreate(
            ['product_id' => $validated['product_id']],
            [
                'badge_text' => $validated['badge_text'] ?? null,
                'badge_color' => $validated['badge_color'] ?? null,
                'starts_at' => $validated['starts_at'] ?? null,
                'ends_at' => $validated['ends_at'] ?? null,
                'sort_order' => $maxOrder + 1,
                'is_active' => true,
            ]
        );

        return back()->with('success', 'Product added to special offers.');
    }

    public function removeProduct(SpecialProduct $specialProduct): RedirectResponse
    {
        $specialProduct->delete();

        return back()->with('success', 'Product removed from special offers.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|integer|exists:special_products,id',
            'products.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['products'] as $item) {
            SpecialProduct::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Order updated.');
    }

    public function toggle(SpecialProduct $specialProduct): RedirectResponse
    {
        $specialProduct->update(['is_active' => !$specialProduct->is_active]);

        return back()->with('success', 'Status updated.');
    }
}