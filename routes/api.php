<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\FlowController;
use App\Http\Controllers\Api\LayoutController;

Route::prefix('v1')->group(function () {
    // Devices
    Route::get('devices', [DeviceController::class, 'index']);
    Route::get('devices/{deviceId}', [DeviceController::class, 'show']);
    Route::post('devices/{deviceId}/control', [DeviceController::class, 'control']);
    Route::post('devices/states', [DeviceController::class, 'states']);

    // Flows
    Route::get('flows', [FlowController::class, 'index']);
    Route::post('flows/{flowId}/trigger', [FlowController::class, 'trigger']);

    // Layout
    Route::post('dashboards/{uuid}/layout', [LayoutController::class, 'save']);
    Route::get('dashboards/{uuid}/available-items', [LayoutController::class, 'availableItems']);
    Route::post('dashboards/{uuid}/items', [LayoutController::class, 'addItem']);
    Route::get('dashboards/{uuid}/items/{itemId}', [LayoutController::class, 'getItem']);
    Route::put('dashboards/{uuid}/items/{itemId}', [LayoutController::class, 'updateItem']);
    Route::delete('dashboards/{uuid}/items/{itemId}', [LayoutController::class, 'removeItem']);
});
