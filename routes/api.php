<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/signin', [AuthController::class, 'login']);
Route::post('send-forget-mail', [AuthController::class, 'sendForgetMail']);
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);

Route::post('/test-pdf', [App\Http\Controllers\ManagePDFController::class, 'testConvert']); 


Route::middleware('auth:api')->group(function () {

    //profile management
    Route::get('/profile_data', [App\Http\Controllers\ProfileManagementController::class, 'profileData']); 
    Route::post('/update_profile_data', [App\Http\Controllers\ProfileManagementController::class, 'updateProfileData']); 
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('change-profile-img', [App\Http\Controllers\ProfileManagementController::class, 'changeProfileImg']);

    Route::get('logout', [AuthController::class, 'logout']);

    Route::get('/pdf_images/{imageName}', [App\Http\Controllers\ImageController::class, 'show']);

    Route::prefix('user')->middleware(['role:2'])->group(function () {

        //CONTACTS Module
        Route::resource('/contacts', App\Http\Controllers\ContactController::class);
        Route::post('/bulk_import_contacts', [App\Http\Controllers\ContactController::class, 'bulkImport']); 
        Route::post('/bulk_delete_contacts', [App\Http\Controllers\ContactController::class, 'bulkDelete']);

        Route::post('/convert-to-png', [App\Http\Controllers\ManagePDFController::class, 'convertToPng']);
        Route::post('/add_image_element', [App\Http\Controllers\ManagePDFController::class, 'addImageElement']);

        //User Request Module
        Route::resource('/user_request', App\Http\Controllers\RequestController::class);
        Route::post('/fetch_request', [App\Http\Controllers\RequestController::class, 'fetchRequest']);

    } );


});
