<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test', function () {
    return view('test');
});
Route::get('/test2', function () {
    return view('test2');
});

Route::get('/test3', function () {
    return view('test3');
});

Route::get('/phpinfo', function () {
    phpinfo();
});

Route::get('/dump-autoload', function () {
    Artisan::call('optimize');
    
    return 'Autoload optimized!';
});


Route::get('/charge', function () {
    return view('charge');
});


Route::get('/generate_sign/{name}', [App\Http\Controllers\ProfileManagementController::class, 'generateSignature']); 

Route::middleware('auth')->get('/pdf_images/{imageName}', [App\Http\Controllers\ImageController::class, 'show']);

Route::get('/test-mail', [App\Http\Controllers\RequestController::class, 'sendMail']); 

//Route::get('/test-sms', [App\Http\Controllers\RequestController::class, 'sendSMSOTP']); 

Route::get('/otp_sms', [App\Http\Controllers\RequestController::class, 'sendSMSOTP']); 


Route::get('/create-client', [App\Http\Controllers\RequestController::class, 'createPhoneNumber']);

Route::get('/run-scheduler', function () {
    // Run the scheduler
    Artisan::call('schedule:run');

    // Return a message indicating the scheduler has been run
    return 'Scheduler has been executed.';
});

Route::get('/clear-cache', function () {
    // Clear application cache
    Artisan::call('cache:clear');
    
    // Clear configuration cache
    Artisan::call('config:clear');

    // Clear route cache
    Artisan::call('route:clear');

    // Clear view cache
    Artisan::call('view:clear');

    // Clear compiled class cache
    Artisan::call('clear-compiled');

    // Clear optimized class loader
    Artisan::call('optimize:clear');
    
    return 'Cache cleared!';
});