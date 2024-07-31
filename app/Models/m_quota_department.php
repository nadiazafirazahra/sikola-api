<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\m_department;

class m_quota_department extends Model {

	protected $fillable =  [
    'id',
	'code_department',
	'quota_plan',
	'quota_used',
	'month',
	'year',
	'id_admin',
    'created_at',
    'updated_at'
	];

	//hotfix-3.1.3, Yudo, 20170522, Get Quota HRD
    public function getQuotaHRD($requester, $year, $month){

        $code = m_department::select('code')->where('npk', $requester)->firstOrFail();

        $tot_quota = self::where('code_department','=',$code->code)
        ->where('year', $year)
        ->where('month', $month)->sum('quota_plan');

        return round( $tot_quota / 60, 2);
    }
    //end yudo

}
