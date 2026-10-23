<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\FarmManagementController;
use App\Http\Controllers\Admin\HarvestManagementController;
use App\Http\Controllers\Admin\ProcessorSupervisionController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Consumer\CatalogController;
use App\Http\Controllers\Consumer\CartController;
use App\Http\Controllers\Consumer\NutriAIController;
use App\Http\Controllers\Consumer\OrderController;
use App\Http\Controllers\Consumer\StripeController;
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

    // Mot de passe oublié : lien par e-mail (token expirable, voir config/auth.php)
    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.update');
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

// ==================== CONSUMER ====================
Route::prefix('consumer')->name('consumer.')->group(function () {
    // Catalogue des produits finis (accessible sans auth pour consultation)
    Route::get('/catalogue', [CatalogController::class, 'index'])->name('catalog');
    Route::get('/catalogue/{stock}', [CatalogController::class, 'show'])->name('catalog.show');

    // Panier et commandes (nécessite auth et role=consumer)
    Route::middleware(['auth', 'consumer'])->group(function () {
        // Panier
        Route::get('/panier', [CartController::class, 'index'])->name('cart');
        Route::post('/panier/add/{stock}', [CartController::class, 'add'])->name('cart.add');
        Route::put('/panier/{cartItem}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/panier/{cartItem}', [CartController::class, 'remove'])->name('cart.remove');
        Route::delete('/panier', [CartController::class, 'clear'])->name('cart.clear');

        // Commandes
        Route::get('/commandes', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/commandes/checkout', [OrderController::class, 'checkout'])->name('orders.checkout');
        Route::post('/commandes', [OrderController::class, 'store'])->name('orders.store');
        Route::get('/commandes/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::put('/commandes/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

        // Stripe
        Route::get('/stripe/create/{order}', [StripeController::class, 'createCheckoutSession'])->name('stripe.create');
        Route::get('/stripe/success/{order}', [StripeController::class, 'success'])->name('stripe.success');
        Route::get('/stripe/cancel/{order}', [StripeController::class, 'cancel'])->name('stripe.cancel');

        // NutriAI Assistant
        Route::get('/nutri-ai', [NutriAIController::class, 'index'])->name('nutri-ai.index');
        Route::post('/nutri-ai/chat', [NutriAIController::class, 'chat'])
            ->middleware('throttle:10,1')
            ->name('nutri-ai.chat');
        Route::post('/nutri-ai/search', [NutriAIController::class, 'search'])->name('nutri-ai.search');
        Route::post('/nutri-ai/product', [NutriAIController::class, 'getProduct'])->name('nutri-ai.product');
        Route::post('/nutri-ai/traceability', [NutriAIController::class, 'traceability'])->name('nutri-ai.traceability');
    });
});

// Webhook Stripe (exclu du middleware CSRF)
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->name('stripe.webhook')->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// ==================== PRO DASHBOARD ====================
Route::prefix('pro')->name('pro.')->middleware(['auth', 'pro'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\Pro\ProDashboardController::class, 'index'])->name('dashboard');

    // Module 3 (Transformateur/Processor) : Réception des Matières Brutes
    Route::middleware('processor')->group(function () {
        Route::prefix('processor/raw-materials')->name('processor.raw-materials.')->controller(App\Http\Controllers\Pro\ProcessorRawMaterialController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create/{harvest}', 'create')->name('create');
            Route::post('/store/{harvest}', 'store')->name('store');
            Route::get('/{reception}', 'show')->name('show');
            Route::get('/{reception}/edit', 'edit')->name('edit');
            Route::put('/{reception}', 'update')->name('update');
            Route::post('/{reception}/approve', 'approve')->name('approve');
            Route::post('/{reception}/reject', 'reject')->name('reject');
            Route::delete('/{reception}', 'destroy')->name('destroy');
        });

    // Module 2 (Transformateur/Processor) : Gestion des Recettes & Ingrédients
        // AI: Generate recipe content (MUST be before resource route)
        Route::post('processor/recipes/ai/generate', [App\Http\Controllers\Pro\AiController::class, 'generateRecipeContent'])->name('processor.recipes.ai.generate');

        Route::resource('processor/recipes', App\Http\Controllers\Pro\ProcessorRecipeController::class, [
            'names' => [
                'index' => 'processor.recipes.index',
                'create' => 'processor.recipes.create',
                'store' => 'processor.recipes.store',
                'show' => 'processor.recipes.show',
                'edit' => 'processor.recipes.edit',
                'update' => 'processor.recipes.update',
                'destroy' => 'processor.recipes.destroy',
            ],
            'parameters' => [
                'recipe' => 'recipe',
            ]
        ]);

        // Module 3 (Transformateur/Processor) : Gestion des Lots de Production & Traçabilité
        Route::prefix('processor/production-lots')->name('processor.production-lots.')->controller(App\Http\Controllers\Pro\ProcessorProductionLotController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{production_lot}', 'show')->name('show');
            Route::get('/{production_lot}/edit', 'edit')->name('edit');
            Route::put('/{production_lot}', 'update')->name('update');
            Route::delete('/{production_lot}', 'destroy')->name('destroy');
            Route::get('/{production_lot}/qr-code', 'generateQrCode')->name('qr-code');
        });
    });

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

    // Processor Supervision (Module 3 - Transformateur)
    Route::prefix('processor-monitoring')->name('processor-monitoring.')->controller(App\Http\Controllers\Admin\ProcessorSupervisionController::class)->group(function () {
        Route::get('/', 'index')->name('index');
    });
});

// ==================== MODULE 3 - DISTRIBUTEUR ====================
require __DIR__.'/distributor.php';

// ==================== GESTION USER - PROFIL ====================
require __DIR__.'/profile.php';
