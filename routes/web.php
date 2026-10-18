<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FarmManagementController;
use App\Http\Controllers\Admin\HarvestManagementController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Pro\AiController;
use App\Http\Controllers\Pro\FarmController;
use App\Http\Controllers\Pro\HarvestController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TraceabilityController;
use Illuminate\Support\Facades\Route;

// ==================== AUTHENTICATION ====================
Route::middleware('guest')->group(function () {
    // Registration
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);

    // Login
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

// Logout
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// ==================== FRONTEND ====================
Route::name('frontend.')->group(function () {
    // Home
    Route::get('/', [HomeController::class, 'index'])->name('home');

    // À propos (placeholder)
    Route::get('/about', [HomeController::class, 'index'])->name('about'); // TODO: créer la vue about

    // Produits : catalogue de tous les lots récoltés par les producteurs
    Route::get('/products', [ProductController::class, 'index'])->name('products');

    // Contact (placeholder)
    Route::get('/contact', [HomeController::class, 'index'])->name('contact'); // TODO: créer la vue contact
});

// ==================== PRO DASHBOARD ====================
Route::prefix('pro')->name('pro.')->middleware(['auth', 'pro'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Pro\ProDashboardController::class, 'index'])->name('dashboard');

    // Module 1 (Producteur) : Gestion des Exploitations & Origine des Récoltes
    Route::middleware('producer')->group(function () {
        Route::get('/farms/export', [FarmController::class, 'export'])->name('farms.export');
        Route::resource('farms', FarmController::class);

        Route::get('/harvests/export', [HarvestController::class, 'export'])->name('harvests.export');
        Route::get('/harvests/{harvest}/label', [HarvestController::class, 'label'])->name('harvests.label');
        Route::resource('harvests', HarvestController::class);

        Route::prefix('ai')->name('ai.')->middleware('throttle:30,1')->group(function () {
            Route::post('/farm-description', [AiController::class, 'farmDescription'])->name('farm-description');
            Route::post('/harvest-description', [AiController::class, 'harvestDescription'])->name('harvest-description');
            Route::get('/farms/{farm}/insights', [AiController::class, 'farmInsights'])->name('farm-insights');
            Route::post('/chat', [AiController::class, 'chat'])->name('chat');
        });
    });
});

// Traçabilité publique d'un lot (cible du QR code)
Route::get('/origine/{code}', [TraceabilityController::class, 'show'])->name('traceability.show');
// Recherche d'un lot par son code (« Tracer un lot »)
Route::get('/tracer', [TraceabilityController::class, 'lookup'])->name('traceability.lookup');
// Annuaire et vitrines publiques des exploitations
Route::get('/exploitations', [TraceabilityController::class, 'farms'])->name('farms.directory');
Route::get('/exploitations/{farm}', [TraceabilityController::class, 'farm'])->name('farms.public');

// ==================== BACKEND ====================
Route::prefix('admin')->name('backend.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard (indicateurs réels)
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Approvals Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/approvals', [App\Http\Controllers\Admin\UserApprovalController::class, 'index'])->name('approvals');
        Route::post('/{user}/approve', [App\Http\Controllers\Admin\UserApprovalController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [App\Http\Controllers\Admin\UserApprovalController::class, 'reject'])->name('reject');
        Route::post('/{user}/reset-pending', [App\Http\Controllers\Admin\UserApprovalController::class, 'resetToPending'])->name('reset-pending');
    });

    // Produits (Module 1) : contrôle de tous les produits récoltés par les producteurs
    Route::prefix('products')->name('products.')->controller(HarvestManagementController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{harvest}/edit', 'edit')->name('edit');
        Route::get('/{harvest}/risk', 'explainRisk')->middleware('throttle:30,1')->name('risk');
        Route::put('/{harvest}', 'update')->name('update');
        Route::patch('/{harvest}/status', 'updateStatus')->name('status');
        Route::delete('/{harvest}', 'destroy')->name('destroy');
    });

    // Catégories de produits (Module 1)
    Route::prefix('categories')->name('categories.')->controller(CategoryController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{category}/edit', 'edit')->name('edit');
        Route::put('/{category}', 'update')->name('update');
        Route::patch('/{category}/toggle', 'toggle')->name('toggle');
        Route::delete('/{category}', 'destroy')->name('destroy');
    });

    // Fermes (Module 1) : contrôle de toutes les exploitations
    Route::prefix('farms')->name('farms.')->controller(FarmManagementController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/{farm}/edit', 'edit')->name('edit');
        Route::put('/{farm}', 'update')->name('update');
        Route::patch('/{farm}/status', 'updateStatus')->name('status');
        Route::delete('/{farm}', 'destroy')->name('destroy');
    });

    // Traçabilité - Lots : les lots sont les produits du Module 1
    Route::redirect('/batches', '/admin/products')->name('batches.index');

    // Traçabilité - Historique
    Route::prefix('history')->name('history.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer historique chaîne
        })->name('index');
    });

    // Certifications
    Route::prefix('certifications')->name('certifications.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer liste certifications
        })->name('index');
    });

    // Labels
    Route::prefix('labels')->name('labels.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer verification labels
        })->name('index');
    });

    // Anti-Greenwashing
    Route::prefix('greenwashing')->name('greenwashing.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer anti-greenwashing
        })->name('index');
    });

    // Éco-Score
    Route::prefix('ecoscore')->name('ecoscore.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer gestion éco-score
        })->name('index');
    });
});

// ==================== MODULE 3 - DISTRIBUTEUR ====================
require __DIR__.'/distributor.php';
