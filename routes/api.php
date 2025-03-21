<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SalesforceController;


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


// Step 1: Redirect to Salesforce for authentication
Route::get('/salesforce/login', [SalesforceController::class, 'redirectToSalesforce']);

// Step 2: Handle the callback and store access token
Route::get('/salesforce/callback', [SalesforceController::class, 'handleSalesforceCallback']);

// Step 3: Fetch contacts from Salesforce and save to Laravel
Route::get('/salesforce/import-contacts', [SalesforceController::class, 'importContacts'])->middleware('auth:api');


Route::post('test_ip', [App\Http\Controllers\AuditTrailController::class, 'store']); 

Route::post('/add-signer-pdf', [App\Http\Controllers\RequestController::class, 'addSignerPDF']);

//Route::get('auth/google', [AuthController::class, 'redirectToGoogle'])->middleware('web');
//Route::get('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);

Route::post('auth/google', [AuthController::class, 'handleGoogleCallback']);

Route::middleware(['web'])->group(function () {
    Route::get('auth/linkedin', [AuthController::class, 'redirectToLinkedIn']);
    Route::get('auth/linkedin/callback', [AuthController::class, 'handleLinkedInCallback']);
});

Route::post('/signup', [AuthController::class, 'register']);
Route::post('/signin', [AuthController::class, 'login']);
Route::post('send-forget-mail', [AuthController::class, 'sendForgetMail']);
Route::post('verify-otp', [AuthController::class, 'verifyOTP']);
Route::post('update-password', [AuthController::class, 'updatePassword']);
Route::post('/check_mail', [App\Http\Controllers\AuthController::class, 'checkMail']); 

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

//Route::post('/add_request_log', [App\Http\Controllers\RequestController::class, 'addRequestLog']);

Route::post('/verify_user_otp', [App\Http\Controllers\AuthController::class, 'otpVerification']); 
Route::post('/resend_user_otp', [App\Http\Controllers\AuthController::class, 'resendOtp']); 


//stripe testing
Route::post('confirm_payment', [App\Http\Controllers\SubscriptionController::class, 'confirmPayment']);
Route::get('get_payment', [App\Http\Controllers\SubscriptionController::class, 'retreivePayment']);
Route::get('get_payment_2', [App\Http\Controllers\SubscriptionController::class, 'getPayment']);
Route::get('complete_payment', [App\Http\Controllers\SubscriptionController::class, 'completePayment']);

Route::post('create_payment_intent_2', [App\Http\Controllers\SubscriptionController::class, 'createPaymentIntent']);

Route::post('testjson', [App\Http\Controllers\SubscriptionController::class, 'testJson']);
Route::get('attach_payment', [App\Http\Controllers\SubscriptionController::class, 'attachPaymentMethod']);

//ending stripe testing

Route::post('/decline_request', [App\Http\Controllers\RequestController::class, 'declineRequest']);

Route::get('/signature_certificate', [App\Http\Controllers\AuditTrailController::class, 'getSignCertificate']); 

Route::middleware('auth:api')->group(function () {

    
    //profile management
    Route::get('/profile_data', [App\Http\Controllers\ProfileManagementController::class, 'profileData']); 
    Route::post('/update_profile_data', [App\Http\Controllers\ProfileManagementController::class, 'updateProfileData']); 
    Route::post('change-password', [AuthController::class, 'changePassword']);
    Route::post('change-profile-img', [App\Http\Controllers\ProfileManagementController::class, 'changeProfileImg']);
    Route::post('change-company-name', [App\Http\Controllers\ProfileManagementController::class, 'changeCompanyName']);
    Route::post('change-logo-img', [App\Http\Controllers\ProfileManagementController::class, 'changeLogoImg']);
    Route::post('change-fav-img', [App\Http\Controllers\ProfileManagementController::class, 'changeFavImg']);
    Route::get('logout', [AuthController::class, 'logout']);

    Route::get('/pdf_images/{imageName}', [App\Http\Controllers\ImageController::class, 'show']);

    Route::prefix('user')->middleware(['role:2'])->group(function () {

        Route::get('auth_data', [App\Http\Controllers\AuthController::class, 'getAuthData']);

        //Audit Trail module

        Route::get('audit_trail', [App\Http\Controllers\AuditTrailController::class, 'index']); 

        //ending Audit Trail Module

        Route::get('/check_plan', [App\Http\Controllers\SubscriptionController::class, 'checkLimitStatus']); 

        //CONTACTS Module
        Route::resource('/contacts', App\Http\Controllers\ContactController::class);
        Route::post('/bulk_import_contacts', [App\Http\Controllers\ContactController::class, 'bulkImport']); 
        Route::post('/bulk_delete_contacts', [App\Http\Controllers\ContactController::class, 'bulkDelete']);
        Route::post('/update_contact_phones', [App\Http\Controllers\ContactController::class, 'updatePhones']);

        Route::post('/convert-to-png', [App\Http\Controllers\ManagePDFController::class, 'convertToPng']);
        Route::post('/add_image_element', [App\Http\Controllers\ManagePDFController::class, 'addImageElement']);

        //User Request Module

        Route::post('/duplicate-request', [App\Http\Controllers\RequestController::class, 'duplicatePost']);

        Route::resource('/user_request', App\Http\Controllers\RequestController::class);
        
        Route::post('/upload-thumbnail', [App\Http\Controllers\RequestController::class, 'uploadThumbnail']);
        
        Route::get('/inbox', [App\Http\Controllers\RequestController::class, 'inbox']);
        Route::get('/get_file/{id}', [App\Http\Controllers\RequestController::class, 'getFileBase']);
        Route::post('/create_request_draft', [App\Http\Controllers\RequestController::class, 'createDraft']);
        Route::post('/store_request_fields', [App\Http\Controllers\RequestController::class, 'fieldsDraft']);
        Route::post('/change_request_status', [App\Http\Controllers\RequestController::class, 'changeRequestStatus']);
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

        //reminder
        Route::post('/send_reminder', [App\Http\Controllers\RequestController::class, 'sendReminder']);
        //ending reminder

        //subscription api's
        Route::resource('/subscription', App\Http\Controllers\SubscriptionController::class);
        Route::post('/cancel_subscription', [App\Http\Controllers\SubscriptionController::class, 'cancelSubscription']);
        Route::post('charge_payment', [App\Http\Controllers\SubscriptionController::class, 'charge']);
        Route::post('create_payment_intent', [App\Http\Controllers\SubscriptionController::class, 'createPaymentIntent']);
        //ending subscription api's

        Route::resource('/transaction', App\Http\Controllers\TransactionController::class);

        Route::get('get_billing_info', [App\Http\Controllers\BillingInfoController::class, 'index']);
        Route::post('update_billing_info', [App\Http\Controllers\BillingInfoController::class, 'update']);

        Route::resource('/payment_method', App\Http\Controllers\PaymentMethodController::class);

        //global settings
        Route::post('/use_company', [App\Http\Controllers\GlobalSettingController::class, 'useCompany']);

        //team members
        Route::resource('/team', App\Http\Controllers\TeamController::class);
        Route::post('/join_team', [App\Http\Controllers\TeamController::class, 'joinTeam']);
        Route::get('/join_requests', [App\Http\Controllers\TeamController::class, 'joinRequests']);
        Route::post('/leave_team', [App\Http\Controllers\TeamController::class, 'leaveTeam']);
        Route::post('/reject_team', [App\Http\Controllers\TeamController::class, 'rejectTeam']);

        //user global setting
        Route::resource('/user_global_setting', App\Http\Controllers\UserGlobalSettingController::class);

        //support email
        //Route::post('/send_support_mail', [App\Http\Controllers\UserGlobalSettingController::class, 'supportMail']);

        Route::resource('/send_support_mail', App\Http\Controllers\SupportMailController::class);


    } );

    //********************************** */
    ///////////////////////////////////////
    ///ADMIN API ROUTES////////////////
    ///////////////////////////////////////
    //******************************* */

    Route::prefix('admin')->middleware(['role:1'])->group(function () {

        Route::resource('/admin_user_request', App\Http\Controllers\RequestController::class);
        Route::resource('/plan', App\Http\Controllers\PlanController::class);

        Route::resource('/user_management', App\Http\Controllers\UserManagementController::class);
        Route::post('/change_plan', [App\Http\Controllers\UserManagementController::class, 'changePlan']);

        Route::resource('/tickets', App\Http\Controllers\SupportMailController::class);
        Route::post('/change_ticket_status', [App\Http\Controllers\SupportMailController::class, 'changeTicketStatus']);

        Route::get('/admin_dashboard', [App\Http\Controllers\DashboardController::class, 'stat']);

    } );


});
