<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class t_quota_transaction extends Model {

	protected $fillable =  [
            'id',
			'department',
			'npk_leader',
			'npk_spv',
			'npk_kadept',
			'npk_gm',
			'quota_ot',
			'id_spkl',
			'section',
			'sub_section',
			'occupation',
			'employment_status',
            'created_at',
            'updated_at'
			];

}
