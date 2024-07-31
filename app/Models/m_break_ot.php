<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class m_break_ot extends Model
{
    use HasFactory;

	protected $fillable =  [
	'day_break',
	'start_break',
	'end_break',
	'status_break',
	'duration_break',
    'created_at'
	];

}
