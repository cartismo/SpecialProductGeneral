<?php

use Illuminate\Support\Facades\Route;
use Modules\SpecialProductGeneral\Http\Controllers\Admin\SettingsController;

Route::prefix('modules/general/special-product-general')->name('admin.general.special.')->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::post('/products', [SettingsController::class, 'addProduct'])->name('products.add');
    Route::delete('/products/{specialProduct}', [SettingsController::class, 'removeProduct'])->name('products.remove');
    Route::post('/products/reorder', [SettingsController::class, 'reorder'])->name('products.reorder');
    Route::post('/products/{specialProduct}/toggle', [SettingsController::class, 'toggle'])->name('products.toggle');
});