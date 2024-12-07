<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PaperworkController;
use App\Http\Controllers\UserController;

// Home route
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route(Auth::user()->user_type === 'admin' ? 'admin.dashboard' : 'user.dashboard');
    }
    return redirect()->route('login');
});

// Guest routes
Route::middleware(['guest'])->group(function () {
    // Login routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    // Register routes
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Admin routes
    Route::middleware(['check-role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [PaperworkController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/manage-accounts', [UserController::class, 'index'])->name('admin.accounts');
        Route::resource('users', UserController::class);
    });

    // User routes
    Route::middleware(['check-role:user'])->group(function () {
        Route::get('/user/dashboard', [PaperworkController::class, 'userDashboard'])->name('user.dashboard');
        Route::get('/account', [UserController::class, 'show'])->name('user.account');
    });

    // Common routes
    Route::resource('paperworks', PaperworkController::class);
});


