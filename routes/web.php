<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\FuelLogController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\RentalPartnerController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin')->group(function () {
        // admin modules: bookings, master data, monitoring, reports
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::get('/bookings/export', [BookingController::class, 'export'])->name('bookings.export');
        Route::post('/bookings/{booking}/start', [BookingController::class, 'start'])->name('bookings.start');
        Route::post('/bookings/{booking}/return', [BookingController::class, 'complete'])->name('bookings.complete');
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel'])->name('bookings.cancel');
        Route::post('/bookings', [BookingController::class, 'store'])->name('bookings.store');
        Route::put('/bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');

        Route::get('/fuel-logs', [FuelLogController::class, 'index'])->name('fuel-logs.index');
        Route::post('/fuel-logs', [FuelLogController::class, 'store'])->name('fuel-logs.store');
        Route::delete('/fuel-logs/{fuelLog}', [FuelLogController::class, 'destroy'])->name('fuel-logs.destroy');

        Route::get('/service-schedules', [ServiceController::class, 'schedules'])->name('service-schedules.index');
        Route::post('/service-schedules', [ServiceController::class, 'storeSchedule'])->name('service-schedules.store');
        Route::delete('/service-schedules/{serviceSchedule}', [ServiceController::class, 'destroySchedule'])->name('service-schedules.destroy');

        Route::get('/service-logs', [ServiceController::class, 'logs'])->name('service-logs.index');
        Route::post('/service-logs', [ServiceController::class, 'storeLog'])->name('service-logs.store');
        Route::delete('/service-logs/{serviceLog}', [ServiceController::class, 'destroyLog'])->name('service-logs.destroy');

        Route::get('/locations', [LocationController::class, 'index'])->name('locations.index');
        Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
        Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

        Route::get('/rental-partners', [RentalPartnerController::class, 'index'])->name('rental-partners.index');
        Route::post('/rental-partners', [RentalPartnerController::class, 'store'])->name('rental-partners.store');
        Route::put('/rental-partners/{rentalPartner}', [RentalPartnerController::class, 'update'])->name('rental-partners.update');
        Route::delete('/rental-partners/{rentalPartner}', [RentalPartnerController::class, 'destroy'])->name('rental-partners.destroy');

        Route::get('/vehicles', [VehicleController::class, 'index'])->name('vehicles.index');
        Route::post('/vehicles', [VehicleController::class, 'store'])->name('vehicles.store');
        Route::put('/vehicles/{vehicle}', [VehicleController::class, 'update'])->name('vehicles.update');
        Route::delete('/vehicles/{vehicle}', [VehicleController::class, 'destroy'])->name('vehicles.destroy');

        Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
        Route::post('/drivers', [DriverController::class, 'store'])->name('drivers.store');
        Route::put('/drivers/{driver}', [DriverController::class, 'update'])->name('drivers.update');
        Route::delete('/drivers/{driver}', [DriverController::class, 'destroy'])->name('drivers.destroy');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::post('/employees', [EmployeeController::class, 'store'])->name('employees.store');
        Route::put('/employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
        Route::delete('/employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');

        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('role:approver')->group(function () {
        Route::get('/approvals', [BookingController::class, 'approvals'])->name('approvals.index');
        Route::post('/approvals/{booking}/decide', [BookingController::class, 'decide'])->name('approvals.decide');
    });
});

require __DIR__.'/settings.php';
