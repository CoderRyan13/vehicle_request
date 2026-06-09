<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RequestVehicleCtrl;
use App\Http\Controllers\SupervisorCtrl;
use App\Http\Controllers\VehicleCtrl;
use App\Http\Controllers\DriverCtrl;
use App\Http\Controllers\AuthCtrl;

 // Vehicle Request Form
 Route::view('/', 'index');

 // Request Vehicle
 Route::post('/request_vehicle', [RequestVehicleCtrl::class, 'request_vehicle']);

 // Get Drivers 
 Route::post('/get_all_drivers', [DriverCtrl::class, 'get_all_drivers']);

 // Get Supervisors 
 Route::post('/get_all_supervisors', [SupervisorCtrl::class, 'get_all_supervisors']);

 Route::post('/get_approved', [RequestVehicleCtrl::class, 'get_approved']);

Route::middleware('auth')->group(function(){
    // Views
    Route::view('/admin', 'admin');
    Route::view('/history', 'history');
    Route::view('/vehicle', 'vehicle');
    Route::view('/driver', 'driver');
    Route::view('/supervisor', 'supervisor');

    // Request Vehicle
    Route::post('/get_request', [RequestVehicleCtrl::class, 'get_request']);
    Route::post('/get_requests', [RequestVehicleCtrl::class, 'get_requests']);
    
    Route::post('/get_denied', [RequestVehicleCtrl::class, 'get_denied']);
    Route::post('/get_all_requests', [RequestVehicleCtrl::class, 'get_all_requests']);
    Route::post('/approve_vehicle', [RequestVehicleCtrl::class, 'approve_vehicle']);
    Route::post('/deny_vehicle', [RequestVehicleCtrl::class, 'deny_vehicle']);
    Route::post('/edit_approved', [RequestVehicleCtrl::class, 'edit_approved']);

    // Practice Calendar
    Route::view('/calendar', 'calendar');

    // Vehicle
    Route::post('/get_all_vehicles', [VehicleCtrl::class, 'get_all_vehicles']);
    Route::post('/add_vehicle', [VehicleCtrl::class, 'add_vehicle']);
    Route::post('/remove_vehicle', [VehicleCtrl::class, 'remove_vehicle']);

    // Driver
    Route::post('/add_driver', [DriverCtrl::class, 'add_driver']);
    Route::post('/remove_driver', [DriverCtrl::class, 'remove_driver']);

    // Supervisor
    Route::post('/add_supervisor', [SupervisorCtrl::class, 'add_supervisor']);
    Route::post('/remove_supervisor', [SupervisorCtrl::class, 'remove_supervisor']);

    // Auth
    Route::post('/logout', [AuthCtrl::class, 'logout']);
});

Route::middleware('guest')->group(function(){
    // Auth
    // Route::view('/register', 'auth.register')->name('register');
    // Route::post('/register', [AuthCtrl::class, 'register']);

    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [AuthCtrl::class, 'login']);
});
