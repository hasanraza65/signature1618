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
Route::post('/fetch_request', [App\Http\Controllers\RequestController::class, 'fetchRequest']);
Route::post('/approver_fetch_request', [App\Http\Controllers\RequestController::class, 'approverFetchRequest']);
Route::post('/approve_request', [App\Http\Controllers\RequestController::class, 'approveRequest']);
Route::post('/reject_request', [App\Http\Controllers\RequestController::class, 'rejectRequest']);

Route::post('/answer_request', [App\Http\Controllers\RequestController::class, 'answerRequest']);

Route::post('/send_otp', [App\Http\Controllers\RequestController::class, 'sendOTP']);
Route::post('/verify_otp', [App\Http\Controllers\RequestController::class, 'verifyOTP']);

Route::get('/test', [App\Http\Controllers\RequestController::class, 'testLaravel']); 

Route::get('/otp_sms', [App\Http\Controllers\RequestController::class, 'sendSMSOTP']); 

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
        Route::post('/update_contact_phones', [App\Http\Controllers\ContactController::class, 'updatePhones']);

        Route::post('/convert-to-png', [App\Http\Controllers\ManagePDFController::class, 'convertToPng']);
        Route::post('/add_image_element', [App\Http\Controllers\ManagePDFController::class, 'addImageElement']);

        //User Request Module
        Route::resource('/user_request', App\Http\Controllers\RequestController::class);
        Route::post('/create_request_draft', [App\Http\Controllers\RequestController::class, 'createDraft']);
        Route::post('/store_request_fields', [App\Http\Controllers\RequestController::class, 'fieldsDraft']);
        //request trash module
        Route::post('/add_to_trash', [App\Http\Controllers\RequestController::class, 'addToTrash']);
        Route::post('/remove_from_trash', [App\Http\Controllers\RequestController::class, 'removeFromTrash']);
        Route::get('/all_trash_items', [App\Http\Controllers\RequestController::class, 'allTrashItems']);
        //ending req trash module

        //request bookmark module
        Route::post('/add_to_bookmarks', [App\Http\Controllers\RequestController::class, 'addToBookmarks']);
        Route::post('/remove_from_bookmarks', [App\Http\Controllers\RequestController::class, 'removeFromBookmarks']);
        Route::get('/all_bookmarked_items', [App\Http\Controllers\RequestController::class, 'allBookmarkedItems']);
        //ending req bookmark module

        

    } );


});
