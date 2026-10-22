<?php

use App\Http\Controllers\Admin\ShipmentController as AdminShipmentController;
use App\Http\Controllers\Admin\TransportModeController;
use App\Http\Controllers\Distributor\CarbonDashboardController;
use App\Http\Controllers\Distributor\PointOfSaleController;
use App\Http\Controllers\Distributor\ShipmentController;
use App\Http\Controllers\Distributor\ShipmentEventController;
use App\Http\Controllers\Distributor\ShipmentPdfController;
use App\Http\Controllers\Distributor\TransportRecommendationController;
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
    // Dashboard carbone
    Route::get('dashboard', CarbonDashboardController::class)->name('dashboard');

    // Points de vente
    Route::resource('points-of-sale', PointOfSaleController::class)
        ->parameters(['points-of-sale' => 'pointOfSale']);

    // Recommandation IA du mode de transport, utilisable avant l'enregistrement de l'expédition
    Route::post('shipments/transport-recommendation', TransportRecommendationController::class)
        ->name('shipments.transport-recommendation');

    // Expéditions : création et modification en une page, étapes de transport comprises
    Route::resource('shipments', ShipmentController::class);
    Route::patch('shipments/{shipment}/status', [ShipmentController::class, 'updateStatus'])
        ->name('shipments.status');

    // Journal de traçabilité, rempli pendant le suivi de l'expédition
    Route::scopeBindings()->group(function () {
        Route::resource('shipments.events', ShipmentEventController::class)
            ->only(['store', 'edit', 'update', 'destroy']);
    });

    // Rapport de traçabilité PDF
    Route::get('shipments/{shipment}/pdf', ShipmentPdfController::class)->name('shipments.pdf');
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
