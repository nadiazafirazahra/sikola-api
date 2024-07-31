<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class t_approved_limit_spesial extends Model {

	protected $fillable = [
    'id',
	'npk',
	'nama',
    'created_at',
    'updated_at'
	];

}
