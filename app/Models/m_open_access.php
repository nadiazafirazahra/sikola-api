<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_open_access extends Model {
	protected $fillable =  [
    'id',
	'npk_user',
	'limit',
	'month',
	'year',
	'is_active',
    'created_at',
    'updated_at'
	];
}
