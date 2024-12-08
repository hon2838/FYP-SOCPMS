<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PaperworkController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AdminController;

// Home route with logging
Route::get('/', function () {
    Log::debug('Home route accessed', [
        'authenticated' => Auth::check(),
        'user_type' => Auth::check() ? Auth::user()->user_type : null
    ]);

    if (Auth::check()) {
        $route = Auth::user()->user_type === 'admin' ? 'admin.dashboard' : 'user.dashboard';
        Log::info('Redirecting authenticated user', [
            'user_id' => Auth::id(),
            'user_type' => Auth::user()->user_type,
            'route' => $route
        ]);
        return redirect()->route($route);
    }

    Log::info('Redirecting unauthenticated user to login');
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
    Route::middleware(['auth', 'check.role:admin'])->prefix('admin')->group(function () {
        Route::get('/dashboard', [PaperworkController::class, 'adminDashboard'])->name('admin.dashboard');
        Route::get('/manage-accounts', [UserController::class, 'index'])->name('admin.accounts');
        Route::resource('users', UserController::class);
    });

    // User routes
    Route::middleware(['auth', 'check.role:user'])->group(function () {
        Log::info('User route group accessed');
        Route::get('/user/dashboard', [PaperworkController::class, 'userDashboard'])->name('user.dashboard');
        Route::get('/account', [UserController::class, 'show'])->name('user.account');
    });

    // Common routes
    Route::resource('paperworks', PaperworkController::class);
});



