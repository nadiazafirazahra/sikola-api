<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_over_request_histories extends Model {

    protected $fillable =  [
        'id',
        'user_id',
        'details',
        'approved_by',
        'quota_at_that_time',
        'request_transaction_code',
        'created_at',
        'updated_at'
        ];

	protected $guarded = ['updated_at'];

}
