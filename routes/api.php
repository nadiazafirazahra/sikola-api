<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;
use App\Http\Controllers\InnerJoinController;
use App\Http\Controllers\Api\LemburController;
use App\Http\Controllers\Api\SpklApiController;
use App\Http\Controllers\CombinedResultsController;
use App\Http\Controllers\Api\MWeekdayHolidayController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(Authenticate::using('sanctum'));

Route::apiResource('/posts', App\Http\Controllers\Api\PostController::class);
Route::apiResource('/users', App\Http\Controllers\Api\UserController::class);
Route::apiResource('/break-ots', App\Http\Controllers\Api\MBreakOtController::class);
Route::apiResource('/categories', App\Http\Controllers\Api\MCategoryController::class);
Route::apiResource('/departments', App\Http\Controllers\Api\MDepartmentController::class);
Route::apiResource('/directors', App\Http\Controllers\Api\MDirectorController::class);
Route::apiResource('/divisions', App\Http\Controllers\Api\MDivisionController::class);
Route::apiResource('/employees', App\Http\Controllers\Api\MEmployeeController::class);
Route::apiResource('/holidays', App\Http\Controllers\Api\MHolidayController::class);
Route::apiResource('/lines', App\Http\Controllers\Api\MLineController::class);
Route::apiResource('/occupations', App\Http\Controllers\Api\MOccupationController::class);
Route::apiResource('/open-accesses', App\Http\Controllers\Api\MOpenAccessController::class);
Route::apiResource('/over-request-historieses', App\Http\Controllers\Api\MOverRequestHistoriesController::class);
Route::apiResource('/quota-adds', App\Http\Controllers\Api\MQuotaAddController::class);
Route::apiResource('/quota-departments', App\Http\Controllers\Api\MQuotaDepartmentController::class);
Route::apiResource('/quota-reals', App\Http\Controllers\Api\MQuotaRealController::class);
Route::apiResource('/quota-requests', App\Http\Controllers\Api\MQuotaRequestController::class);
Route::apiResource('/quota-useds', App\Http\Controllers\Api\MQuotaUsedController::class);
Route::apiResource('/sections', App\Http\Controllers\Api\MSectionController::class);
Route::apiResource('/shifts', App\Http\Controllers\Api\MShiftController::class);
Route::apiResource('/spesial-limits', App\Http\Controllers\Api\MSpesialLimitsController::class);
Route::apiResource('/spesial-limit-histories', App\Http\Controllers\Api\MSpesialLimitHistoriesController::class);
Route::apiResource('/sub-sections', App\Http\Controllers\Api\MSubSectionController::class);
Route::apiResource('/transports', App\Http\Controllers\Api\MTransportController::class);
Route::apiResource('/weekday-holidays', MWeekdayHolidayController::class);
Route::apiResource('/quota-add-itds', App\Http\Controllers\Api\QuotaAddItdController::class);
Route::apiResource('/approved-limit-spesials', App\Http\Controllers\Api\TApprovedLimitSpesialController::class);
Route::apiResource('/approved-limit-spesial-logs', App\Http\Controllers\Api\TApprovedLimitSpesialLogController::class);
Route::apiResource('/quota-transactions', App\Http\Controllers\Api\TQuotaTransactionController::class);
Route::apiResource('/spkls', App\Http\Controllers\Api\TSpklController::class);
Route::apiResource('/spkl-backups', App\Http\Controllers\Api\TSpklBackupController::class);
Route::apiResource('/spkl-details', App\Http\Controllers\Api\TSpklDetailController::class);
Route::apiResource('/spkl-detail-backups', App\Http\Controllers\Api\TSpklDetailBackupController::class);
Route::apiResource('/lemburs', App\Http\Controllers\Api\LemburController::class);
// Route::apiResource('/show-query', App\Http\Controllers\Api\InnerJoinController::class);

// ********************************************************************************************************************************************//
// ********************************************************************************************************************************************//

    Route::get('/lemburs/{tanggal}', [LemburController::class, 'index']);
    Route::post('/lembur', [LemburController::class, 'store']);
    Route::get('/lembur/{id}', [LemburController::class, 'show']);
    Route::put('/lembur/{id}', [LemburController::class, 'update']);
    Route::delete('/lembur/{id}', [LemburController::class, 'destroy']);
    Route::get('/lembur/date/{date}', [LemburController::class, 'getByDate']);
    Route::get('/lemburs/details', [LemburController::class, 'details']);
    Route::get('/lemburs/search', [LemburController::class, 'search']);


    Route::get('/joined-data', [InnerJoinController::class, 'getJoinedData']);
    Route::get('/joined-data/{start_date}', [InnerJoinController::class, 'getJoinedData']);


    Route::get('lembur/tanggal/{tanggal}', [LemburController::class, 'getByDate']);
    Route::get('lembur/npk/{npk}', [LemburController::class, 'getByNpk']);


Route::group(['controller' => 'App\Http\Controllers\Api'], function () {
    Route::get('/user-view', [App\Http\Controllers\Api\MasterApiController::class, 'user_view']);
    Route::post('/user-create', [App\Http\Controllers\Api\MasterApiController::class, 'user_create']);
    Route::get('/user-reset-pwd/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_reset_pwd']);
    Route::get('/user-reset', [App\Http\Controllers\Api\MasterApiController::class, 'reset_pwd']);
    Route::get('/user-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_delete']);
    Route::get('/user-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_update']);
    Route::post('/user-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'user_update_save']);
    Route::post('/user-save-edit-password', [App\Http\Controllers\Api\MasterApiController::class, 'user_save_edit_password']);

     //hotfix-2.3.5, by Merio, 20161118, untuk manajemen limit open or closed spkl terlambat
     Route::get('/user-ot-open/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_ot_open_par']);
     Route::post('/user-ot-open-save', [App\Http\Controllers\Api\MasterApiController::class, 'ser_ot_open']);

     Route::get('/user-ot-closed/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_ot_closed_par']);
     Route::post('/user-ot-closed-save', [App\Http\Controllers\Api\MasterApiController::class, 'user_ot_closed']);

     Route::get('/user-non_active/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'user_non_active']);
     Route::get('/user-active/{id}', [App\Http\Controllers\Api\MasterApiController::class,'user_active']);

     //hotfix-1.9.11, by Merio, 20160824, user profile
     Route::get('/user-profile', [App\Http\Controllers\Api\MasterApiController::class, 'user_profile']);


    //**********************************v1.5.21 by : Merio Aji, 20160525, Crud for Master Master Break**********************************//
    Route::get('/m_break_ot-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_break_ot_view']);
    Route::post('/m_break_ot-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_break_ot_create']);
    Route::get('/m_break_ot-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_break_ot_delete']);
    Route::get('/m_break_ot-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_break_ot_update']);
    Route::post('/m_break_ot-update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_break_ot_update_save']);


    //***************************************v1.0 by Merio, 20151230, Crud for Master Department***************************************//
     Route::get('/m_department-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_view']);
     Route::post('/m_department-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_import']);
     Route::post('/m_department-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_create']);
     Route::get('/m_department-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_delete']);
     Route::get('/m_department-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_update']);
     Route::post('/m_department-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'm_department_update_save']);

     Route::get('/member-department', [App\Http\Controllers\Api\MasterApiController::class, 'member_department']);


    //**************************************v1.0 by : Merio Aji, 20160418, Crud for Master Directors***********************************//
    Route::get('/m_director-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_view']);
    Route::post('/m_director-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_import']);
    Route::post('/m_director-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_create']);
    Route::get('/m_director-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_delete']);
    Route::get('/m_director-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_update']);
    Route::post('/m_director-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_import']);
    Route::post('/m_director-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'm_director_update_save']);


    //**************************************v1.0 by : Merio Aji, 20160102, Crud for Master Categories***********************************//
     Route::get('/m_category-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_view']);
     Route::post('/m_category-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_import']);
     Route::post('/m_category-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_create']);
     Route::get('/m_category-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_delete']);
     Route::get('/m_category-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_update']);
     Route::post('/m_category-update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_category_update_save']);


    //***************************************v1.0 by : Merio Aji, 20151221, Crud for Master Division************************************//
     Route::get('/m_division-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_view']);
     Route::post('/m_division-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_import']);
     Route::post('/m_division-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_create']);
     Route::get('/m_division-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_delete']);
     Route::get('/m_division-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_update']);
     Route::post('/m_division-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'm_division_update_save']);


    //***************************************v1.0 by : Merio Aji, 20160102, Crud for Master Employee************************************//
    Route::get('/m_employee-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_view']);
    Route::post('/m_employee-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_import']);
    Route::post('/m_employee-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_create']);
    Route::get('/m_employee-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_delete']);
    Route::get('/m_employee-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_update']);
    Route::post('/m_employee-update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_update_save']);
    Route::get('/m_employee-non_active/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_non_active']);
    Route::get('/m_employee-active/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_employee_active']);


    //*********************************************dev1.7 by andre, 20160623, Crud holiday*********************************************//
    Route::get('/m_holiday-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_holiday_view']);
    Route::post('/holiday-upload', [App\Http\Controllers\Api\MasterApiController::class, 'holiday_upload']);
    Route::post('/m_holiday-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_holiday_create']);
    Route::get('/m_holiday-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_holiday_delete']);


     //****************************************v1.0 by : Ario Rizki, 20170807, new line masatery****************************************//
    Route::get('/m_line-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_view']);
    Route::post('/m_line-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_create']);
    Route::get('/m_line-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_update']);
    Route::post('/m_line-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_import']);
    Route::post('/m_line-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_update_save']);
    Route::get('/m_line-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_line_delete']);


    //************************************v1.0 by : Merio Aji, 20160104, Crud for Master Occupations************************************//
    Route::get('/m_occupation-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_view']);
    Route::post('/m_occupation-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_import']);
    Route::post('/m_occupation-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_create']);
    Route::get('/m_occupation-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_delete']);
    Route::get('/m_occupation-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_update']);
    Route::post('/m_occupation-update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_occupation_update_save']);


    //*************hotfix-2.3.2, by Merio, 20161004, menambahkan fungsi untuk update limit open access overtime late oleh HRD*************//
    Route::get('/open-access-ot-late', [App\Http\Controllers\Api\SpklApiController::class, 'open_access_ot_late']);
    Route::get('/open-access-ot-late-update/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'open_access_ot_late_update']);
    Route::post('/open-access-ot-late-save', [App\Http\Controllers\Api\SpklApiController::class, 'open_access_ot_late_save']);


    //************************************************************************************************************************************//
    // m_over_request_histories


//***************************************************************QUOTA_ADD****************************************************************//
    Route::get('quota-mp-spv', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_spv']);
    Route::get('quota-mp-kadept', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_kadept']);
    Route::get('quota-mp-gm', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_gm']);
    Route::get('quota-mp-filter', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_filter']);
    Route::get('quota-mp-hr', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_hr']);
    Route::post('quota-mp-hr-proses', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_hr_proses']);

    //hotfix-2.0.1, by Merio, 20160829, quota overtime daily
    Route::get('quota-mp-daily-gm', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_daily_gm']); // dev-2.2, Ferry, 20160911
    Route::get('quota-mp-daily-kadept', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_daily_kadept']);
    Route::post('quota-mp-daily-kadept', [App\Http\Controllers\Api\MasterApiController::class, 'upload_line']);
    Route::get('quota-mp-daily-spv', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_daily_spv']);

    Route::get('quota-mp-daily-ldr', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_daily_ldr']);
    Route::get('quota-mp-daily-hr', [App\Http\Controllers\Api\MasterApiController::class, 'quota_mp_daily_hr']); // hotfix-2.3.5, by Merio, 20161119

    Route::get('quota-approve-kadept-add', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_kadept_add']);
    Route::post('quota-approve-kadept-save', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_kadept_save']);
    Route::get('quota-approve-kadept-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_kadept_delete']);
    Route::get('quota-approve-gm-view', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_gm_view']);
    Route::get('/quota-approve-gm-action/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_gm_action_view']);
    Route::post('/quota-approve-gm-action-save', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_gm_action_save']);
    Route::get('/quota-approve-gm-action-reject/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_approve_gm_action_reject']);

    // special limit change history
    Route::get('/quota-gm-mp-allowed-histories', [App\Http\Controllers\Api\MasterApiController::class, 'mp_allowed_history']);

    Route::post('/quota-request-view', [App\Http\Controllers\Api\MasterApiController::class, 'store_upload']);

    Route::get('/user-view2', [App\Http\Controllers\Api\MasterApiController::class, 'quota_spesial_limit']);

    //*********************************************dev-3.0, by Merio, SIKOLA v3.0 - Quota Department Management*********************************************//
    Route::get('/quota/department/view', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_view']);
    Route::post('/quota/department/create', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_create']);
    Route::get('/quota/department/delete/{id}', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_delete']);
    Route::get('/quota/department/revise/hr/{id}', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_revise_hr']);
    Route::get('/quota/department/overview/gm', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_overview_gm']);
    Route::post('/quota/department/revise', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_department_revise']);
    Route::get('/quota/dept/overview', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_dept_overview']);
    Route::get('/quota/normalisasi', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_normalisasi']);
    Route::post('/quota/normalisasi/proses', [App\Http\Controllers\Api\QuotaApiController::class, 'quota_normalisasi_proses']);


    //******************************************************//v1.6 by Andre, 20160502 Crud Quota Real******************************************************//
    Route::get('/quota_original/view', [App\Http\Controllers\Api\MasterApiController::class, 'quota_real_view']);
    Route::post('/quota_original/create', [App\Http\Controllers\Api\MasterApiController::class, 'quota_real_create']);
    Route::get('/quota_original/update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_real_update']);
    Route::get('/quota_original/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_real_delete']);
    Route::post('/quota_original/update/save', [App\Http\Controllers\Api\MasterApiController::class, 'quota_real_update_save']);


    //*******************************************hotfix-1.9.4, by Merio Aji, 20160818, management quota request*******************************************//
     Route::get('/quota/request/view', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_view']);
     Route::get('/quota/request/history/approve', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_history_approve']);
     Route::get('/quota/request/history/rejected', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_history_rejected']);
     Route::get('/quota/request/temp', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_temp']);
     Route::get('/quota/request/import', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_import']);

     // Route::post('quota/request/view', 'MasterController@quota_request_import');
     Route::post('/quota/request/generate', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_generate']);
     Route::get('/quota/request/gm/view', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_view']);
     Route::get('/quota/request/gm/reject/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_reject']);
     Route::get('/quota/request/gm/approve/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_approve']);
     Route::get('/quota/request/gm/detail/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_detail']);
     Route::get('/quota/request/gm/reject/detail/{id}/{id2}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_reject_detail']);
     Route::get('/quota/request/gm/approve/detail/{id}/{id2}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_approve_detail']);
     Route::get('/quota/request/detail/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_detail']);
     Route::get('/quota/request/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_delete']);
     Route::get('/quota/request/cancel/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_cancel']);
     Route::get('/quota/request/destroy/all', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_destroy_all']);

     Route::get('/quota/request/gm/history/approve', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_history_approve']);
     Route::get('/quota/request/gm/history/rejected', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_gm_history_rejected']);

     Route::get('/quota/request/hr/view', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_view']);
     Route::get('/quota/request/hr/detail/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_detail']);
     Route::get('/quota/request/hr/approve/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_approve']);
     Route::get('/quota/request/hr/reject/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_reject']);
     Route::get('/quota/request/hr/approve/detail/{id}/{id2}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_approve_detail']);
     Route::get('/quota/request/hr/reject/detail/{id}/{id2}', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_reject_detail']);
     Route::get('/quota/request/hr/history/approve', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_history_approve']);
     Route::get('quota/request/hr/history/rejected', [App\Http\Controllers\Api\MasterApiController::class, 'quota_request_hr_history_rejected']);
     Route::get('/data-graph', [App\Http\Controllers\Api\HomeApiController::class, 'data_graph']);


    //******************************************************v1.6 by Andre, 20160502 Crud Quota Used******************************************************//
    Route::get('/m_quota_approved/view', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_used_view']);
    Route::post('/m_quota_approved/create', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_used_create']);
    Route::get('/m_quota_approved/update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_used_update']);
    Route::get('/m_quota_approved/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_used_delete']);
    Route::post('/m_quota_approved/update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_used_update_save']);


    //**************************************************v1.0 by Merio, 20151230, Crud for Master Section**************************************************//
    Route::get('/m_section-view', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_view']);
    Route::post('/m_section-import', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_import']);
    Route::post('/m_section-create', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_create']);
    Route::get('/m_section-delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_delete']);
    Route::get('/m_section-update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_update']);
    Route::post('/m_section-update-save', [App\Http\Controllers\Api\MasterApiController::class, 'm_section_update_save']);

    Route::get('quota/section/daily', [App\Http\Controllers\Api\MasterApiController::class, 'quota_section_daily']);

    //*******************************************************************SHIFT*******************************************************************//
    Route::get('/report/rekap/makan/view', [App\Http\Controllers\Api\ReportApiController::class, 'rekap_makan_view']);
    Route::post('/report/rekap/makan/post', [App\Http\Controllers\Api\ReportApiController::class, 'rekap_makan_post']);

    Route::get('/report/rekap/transport/view', [App\Http\Controllers\Api\ReportApiController::class, 'rekap_transport_view']);
    Route::post('/report/rekap/transport/post', [App\Http\Controllers\Api\ReportApiController::class, 'rekap_transport_post']);


    //***************************************************************Spesial Limits***************************************************************//
    Route::post('/quota/gm/spesiallimit', [App\Http\Controllers\Api\MasterApiController::class, 'edit_parameter']);
    Route::post('/quota/gm/defaultspesiallimit', [App\Http\Controllers\Api\MasterApiController::class, 'back_default']);


    //***************************************************************Spesial Limit Histories***************************************************************//
    Route::get('/quota/gm/spesiallimit-histories', [App\Http\Controllers\Api\MasterApiController::class, 'spesial_limit_history']);


    //*************************************************v1.0 by Merio, 20151230, Crud for Master Sub Section************************************************//
    Route::get('/m_sub_section/view', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_view']);
    Route::post('/m_sub_section/import', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_import']);
    Route::post('/m_sub_section/create', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_create']);
    Route::get('/m_sub_section/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_delete']);
    Route::get('/m_sub_section/update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_update']);
    Route::post('/m_sub_section/update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_sub_section_update_save']);

    Route::get('/member-sub_section', [App\Http\Controllers\Api\MasterApiController::class,'member_sub_section']);



    //**********************************************v1.0 by : Merio Aji, 20160107, Crud for Master Transports*********************************************//
    Route::get('/m_transport/view', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_view']);
    Route::post('/m_transport/import', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_import']);
    Route::post('/m_transport/create', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_create']);
    Route::get('/m_transport/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_delete']);
    Route::get('/m_transport/update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_update']);
    Route::post('/m_transport/update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_transport_update_save']);


    //******************************************************************WEEKDAY HOLIDAY******************************************************************//
    Route::get('/m_weekday/holiday', [App\Http\Controllers\Api\SpklApiController::class, '']);


    //******************************************************v1.6 by Andre, 20160502 Crud Quota Real******************************************************//
    Route::get('/m_quota_original/view', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_real_view']);
    Route::post('/m_quota_original/create', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_real_create']);
    Route::get('/m_quota_original/update/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_real_update']);
    Route::get('/m_quota_original/delete/{id}', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_real_delete']);
    Route::post('/m_quota_original/update/save', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_real_update_save']);
    Route::get('/m_quota_original/add', [App\Http\Controllers\Api\MasterApiController::class, 'm_quota_original_add']);


    //***********************************************T APPROVED SPESIAL LIMIT & T APPROVED SPESIAL LIMIT LOG**********************************************//
    Route::get('/quota/gm/approvedspesiallimit/{npk_user}', [App\Http\Controllers\Api\MasterApiController::class, 'approved_spesial_limit']);
    Route::get('/quota/gm/cancelapprovedspesiallimit/{npk_user}', [App\Http\Controllers\Api\MasterApiController::class, 'cancel_approved_spesial_limit']);



    //*****************************************************************T QUOTA TRANSACTION*****************************************************************//


    //***********************************************************************T SPKL***********************************************************************//
    Route::get('t_spkl/view', [App\Http\Controllers\Api\SpklApiController::class, 't_spkl_view']);
    Route::post('t_spkl/print', [App\Http\Controllers\Api\SpklApiController::class, 't_spkl_print']);
    Route::get('t_spkl/print/{id}', [App\Http\Controllers\Api\SpklApiController::class, 't_spkl_print_2']);
    Route::get('/t_spkl/date/{date}', [SpklApiController::class, 'getByDate']);


    //*****************************************************************T SPKL BACKUP*****************************************************************//



    //*****************************************************************T SPKL DETAIL*****************************************************************//
    //v1.0 by Ferry, 20151230, Manage SPKL Planning & Actual Entry
    Route::get('/spkl/planning/view/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_view_search_result']);
    Route::get('/spkl/reject/view/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_reject_view_search_result']);
    Route::get('/spkl/planning/print/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_print']);

    Route::get('/spkl/actual/view/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_view_search_result']);
    Route::get('/spkl/history/view/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_history_view_search_result']);
    Route::get('/spkl/actual/print/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_print']);

    Route::post('/report/export/result', [App\Http\Controllers\Api\SpklApiController::class, 'report_export_result']);

    // Routes for SPKL Planning Approval
    Route::get('/planning/approval', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approval']);
    Route::get('/planning/approval/list/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approval_list']);

    Route::get('/planning/approval/1', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approval_1']);
    Route::get('/planning/approval/2', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approval_2']);
    Route::get('/planning/approval/3', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approval_3']);

    // Routes for Approving and Rejecting SPKL Planning
    Route::get('/planning/approve/1/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_1']);
    Route::get('/planning/reject/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject']);

    Route::get('/planning/approve/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_2']);
    Route::get('/planning/reject/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject_2']);

    Route::get('/planning/approve/3/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_3']);
    Route::get('/planning/reject/3/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject_3']);

    // Routes for Approving and Rejecting SPKL Planning Members
    Route::get('/planning/approve/member/1/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_member_1']);
    Route::get('/planning/approve/member/2/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_member_2']);
    Route::get('/planning/approve/member/3/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve_member_3']);

    Route::get('/planning/reject/member/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject_member']);
    Route::get('/planning/reject/member/2/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject_member_2']);
    Route::get('/planning/reject/member/3/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject_member_3']);

    // Search Result Routes for SPKL Planning Approval
    Route::get('/planning/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_approval_search_result']);
    Route::get('/planning/search_result/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_approval_search_result_3']);


    // Routes for adding and saving SPKL planning
    Route::post('/planning/add/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_add_save']);
    Route::post('/planning/add/clv/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_add_clv_save']);
    Route::post('/planning/add/employee/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_add_employee_save']);
    Route::post('/planning/input/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_input_save']);
    Route::post('/planning/input/clv/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_input_clv_save']);

    // Routes for deleting SPKL planning
    Route::get('/planning/delete/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_delete_all']);
    Route::get('/planning/delete/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_delete']);
    Route::get('/planning/delete/clv/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_delete_clv']);
    Route::get('/planning/2/delete/{id}/{id2}/{id3}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_2_delete']);

    // Routes for updating SPKL planning
    Route::get('/planning/update/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_update']);
    Route::get('/planning/update/clv/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_update_clv']);
    Route::get('/planning/2/update/{id}/{id2}/{id3}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_2_update']);
    Route::post('/planning/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_update_save']);
    Route::post('/planning/update/clv/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_update_clv_save']);
    Route::post('/planning/2/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_2_update_save']);
    Route::get('/planning/detail/update/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_detail_update']);
    Route::post('/planning/detail/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_detail_update_save']);

    // Routes for SPKL planning input
    Route::get('/planning/input', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_input']);
    Route::get('/planning/clv/input', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_clv_input']);



    //v1.0 by Merio, 20160120, Input SPKL Actual Entry
    // SPKL Actual input and result routes
    Route::get('/input', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_input']);
    Route::post('/result', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_result']);
    Route::get('/result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_result2']);

    // SPKL Actual creation and synchronization routes
    Route::get('/actual/create/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_create']);
    Route::post('/actual/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_save']);
    Route::get('/actual/sinkron/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_sinkron']);


    // SPKL Planning approval and rejection routes
    Route::get('/planning/approve/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_approve']);
    Route::get('/planning/reject/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_reject']);


    // SPKL Actual approval and search result routes
    Route::get('/approval', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval']);
    Route::get('/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_search_result']);
    Route::get('/approve/member/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve_member']);
    Route::get('/reject/member/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_member']);
    Route::get('/approve/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve']);
    Route::get('/reject/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject']);

    // SPKL Actual edit member routes
    Route::get('/edit/member/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_edit_member']);
    Route::post('/member/edit/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_member_edit_save']);

    Route::get('/edit/member/2/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_edit_member_2']);
    Route::post('/member/2/edit/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_member_2_edit_save']);

    Route::get('/edit/member/3/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_edit_member_3']);
    Route::post('/member/3/edit/save', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_member_3_edit_save']);

    // Additional approval routes
    Route::get('/approval/2', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval_2']);
    Route::get('/search_result/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_search_result_2']);
    Route::get('/approve/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve_2']);
    Route::get('/reject/2/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_2']);
    Route::get('/approve/member/2/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve_member_2']);
    Route::get('/reject/member/2/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_member_2']);

    Route::get('/approval/3', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval_3']);
    Route::get('/search_result/3/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_search_result_3']);
    Route::get('/approve/3/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve_3']);
    Route::get('/reject/3/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_3']);
    Route::get('/approve/member/3/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approve_member_3']);
    Route::get('/reject/member/3/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_member_3']);

    // Approval and update routes
    Route::get('/1/view', [App\Http\Controllers\Api\SpklApiController::class, 'approval_1_view']);
    Route::get('/1/delete/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_1_delete']);
    Route::get('/2/view', [App\Http\Controllers\Api\SpklApiController::class, 'approval_2_view']);
    Route::get('/2/delete/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_2_delete']);
    Route::get('/3/view', [App\Http\Controllers\Api\SpklApiController::class, 'approval_3_view']);
    Route::get('/3/delete/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_3_delete']);
    Route::get('/1/update/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_1_update']);
    Route::get('/2/update/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_2_update']);
    Route::get('/3/update/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'approval_3_update']);
    Route::post('/1/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'approval_1_update_save']);
    Route::post('/2/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'approval_2_update_save']);
    Route::post('/3/update/save', [App\Http\Controllers\Api\SpklApiController::class, 'approval_3_update_save']);

    // Other SPKL related routes
    Route::get('/on_progress/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_on_progress_view']);
    Route::get('/done/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_done_view']);
    Route::get('/history', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_history']);
    Route::get('/reject/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_reject_view']);

    //hotfix-2.2.9, by Merio, 20161027, memisahkan spkl yang blm dan sudah diproses oleh hrd
    Route::get('spkl/done/hrd/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_done_hrd']);

    //hotfix-1.9.3, 20160815, by Merio, fitur list
    Route::get('/spkl/list', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list']);
    Route::get('/spkl/list/realization', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_realization']);
    Route::get('/spkl/list/done', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_done']);
    Route::get('/spkl/list/reject', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_reject']);

    Route::get('/spkl_list/view/search_result/{id}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_view_search_result']);
    Route::post('/spkl_list/view/search_result', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_view_search_result2']);
    Route::post('/spkl_list/2/view',  [App\Http\Controllers\Api\SpklApiController::class, 'spkl_list_2_view']);

    Route::get('/spkl/planning/history/3/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_planning_history_3_view']);

    Route::get('/spkl/actual/approval/1/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval_1_view']);
    Route::get('/spkl/actual/approval/2/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval_2_view']);
    Route::get('/spkl/actual/approval/3/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_approval_3_view']);

    Route::get('spkl/actual/rejects/1/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_1_view']);
    Route::get('spkl/actual/history/1/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_history_1_view']);
    Route::get('spkl/actual/rejects/2/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_2_view']);
    Route::get('spkl/actual/history/2/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_history_2_view']);
    Route::get('spkl/actual/rejects/3/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_reject_3_view']);
    Route::get('spkl/actual/history/3/view', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_actual_history_3_view']);


    Route::get('/spkl/reject/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_reject']);
    Route::get('/spkl/status', [App\Http\Controllers\Api\SpklApiController::class, 'spkl_status']);

    //dev-2.0, 20160825, by Merio, untuk check overtime karyawan di halaman guest
    Route::get('/check/overtime/mp', [App\Http\Controllers\Api\SpklApiController::class, 'check_overtime_mp']);
    Route::post('/check/overtime/mp2', [App\Http\Controllers\Api\SpklApiController::class, 'check_overtime_mp2']);

    //hotfix-2.2.2, 20160922, by Merio, change mp in overtime realization
    Route::get('/spkl/actual/change/ot/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'change_ot_actual_view']);
    Route::post('/spkl/actual/change/ot/save', [App\Http\Controllers\Api\SpklApiController::class, 'change_ot_actual_save']);

    //hotfix-2.2.3, 20161003, by Merio, change mp in overtime planning
    Route::get('/spkl/planning/change/ot/{id}/{id2}', [App\Http\Controllers\Api\SpklApiController::class, 'change_ot_planning_view']);
    Route::post('/spkl/planning/change/ot/save', [App\Http\Controllers\Api\SpklApiController::class, 'change_ot_planning_save']);

    Route::get('/show-query', [InnerJoinController::class, 'showQuery']);


     //********************************************************************************************************************************************//

     Route::get('/test-query', function () {
        // Menyiapkan query
        $query = DB::table('lemburs')
            ->join('spkls', 'lemburs.id', '=', 'spkls.id_spkl')
            ->join('employees', 'lemburs.npk', '=', 'employees.npk')
            ->select(
                'lemburs.id as lembur_id', 'lemburs.nama as lembur_nama', 'lemburs.title as lembur_title', 'lemburs.tanggal_lembur as lembur_tanggal_lembur',
                'spkls.id as spkl_id', 'spkls.type as spkl_type', 'spkls.category as spkl_category', 'spkls.note as spkl_note',
                'employees.npk as employee_npk', 'employees.nama as employee_nama', 'employees.occupation as employee_occupation'
            );

        // Menjalankan query dan mendapatkan hasilnya
        $results = $query->get();

        // Mengembalikan hasil dalam format JSON
        return response()->json($results);
    });

});
