<?php

use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Admin\TransportModeController;
use App\Http\Controllers\Distributor\PointOfSaleController;
use App\Http\Controllers\Distributor\ShipmentController;
use App\Http\Controllers\Distributor\ShipmentEventController;
use App\Http\Controllers\Distributor\ShipmentLegController;
use App\Http\Middleware\DistributorMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Module 3 - Distributeur : Traçabilité Logistique & Empreinte Carbone
|--------------------------------------------------------------------------
| Chargé depuis routes/web.php
*/

// ==================== ESPACE DISTRIBUTEUR ====================
Route::prefix('pro/distributor')->name('pro.distributor.')->middleware(['auth', DistributorMiddleware::class])->group(function () {
    // Points de vente
    Route::resource('points-of-sale', PointOfSaleController::class)
        ->parameters(['points-of-sale' => 'pointOfSale']);

    // Expéditions
    Route::resource('shipments', ShipmentController::class);

    // Étapes de transport et journal, rattachés à une expédition
    Route::scopeBindings()->group(function () {
        Route::resource('shipments.legs', ShipmentLegController::class)
            ->only(['store', 'edit', 'update', 'destroy']);
        Route::resource('shipments.events', ShipmentEventController::class)
            ->only(['store', 'edit', 'update', 'destroy']);
    });
});

// ==================== BACKEND (ADMIN) ====================
Route::prefix('admin/logistics')->name('backend.logistics.')->middleware(['auth', 'admin'])->group(function () {
    // Facteurs d'émission
    Route::resource('transport-modes', TransportModeController::class)
        ->except(['show'])
        ->parameters(['transport-modes' => 'transportMode']);

    // Supervision des expéditions
    Route::resource('shipments', AdminShipmentController::class)
        ->only(['index', 'show', 'destroy']);
});
