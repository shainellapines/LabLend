<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\RoomController;
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
    // {equipment:asset_tag}: find the item by its asset tag (e.g. /equipment/PRJ-0004) instead of its id.
    Route::get('/equipment/{equipment:asset_tag}', [EquipmentController::class, 'show'])
        ->withTrashed()
        ->name('equipment.show');

    // Rooms: everyone can browse; changes are under /admin below.
    Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    Route::get('/rooms/{room}', [RoomController::class, 'show'])->name('rooms.show');

    // Reservations (borrower side). index (My Reservations) comes in step 2.6.
    Route::resource('reservations', ReservationController::class)->only('create', 'store', 'show', 'edit', 'update');

    // Admin-only pages: URLs start with /admin, route names start with "admin.".
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        // One line = 6 routes: index, create, store, edit, update, destroy (names admin.categories.*).
        Route::resource('categories', Admin\CategoryController::class)->except('show');
        Route::resource('equipment', Admin\EquipmentController::class)->except('index', 'show');
        Route::resource('rooms', Admin\RoomController::class)->except('index', 'show');
    });
});

require __DIR__.'/auth.php';
