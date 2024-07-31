<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class t_approved_limit_spesial_log extends Model {

	protected $fillable = [
    'id',
	'npk_allowed',
	'allowed_by',
	'date',
	'status',
    'created_at',
    'updated_at'
	];

}
