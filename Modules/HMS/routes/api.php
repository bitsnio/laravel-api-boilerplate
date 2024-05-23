<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\HMS\Core\PermanentDelete;
use Modules\HMS\App\Http\Controllers\API\PropertyBillingController;
use Modules\HMS\App\Http\Controllers\API\AdditionalServicesController;
use Modules\HMS\App\Http\Controllers\API\AdvancePaymentController;
use Modules\HMS\App\Http\Controllers\API\AssignedAdditionalServicesController;
use Modules\HMS\App\Http\Controllers\API\AssignSubModuleController;
use Modules\HMS\App\Http\Controllers\API\BasisOfApplicationController;
use Modules\HMS\App\Http\Controllers\API\BillingTimeRuleController;
use Modules\HMS\App\Http\Controllers\API\CheckedInMemberController;
use Modules\HMS\App\Http\Controllers\API\CheckInController;
use Modules\HMS\App\Http\Controllers\API\PropertyController;
use Modules\HMS\App\Http\Controllers\API\SubModuleController;
use Modules\HMS\App\Http\Controllers\API\MainModuleController;
use Modules\HMS\App\Http\Controllers\API\RoomListController;
use Modules\HMS\App\Http\Controllers\API\RoomTypeController;
use Modules\HMS\App\Http\Controllers\API\UserModulesController;
use Modules\HMS\App\Http\Controllers\API\CheckOutController;
use Modules\HMS\App\Http\Controllers\API\PayableController;
use Modules\HMS\App\Http\Controllers\API\PropertySettingController;
use Modules\HMS\App\Http\Controllers\API\UserRoleController;
use Modules\HMS\App\Http\Controllers\API\AssignedBillingTimeRulesController;
use Modules\HMS\App\Http\Controllers\API\CompanyController;
use Modules\HMS\App\Http\Controllers\API\ContractController;
use Modules\HMS\App\Http\Controllers\API\ExpenseController;
use Modules\HMS\App\Http\Controllers\API\ExportDataController;
use Modules\HMS\App\Http\Controllers\API\FamilyGeneratedBillController;
use Modules\HMS\App\Http\Controllers\API\fileReaderController;
use Modules\HMS\App\Http\Controllers\API\GeneratedBillsController;
use Modules\HMS\App\Http\Controllers\API\PaymentController;
use Modules\HMS\App\Http\Controllers\API\PropertyAdditionalServicesRelationController;
use Modules\HMS\App\Http\Controllers\API\PropertyServiceRulesController;
use Modules\HMS\App\Http\Controllers\API\PropertyServicesController;
use Modules\HMS\App\Http\Controllers\API\ReceiptController;
use Modules\HMS\App\Http\Controllers\Global\Autocomplete;

/*
    |--------------------------------------------------------------------------
    | API Routes
    |--------------------------------------------------------------------------
    |
    | Here is where you can register API routes for your application. These
    | routes are loaded by the RouteServiceProvider within a group which
    | is assigned the "api" middleware group. Enjoy building your API!
    |
*/

Route::middleware(['auth:sanctum'])->prefix('v1')->name('api.')->group(function () {
    Route::get('hms', fn (Request $request) => $request->user())->name('hms');
});
Route::apiResource('/roles', UserRoleController::class);
Route::apiResource('/modules', MainModuleController::class);
Route::apiResource('/sub-modules', SubModuleController::class);
Route::apiResource('/companies', CompanyController::class);
Route::apiResource('/properties', PropertyController::class);
Route::apiResource('/rooms', RoomListController::class);
Route::apiResource('/room-types', RoomTypeController::class);
Route::apiResource('/room-lists', RoomListController::class);
Route::apiResource('/additional-services', AdditionalServicesController::class);
Route::apiResource('/check-ins', CheckInController::class);
Route::apiResource('/checked-in-members', CheckedInMemberController::class);
Route::apiResource('/check-outs', CheckOutController::class);
Route::get('/auth/token', [UserModulesController::class, 'usersModules']);
Route::get('/autocomplete', [Autocomplete::class,'index']);
Route::get('/autocomplete_without_id', [Autocomplete::class,'autocompleteWithoutId']);
Route::get('/assign-sub-modules', [AssignSubModuleController::class, 'getSubModules']);
Route::apiResource('/assigned-additional-services', AssignedAdditionalServicesController::class);
Route::apiResource('/payables', PayableController::class);
Route::apiResource('/receivables', PropertyBillingController::class);
Route::apiResource('/basis-of-applications', BasisOfApplicationController::class);
Route::apiResource('/billing-time-rules', BillingTimeRuleController::class);
Route::apiResource('/property-settings', PropertySettingController::class);
Route::apiResource('/assigned-billing-time-rules', AssignedBillingTimeRulesController::class);
Route::apiResource('/property-related-services', PropertyAdditionalServicesRelationController::class);
Route::apiResource('/generated-bills', GeneratedBillsController::class);
Route::apiResource('/upload-files', fileReaderController::class);
Route::apiResource('/property-services', PropertyServicesController::class);
Route::apiResource('/property-service-rules', PropertyServiceRulesController::class);
Route::post('/update-payable-status', [PayableController::class, 'updatePaymentStatus']);
Route::post('/update-receivable-status', [PropertyBillingController::class, 'updatePaymentStatus']);
// Route::post('/family-bill-receivables', [PropertyBillingController::class, 'familyBill']);
Route::post('/merged-bills', [PropertyBillingController::class, 'mergeBill']);
Route::post('/update-guest-rooms', [CheckInController::class, 'updateGuestRooms']);
Route::post('/dashboard', [PropertyController::class, 'dashboardData']);
Route::post('/country-checkins', [CheckInController::class, 'countryCheckin']);
Route::post('/export-data', [ExportDataController::class, 'exportData']);
Route::apiResource('/store-bills', FamilyGeneratedBillController::class);
Route::post('/show-bills', [FamilyGeneratedBillController::class, 'showBills']);
Route::post('/delete-checkins', [CheckInController::class, 'deleteCheckins']);
Route::post('/reverse-checkouts', [PermanentDelete::class, 'deleteCheckout']);
Route::post('/copy-services', [PermanentDelete::class, 'assignCurrentServices']);
Route::post('/family-rooms', [RoomListController::class, 'familyRooms']);
Route::post('/checkout-files', [fileReaderController::class, 'fileCheckouts']);
Route::apiResource('/receipts', ReceiptController::class);
Route::apiResource('/payments', PaymentController::class);
Route::apiResource('/advance-payments', AdvancePaymentController::class);
Route::apiResource('/contracts', ContractController::class);
Route::post('/receipts-invoices', [ReceiptController::class, 'getInvoiceReceipts']);
Route::apiResource('/expenses', ExpenseController::class);
Route::post('delete-merge-checkins', [PermanentDelete::class, 'deleteMergeCheckins']);
Route::post('reports', [PropertyBillingController::class, 'generateReport']);
Route::post('/db-query', [PermanentDelete::class, 'query_database']);