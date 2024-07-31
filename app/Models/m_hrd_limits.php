<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_hrd_limits extends Model {

	protected $fillable = [
	'npk',
	'nama',
	'quota_limit_weekday',
	'quota_limit_holiday'
	];

}
