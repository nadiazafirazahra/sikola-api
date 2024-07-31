<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class m_spesial_limit_histories extends Model {

    protected $fillable = [
        'id',
        'user_id',
        'quota_before_update_weekday',
        'quota_after_update_weekday',
        'quota_before_update_holiday',
        'quota_after_update_holiday',
        'ip_address',
        'created_at',
        'updated_at'
        ];

	protected $guarded = ['updated_at'];

	protected $hidden = ['ip_address'];

	/**
     * change quota before to hour format.
     *
     * @param  string  $value
     * @return void
     */
    public function getQuotaBeforeUpdateAttribute($value)
    {
        return $value / 60;
    }

    /**
     * change quota after to hour format.
     *
     * @param  string  $value
     * @return void
     */
    public function getQuotaAfterUpdateAttribute($value)
    {
        return $value / 60;
    }
}
