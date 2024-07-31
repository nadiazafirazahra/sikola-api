<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_holiday extends Model {

	//
	protected $fillable = [
	'date_holiday',
	'type_holiday',
	'note_holiday',
	'npk_admin',
    'created_at',
    'updated_at'
	];
}
