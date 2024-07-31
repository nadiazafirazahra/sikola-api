<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_quota_used extends Model {
	protected $fillable = [
	'id',
	'department',
	'quota_plan',
	'quota_approve',
	'month',
	'fyear',
	'employment_status',
	'occupation',
    'created_at',
    'updated_at'
	];
}
