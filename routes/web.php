<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
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
    Route::get('/', function () {
        return view('frontend.pages.home');
    })->name('home');

    // À propos (placeholder)
    Route::get('/about', function () {
        return view('frontend.pages.home'); // TODO: créer la vue about
    })->name('about');

    // Produits (placeholder)
    Route::get('/products', function () {
        return view('frontend.pages.home'); // TODO: créer la vue products
    })->name('products');

    // Contact (placeholder)
    Route::get('/contact', function () {
        return view('frontend.pages.home'); // TODO: créer la vue contact
    })->name('contact');
});

// ==================== BACKEND ====================
Route::prefix('admin')->name('backend.')->middleware(['auth', 'admin'])->group(function () {
    // Dashboard
    Route::get('/dashboard', function () {
        return view('backend.pages.dashboard');
    })->name('dashboard');

    // User Approvals Management
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/approvals', [App\Http\Controllers\Admin\UserApprovalController::class, 'index'])->name('approvals');
        Route::post('/{user}/approve', [App\Http\Controllers\Admin\UserApprovalController::class, 'approve'])->name('approve');
        Route::post('/{user}/reject', [App\Http\Controllers\Admin\UserApprovalController::class, 'reject'])->name('reject');
        Route::post('/{user}/reset-pending', [App\Http\Controllers\Admin\UserApprovalController::class, 'resetToPending'])->name('reset-pending');
    });

    // Produits
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer liste produits
        })->name('index');
        
        Route::get('/create', function () {
            return view('backend.pages.dashboard'); // TODO: créer formulaire produit
        })->name('create');
    });

    // Catégories
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer liste catégories
        })->name('index');
    });

    // Fermes
    Route::prefix('farms')->name('farms.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer liste fermes
        })->name('index');
        
        Route::get('/create', function () {
            return view('backend.pages.dashboard'); // TODO: créer formulaire ferme
        })->name('create');
    });

    // Traçabilité - Lots
    Route::prefix('batches')->name('batches.')->group(function () {
        Route::get('/', function () {
            return view('backend.pages.dashboard'); // TODO: créer liste lots
        })->name('index');
    });

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
