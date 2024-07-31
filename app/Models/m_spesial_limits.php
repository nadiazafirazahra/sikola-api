<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_spesial_limits extends Model {

	protected $fillable = [
    'id',
    'npk',
    'nama',
    'sub_section',
    'quota_limit',
    'quota_limit_weekday',
    'quota_limit_holiday',
    'created_at',
    'updated_at'
    ];
}
