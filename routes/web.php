<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminDashboardItemController;
use App\Http\Controllers\Admin\AdminSettingsController;

// Public Dashboard Routes (UUID-based)
Route::get('/d/{dashboard:uuid}', [DashboardController::class, 'show'])
    ->name('dashboard.show');

// Admin Routes (no authentication)
Route::prefix('admin')->name('admin.')->group(function () {
    // Dashboard management
    Route::resource('dashboards', AdminDashboardController::class);

    // Dashboard items
    Route::post('dashboards/{dashboard}/items', [AdminDashboardItemController::class, 'store'])
        ->name('dashboards.items.store');
    Route::get('items/{item}/settings', [AdminDashboardItemController::class, 'settings'])
        ->name('items.settings');
    Route::put('items/{item}', [AdminDashboardItemController::class, 'update'])
        ->name('items.update');
    Route::delete('items/{item}', [AdminDashboardItemController::class, 'destroy'])
        ->name('items.destroy');
    Route::post('dashboards/{dashboard}/items/reorder', [AdminDashboardItemController::class, 'reorder'])
        ->name('dashboards.items.reorder');

    // Settings
    Route::get('settings', [AdminSettingsController::class, 'index'])->name('settings.index');
    Route::post('settings', [AdminSettingsController::class, 'store'])->name('settings.store');
    Route::post('settings/test', [AdminSettingsController::class, 'test'])->name('settings.test');
});

// Redirect home to admin
Route::redirect('/', '/admin/dashboards');
