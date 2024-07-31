<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_quota_add extends Model {

	protected $fillable =  [
    'id',
	'department',
	'quota_kadept',
	'status',
	'date_kadept',
	'date_gm',
	'quota_gm',
	'reason_kadept',
	'reason_gm',
	'npk_kadept',
	'npk_gm',
    'created_at',
    'updated_at',
	'id_quota'
	];

}
