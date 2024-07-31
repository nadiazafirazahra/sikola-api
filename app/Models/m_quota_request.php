<?php

namespace App\Models;

use Exception;
use Carbon\Carbon;
use App\Models\m_division;
use App\Models\m_employee;
use App\Models\m_department;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class m_quota_request extends Model {

	protected $fillable = [
    'id',
	'id_transaction',
	'npk',
	'quota',
    'quota_before',
    'quota_before_detail',
    'quota_after',
    'quota_after_detail',
	'month',
    'year',
    'keterangan',
	'requester',
	'approval',
	'department_code',
	'status',
	'request_date',
	'reject_date',
	'approve_date',
    'created_at',
    'updated_at'
	];

    // hotfix-3.1.4, Ferry, 20170526, Formatting status for humans
    public $date_approved = '';

    // hotfix-3.1.4, Ferry, 20170526, Formatting status Year - Month
    public function getYearMonthFormat() {

        return Carbon::createFromDate($this->year, $this->month, 1)->formatLocalized('%Y - %B');
    }

    // hotfix-3.1.4, Ferry, 20170526, Formatting status Month
    public function getMonthFormat() {

        return Carbon::createFromDate($this->year, $this->month, 1)->formatLocalized('%B');
    }

    // hotfix-3.1.4, Ferry, 20170526, Formatting status for humans
    public function getStatusForHumansAttribute($value) {
        if ($value == "1") {
            $status = "Waiting to Generate";
            $this->date_approved = $this->created_at;
        } elseif ($value == "2") {
            $status = "Waiting Approval GM";
            $this->date_approved = $this->request_date;
        } elseif ($value == "3") {
            $status = "Approved by GM";
            $this->date_approved = $this->approve_date;
        } elseif ($value == "-1") {
            $status = "Rejected by GM";
            $this->date_approved = $this->reject_date;
        }
        return $status;
    }

    // hotfix-3.1.2, Ferry, 20170516, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasEmployee()
    {
        return $this->hasOne('App\m_employee', 'npk', 'npk');
    }

    // hotfix-3.1.2, Ferry, 20170516, Generate id quota request, $month = 2 digit, cth 05
    public function getNewSpklId ($department) {
        $request = self::selectRaw('MAX(id_transaction) as maxid')
                        ->where('month', $this->month)
                        ->where('year', $this->year)
                        ->where('department_code', $department)
                        ->first();

        $last_id    = substr($request->maxid, -4) + 1;
        $last_id    = sprintf("%04s", $last_id);
        return $this->year.sprintf("%02s", $this->month).$department.$last_id;
    }

    // hotfix-3.1.2, Ferry, 20170513, Akses Quota History
    public function getGMQuotaBeforeHistory($requester, $year, $month) {

        $subQuery = m_quota_request::selectRaw('npk, MAX(id_transaction) AS idx')
                            ->where('requester', $requester)
                            ->where('status', 3)
                            ->where('year', $year)
                            ->where('month', $month)
                            ->groupby('npk')->getQuery();

        $result = DB::table(DB::raw("({$subQuery->toSql()}) as a"))
            ->mergeBindings($subQuery)
            ->selectRaw("COALESCE(sum(quota_before) , 0) total")
            ->join('m_quota_requests as q', function($join) {
                    $join->on('a.idx', '=', 'q.id_transaction');
                    $join->on('a.npk', '=', 'q.npk');
                })
            ->first();

        return round($result->total / 60, 2);
    }

    // hotfix-3.1.2, Ferry, 20170513, Akses Quota History
    public function getGMQuotaAfterHistory($requester, $year, $month) {

        $subQuery = m_quota_request::selectRaw('npk, MAX(id_transaction) AS idx')
                            ->where('requester', $requester)
                            ->where('status', 3)
                            ->where('year', $year)
                            ->where('month', $month)
                            ->groupby('npk')->getQuery();

        $result = DB::table(DB::raw("({$subQuery->toSql()}) as a"))
            ->mergeBindings($subQuery)
            ->selectRaw("COALESCE(sum(quota_after) , 0) total")
            ->join('m_quota_requests as q', function($join) {
                    $join->on('a.idx', '=', 'q.id_transaction');
                    $join->on('a.npk', '=', 'q.npk');
                })
            ->first();

        return round($result->total / 60, 2);
    }

    // hotfix-3.1.2, Ferry, 20170505, Akses Quota Current
    public function getGMQuotaBefore($requester, $year, $month, $rounded = true) {

        // hotfix-3.5.2, Alternatif-3.
        // cek department requester
        $requester_dept = m_department::where('npk', $requester)->first();

        if ($requester_dept) {

            // explode ke array semua section
            $sections = $requester_dept->hasManySection;

            $arrSection = [];
            foreach ($sections as $section) {
                array_push($arrSection, "'".$section->code."%'");
            }
            $query = implode(' OR sub_section LIKE ', $arrSection);

            $result = m_employee::whereRaw('sub_section LIKE '.$query)->sum('quota_remain_'.$month);
        }
        else {
            $result = 0;
        }

        if ($rounded) {
            return round($result / 60, 2);
        }
        else {
            return $result;
        }
    }

    // hotfix-3.1.2, Ferry, 20170505, Akses Quota Current
    public function getGMQuotaAfter($requester, $year, $month) {


        $arrNpkExclude = self::select('npk')
                            ->where('requester', $requester)
                            ->where('status', 2)
                            ->where('year', $year)
                            ->where('month', $month)
                            ->distinct()->get()->toArray();

        $quota_request = self::where('requester', $requester)
                            ->where('status', 2)
                            ->where('year', $year)
                            ->where('month', $month)->sum('quota');


        $result = $this->getGMQuotaBefore($requester, $year, $month, false) -
                    m_employee::whereIn('npk', $arrNpkExclude)->sum('quota_remain_'.$month) +
                    $quota_request;

        return round($result / 60, 2);
    }

    //hotfix-3.1.3, by yudo, 20170519, get total MP
    public function getTotalMp($npk, $year, $month){

        $total_mp = self::where('requester', $npk)
                        ->where('status', 1)
                        ->where('year', $year)
                        ->where('month', $month)->count('npk');

        return $total_mp;
    }

    //hotfix-3.1.3, by yudo, 20170519, get total quota upload
    public function getTotalQuotaUpload($npk, $year, $month){

       $total_mp = self::where('requester', $npk)
                        ->where('status', 1)
                        ->where('year', $year)
                        ->where('month', $month)->sum('quota');

        return $total_mp;
    }

    // hotfix-3.1.3, Yudo, 20170522, Akses Quota Current
    public function getGMQuotaAfterGenerate($requester, $year, $month) {


        $arrNpkExclude = self::select('npk')
                            ->where('requester', $requester)
                            ->where('status', 1)
                            ->where('year', $year)
                            ->where('month', $month)
                            ->distinct()->get()->toArray();

        $quota_request = self::where('requester', $requester)
                            ->where('status', 1)
                            ->where('year', $year)
                            ->where('month', $month)->sum('quota');

        // hotfix-3.5.2, Ferry, 20180913
        $result = $this->getGMQuotaBefore($requester, $year, $month, false) -
                    m_employee::whereIn('npk', $arrNpkExclude)->sum('quota_remain_'.$month) +
                    ($quota_request*60);

        return round($result / 60, 2);
    }

    //hotfix-3.1.3, Yudo, 20170522, Get Quota HRD
    //hotfix-3.1.3, Yudo, 20170522, Get Quota HRD
    public function getQuotaHRD($requester, $year, $month){

        // $code = m_department::select('code')->where('npk', $requester)->firstOrFail();

        //total Quota dari HRD
        // return 'r';



        $tot_quota = m_quota_department::join('m_departments', 'm_departments.code', '=', 'm_quota_departments.code_department')
        ->where('m_departments.npk','=',$user->npk)
        ->where('m_quota_departments.year', $getDate->year)
        ->where('m_quota_departments.month', $getDate->month)->sum('m_quota_departments.quota_plan');


        // die($tot_quota);
die('e');
        return $tot_quota/60;
    }
    //end yudo


    // hotfix-3.1.1, Ferry, 20170428, Formatting output of quota rounded
    public function getQuotaPlanInHoursAttribute($value) {
        return round($value / 60, 2);
    }

	public static function array_to_db($array_data){
        $total=sizeof($array_data);
        if($total>0){
            try {

                // hotfix-3.5.2, Ferry, 20180914. Saring dulu npk requester dan approver diluar loop
                //                                  Tambahkan skema commit-rollback

                $npk = Auth::user()->npk;
                $department = m_department::whereNpk($npk)->first();
                $approval = m_employee::where('sub_section', $department->code_division);

                DB::beginTransaction();

                foreach ($array_data as $value) {

                    $key		= explode(';',$value);
                    $employee   = m_employee::whereNpk(trim($key[0]))->first();

                    if ($department->code != $employee->hasSubSection->hasSection->hasDepartment->code) {

                        // hotfix-3.5.2, Ferry, 20180914
                        return ['code' => -1, 'msg' => 'data npk : ('.$key[0].') bukan dalam struktur departemen terkait. mohon dikeluarkan dari list.'];
                    }

                    $status 	= 1;
                    self::create([
                        'npk'     	=>$key[0],
                        'quota'     =>$key[1]*60,
                        'month' 	=>$key[2],
                        'status'    =>$status,
                        'requester' =>$npk,
                        'approval'  =>$approval,
                        'department_code' => $department->code
                    ]);
                }

                DB::commit();   // hotfix-3.5.2

                return ['code' => 1, 'msg' => 'Successfully Saved'];
            }
            catch (Exception $e) {

                DB::rollback(); // hotfix-3.5.2
                return ['code' => 0, 'msg' => 'Error lain : '.$e->getMessage()];
            }
        }
        else{
            return ['code' => 0, 'msg' => 'Tidak ada data!'];
        }
    }

}
