<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class t_spkl_backup extends Model {

	protected $fillable =  [
		'id',
		'id_spkl',
		'type',
		'category',
		'category_detail',
		'note',
		'is_late',
        'kolektif',
		'is_print',
		'npk_1',
		'npk_2',
		'npk_3',
		'created_at',
        'updated_at'
	];
}
