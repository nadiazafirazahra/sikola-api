<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('user', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('status_user');
        $table->string('ot_par');
        $table->string('remember_token');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
        $table->string('limit_mp');
    });

    Schema::create('m_break_ot', function (Blueprint $table) {
        $table->id();
        $table->string('day_break');
        $table->string('start_break');
        $table->string('end_break');
        $table->string('status_break');
        $table->string('duration_break');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_category', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_department', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->string('alias');
        $table->string('code');
        $table->string('npk');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_director', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_division', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->string ('npk');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
        $table->string('code_director');
    });

    Schema::create('m_employee', function (Blueprint $table) {
        $table->id();
        $table->string('npk');
        $table->string('nama');
        $table->string ('line_code');
        $table->string ('sub_section');
        $table->string ('occupation');
        $table->string ('transport');
        $table->string ('status_mp');
        $table->string ('employment_status');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
        $table->string('quota_used_1');
        $table->string('quota_remain_1');
        $table->string('quota_used_2');
        $table->string('quota_remain_2');
        $table->string('quota_used_3');
        $table->string('quota_remain_3');
        $table->string('quota_used_4');
        $table->string('quota_remain_4');
        $table->string('quota_used_5');
        $table->string('quota_remain_5');
        $table->string('quota_used_6');
        $table->string('quota_remain_6');
        $table->string('quota_used_7');
        $table->string('quota_remain_7');
        $table->string('quota_used_8');
        $table->string('quota_remain_8');
        $table->string('quota_used_9');
        $table->string('quota_remain_9');
        $table->string('quota_used_10');
        $table->string('quota_remain_10');
        $table->string('quota_used_11');
        $table->string('quota_remain_11');
        $table->string('quota_used_12');
        $table->string('quota_remain_12');
    });

    Schema::create('m_holiday', function (Blueprint $table) {
        $table->id();
        $table->timestamps('date_holiday');
        $table->string('type_holiday');
        $table->string('note_holiday');
        $table->string ('npk_admin');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_line', function (Blueprint $table) {
        $table->id();
        $table->string('line_code');
        $table->string('line_name');
        $table->string('line_alias');
        $table->string ('section_id');
        $table->string ('remember_token');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_occupation', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_open_access', function (Blueprint $table) {
        $table->id();
        $table->string('npk_user');
        $table->string('limit');
        $table->string('month');
        $table->string ('year');
        $table->string ('is_active');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_over_request_histories', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->string('details');
        $table->string('approved');
        $table->string ('quota_at_that_time');
        $table->string ('request_transaction_code');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_quota_add', function (Blueprint $table) {
        $table->id();
        $table->string('department');
        $table->string('quota_kadept');
        $table->string('status');
        $table->string ('date_kadept');
        $table->string ('date_gm');
        $table->string ('quota_gm');
        $table->string ('reason_kadept');
        $table->string ('reason_gm');
        $table->string ('npk_kadept');
        $table->string ('npk_gm');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
        $table->string ('id_quota');
    });

    Schema::create('m_quota_add', function (Blueprint $table) {
        $table->id();
        $table->string('code_department');
        $table->string('quota_plan');
        $table->string('quota_used');
        $table->string ('month');
        $table->string ('year');
        $table->string ('id_admin');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_quota_real', function (Blueprint $table) {
        $table->id();
        $table->string('department');
        $table->string('quota_plan');
        $table->string('quota_approve');
        $table->string ('month');
        $table->string ('fyear');
        $table->string ('employment_status');
        $table->string ('occupation');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_quota_request', function (Blueprint $table) {
        $table->id();
        $table->string('id_transaction');
        $table->string('npk');
        $table->string('quota');
        $table->string('quota_before');
        $table->string('quota_before_detail');
        $table->string('quota_after');
        $table->string('quota_after_detail');
        $table->string ('month');
        $table->string ('year');
        $table->string ('keterangan');
        $table->string ('requester');
        $table->string ('approval');
        $table->string ('department_code');
        $table->string ('status');
        $table->timestamps('request_date');
        $table->timestamps('reject_date');
        $table->string ('approve_date');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_quota_used', function (Blueprint $table) {
        $table->id();
        $table->string('department');
        $table->string('quota_plan');
        $table->string('quota_approve');
        $table->string ('month');
        $table->string ('fyear');
        $table->string ('employment_status');
        $table->string ('occupation');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_section', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->string('alias');
        $table->string('code_department');
        $table->string('npk');
        $table->string('npk_admin');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_shift', function (Blueprint $table) {
        $table->id();
        $table->string('kode');
        $table->string('nama');
        $table->string('tipe_golongan');
        $table->string('tipe_hari_kode');
        $table->string('time_in');
        $table->string('time_out');
        $table->string('time_cutoff1');
        $table->string('time_cutoff2');
        $table->string('days_out');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_spesial_limits', function (Blueprint $table) {
        $table->id();
        $table->string('npk');
        $table->string('nama');
        $table->string('sub_section');
        $table->string('quota_limit');
        $table->string('quota_limit_weekday');
        $table->string('quota_limit_holiday');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_spesial_limit_histories', function (Blueprint $table) {
        $table->id();
        $table->string('user_id');
        $table->string('quota_before_update_weekday');
        $table->string('quota_after_update_weekday');
        $table->string('quota_before_update_holiday');
        $table->string('quota_after_update_holiday');
        $table->string('ip_address');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_sub_section', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('name');
        $table->string('alias');
        $table->string('code_section');
        $table->string('npk');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_transport', function (Blueprint $table) {
        $table->id();
        $table->string('code');
        $table->string('route');
        $table->string('kd_shift');
        $table->string('time_in');
        $table->string('time_out');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('m_weekday_holiday', function (Blueprint $table) {
        $table->id();
        $table->string('npk');
        $table->string('nama');
        $table->string('used_weekday_1');
        $table->string('used_holiday_1');
        $table->string('used_weekday_2');
        $table->string('used_holiday_2');
        $table->string('used_weekday_3');
        $table->string('used_holiday_3');
        $table->string('used_weekday_4');
        $table->string('used_holiday_4');
        $table->string('used_weekday_5');
        $table->string('used_holiday_5');
        $table->string('used_weekday_6');
        $table->string('used_holiday_6');
        $table->string('used_weekday_7');
        $table->string('used_holiday_7');
        $table->string('used_weekday_8');
        $table->string('used_holiday_8');
        $table->string('used_weekday_9');
        $table->string('used_holiday_9');
        $table->string('used_weekday_10');
        $table->string('used_holiday_10');
        $table->string('used_weekday_11');
        $table->string('used_holiday_11');
        $table->string('used_weekday_12');
        $table->string('used_holiday_12');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('quota_add_itd', function (Blueprint $table) {
        $table->id();
        $table->string('quota_add');
        $table->string('date');
        $table->string('quota_plan');
        $table->string('memo_number');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
        $table->string('id_quota_real');
    });

    Schema::create('t_approved_limit_spesial', function (Blueprint $table) {
        $table->id();
        $table->string('npk');
        $table->string('nama');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_approved_limit_spesial', function (Blueprint $table) {
        $table->id();
        $table->string('npk_allowed');
        $table->string('allowed_by');
        $table->string('date');
        $table->string('status');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_quota_transaction', function (Blueprint $table) {
        $table->id();
        $table->string('department');
        $table->string('npk_leader');
        $table->string('npk_spv');
        $table->string('npk_kadept');
        $table->string('npk_gm');
        $table->string('quota_ot');
        $table->string('id_spkl');
        $table->string('section');
        $table->string('sub_section');
        $table->string('occupation');
        $table->string('employment_status');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_spkl', function (Blueprint $table) {
        $table->id();
        $table->string('id_spkl');
        $table->string('type');
        $table->string('category');
        $table->string('category_detail');
        $table->string('note');
        $table->string('is_late');
        $table->string('kolektif');
        $table->string('is_print');
        $table->string('npk_1');
        $table->string('npk_2');
        $table->string('npk_3');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_spkl_backup', function (Blueprint $table) {
        $table->id();
        $table->string('id_spkl');
        $table->string('type');
        $table->string('category');
        $table->string('category_detail');
        $table->string('note');
        $table->string('is_late');
        $table->string('kolektif');
        $table->string('is_print');
        $table->string('npk_1');
        $table->string('npk_2');
        $table->string('npk_3');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_spkl_detail', function (Blueprint $table) {
        $table->id();
        $table->string('id_spkl');
        $table->string('npk');
        $table->string('npk_before');
        $table->timestamps('start_date');
        $table->timestamps('end_date');
        $table->timestamps('start_planning');
        $table->timestamps('end_planning');
        $table->timestamps('start_actual');
        $table->timestamps('system_in');
        $table->timestamps('system_out');
        $table->string('npk_edited');
        $table->timestamps('date_edited');
        $table->timestamps('end_actual');
        $table->string('ref_code');
        $table->string('notes');
        $table->string('is_closed');
        $table->string('is_clv');
        $table->string('quota_ot');
        $table->string('quota_ot_actual');
        $table->string('sub-section');
        $table->string('status');
        $table->string('kd_shift_makan');
        $table->string('kd_trans');
        $table->string('kd_shift_trans');
        $table->timestamps('approval_1_planning_date');
        $table->timestamps('approval_2_planning_date');
        $table->timestamps('approval_3_planning_date');
        $table->timestamps('approval_1_realisasi_date');
        $table->timestamps('approval_2_realisasi_date');
        $table->timestamps('approval_3_realisasi_date');
        $table->string('npk_leader');
        $table->timestamps('reject_date');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('t_spkl_detail_backup', function (Blueprint $table) {
        $table->id();
        $table->string('id_spkl');
        $table->string('npk');
        $table->string('npk_before');
        $table->timestamps('start_date');
        $table->timestamps('end_date');
        $table->timestamps('start_planning');
        $table->timestamps('end_planning');
        $table->timestamps('start_actual');
        $table->timestamps('system_in');
        $table->timestamps('system_out');
        $table->string('npk_edited');
        $table->timestamps('date_edited');
        $table->timestamps('end_actual');
        $table->string('ref_code');
        $table->string('notes');
        $table->string('is_closed');
        $table->string('is_clv');
        $table->string('quota_ot');
        $table->string('quota_ot_actual');
        $table->string('sub-section');
        $table->string('status');
        $table->string('kd_shift_makan');
        $table->string('kd_trans');
        $table->string('kd_shift_trans');
        $table->timestamps('approval_1_planning_date');
        $table->timestamps('approval_2_planning_date');
        $table->timestamps('approval_3_planning_date');
        $table->timestamps('approval_1_realisasi_date');
        $table->timestamps('approval_2_realisasi_date');
        $table->timestamps('approval_3_realisasi_date');
        $table->string('npk_leader');
        $table->timestamps('reject_date');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });

    Schema::create('lembur', function (Blueprint $table) {
        $table->id();
        $table->string('nama');
        $table->string('npk');
        $table->string('title');
        $table->string('tanggal_lembur');
        $table->string('jam_masuk');
        $table->string('jam_pulang');
        $table->timestamps('created_at');
        $table->timestamps('updated_at');
    });






}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('post');
        Schema::dropIfExists('user');
        Schema::dropIfExists('m_break_ot');
        Schema::dropIfExists('m_category');
        Schema::dropIfExists('m_department');
        Schema::dropIfExists('m_director');
        Schema::dropIfExists('m_division');
        Schema::dropIfExists('m_employee');
        Schema::dropIfExists('m_holiday');
        Schema::dropIfExists('m_line');
        Schema::dropIfExists('m_occupation');
        Schema::dropIfExists('m_open_access');
        Schema::dropIfExists('m_over_request_histories');
        Schema::dropIfExists('m_quota_add');
        Schema::dropIfExists('m_quota_department');
        Schema::dropIfExists('m_quota_real');
        Schema::dropIfExists('m_quota_request');
        Schema::dropIfExists('m_quota_used');
        Schema::dropIfExists('m_section');
        Schema::dropIfExists('m_shift');
        Schema::dropIfExists('m_spesial_limits');
        Schema::dropIfExists('m_spesial_limit_histories');
        Schema::dropIfExists('m_sub_section');
        Schema::dropIfExists('m_transport');
        Schema::dropIfExists('m_weekday_holiday');
        Schema::dropIfExists('quota_add_itd');
        Schema::dropIfExists('t_approved_limit_spesial');
        Schema::dropIfExists('t_approved_limit_spesial_log');
        Schema::dropIfExists('t_quota_transaction');
        Schema::dropIfExists('t_spkl');
        Schema::dropIfExists('t_spkl_backup');
        Schema::dropIfExists('t_spkl_detail');
        Schema::dropIfExists('t_spkl_detail_backup');
        Schema::dropIfExists('lembur');

    }
};
