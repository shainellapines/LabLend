<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Every logged-in page uses 'auth' (must be logged in) and 'active' (account not deactivated).
Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->middleware('verified')->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Equipment: everyone can browse; changes are under /admin below.
    Route::get('/equipment', [EquipmentController::class, 'index'])->name('equipment.index');

    // Admin-only pages: URLs start with /admin, route names start with "admin.".
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // One line = 6 routes: index, create, store, edit, update, destroy (names admin.categories.*).
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::resource('equipment', Admin\EquipmentController::class)->except('index', 'show');
    });
});

require __DIR__.'/auth.php';
