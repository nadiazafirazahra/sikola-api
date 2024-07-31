<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class t_spkl_detail_backup extends Model {

	protected $fillable =  [
		'id',
		'id_spkl',
		'npk',
        'npk_before',
		'start_date',
		'end_date',
		'start_planning',
		'end_planning',
		'start_actual',
        'system_in',
        'system_out',
        'npk_edited',
		'date_edited',
        'end_actual',
        'ref_code',
        'notes',
		'is_closed',
		'is_clv',
        'quota_ot',
		'quota_ot_actual',
        'sub_section',
		'status',
        'kd_shift_makan',
        'kd_trans',
        'kd_shift_trans',
		'approval_1_planning_date',
		'approval_2_planning_date',
		'approval_3_planning_date',
		'approval_1_realisasi_date',
		'approval_2_realisasi_date',
		'approval_3_realisasi_date',
		'npk_leader',
		'reject_date',
        'created_at',
        'updated_at'
	];
}
