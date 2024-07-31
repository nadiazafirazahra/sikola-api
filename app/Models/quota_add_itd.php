<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class quota_add_itd extends Model {

	protected $fillable =  [
    'id',
	'quota_add',
	'date',
	'quota_plan',
	'memo_number',
    'created_at',
    'updated_at',
	'id_quota_real'
	];


}
