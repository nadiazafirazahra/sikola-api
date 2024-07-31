<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\m_employee;	// dev-1.8, Ferry, 20160630

class t_spkl_detail extends Model {

    protected $table = 't_spkl_detail';
    protected $fillable =  [
        'id',
		'id_spkl',
		'npk',
        'npk_before',
		'start_date',
		'end_date',
		'start_planning',
		'end_planning',
		'start_actual',
        'system_in',
        'system_out',
        'npk_edited',
		'date_edited',
        'end_actual',
        'ref_code',
        'notes',
		'is_closed',
		'is_clv',
        'quota_ot',
		'quota_ot_actual',
        'sub_section',
		'status',
        'kd_shift_makan',
        'kd_trans',
        'kd_shift_trans',
		'approval_1_planning_date',
		'approval_2_planning_date',
		'approval_3_planning_date',
		'approval_1_realisasi_date',
		'approval_2_realisasi_date',
		'approval_3_realisasi_date',
		'npk_leader',
		'reject_date',
        'created_at',
        'updated_at'
	];


    public function m_employee()
    {
        return $this->belongsTo(employee::class, 'npk', 'npk');
    }


    // dev-2.2, Ferry, 20160908, Formatting output of quota rounded
    public function getSumRoundedAttribute($value) {
        return round($value / 60, 2);
    }

	// dev-2.0, Ferry, 20160821, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
	public function employee()
    {
        return $this->hasOne('App\m_employee', 'npk', 'npk');
    }

    public function hasSubSection()
    {
        return $this->hasOne('App\m_sub_section', 'code', 'sub_section');
    }

	// dev-1.7, Ferry, 20160616, Menghitung total SPKL Hours untuk planning
	public static function SPKL_Plan_Hours($id_spkl, $status_spkl)
	{
	    return (round(self::where('id_spkl', $id_spkl)
	    				->where('status',$status_spkl)
	    				->sum('quota_ot') / 60, 2));
	}

	// dev-1.7, Ferry, 20160616, Menghitung total SPKL Hours untuk realisasi / actual
	public static function SPKL_Actual_Hours($id_spkl, $status_spkl)
	{
	    return (round(self::where('id_spkl', $id_spkl)
	    				->where('status',$status_spkl)
	    				->sum('quota_ot_actual') / 60, 2));
	}

	// dev-2.1, Merio, 20160903, Formatting output of OT Planning
	public function getOtPlanRoundedAttribute($value) {
        if (is_numeric($value)) {
        	return round($value / 60, 2);
        } else {
        	return $value;
        }
    }

	// dev-2.1, Merio, 20160903, Formatting output of OT Actual
	public function getOtActualRoundedAttribute($value) {
        if (is_numeric($value)) {
        	return round($value / 60, 2);
        } else {
        	return $value;
        }
    }

	// dev-1.7, Ferry, 20160616, Mencari maksimum member MP Hours dalam SPKL
	public static function max_mp_hours_spkl($id_spkl, $month, $status_spkl)
	{
		$max = self::where('id_spkl', $id_spkl)
					->where('status',$status_spkl)
						->join('m_employees', 'm_employees.npk', '=', 't_spkl_details.npk' )
						->select(
							DB::raw('max(quota_used_'.$month.') as quota_used,
									quota_remain_'.$month.' as quota_remain,
									m_employees.nama as nama_emp'))
						->first();
	    return ($max);
	}

	// dev-1.8, Ferry, 20160629, Menghitung jumlah SPKL yang pending untuk notifikasi
	public static function spkl_waiting_count($is_planning = true)
	{
		// get user role yg sedang login
		$user = Auth::user();
		$total = self::select( DB::raw('count(distinct t_spkl_details.id_spkl) as total') )
						->join('t_spkls','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->whereRaw ('month(t_spkl_details.start_date) > 0 AND month(t_spkl_details.start_date) <= 12')	// hotfix-2.2.1, Ferry, bugs startdate=0000
                        ->where('is_closed', 1);

		if ($is_planning) {

			if ($user->role == 'Supervisor') {
				$total = $total->where('status', 1)
		                        // ->where('m_sections.npk', $user->npk)		// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_1', $user->npk)
								->first();

				$total = $total->total;
			}
			elseif ($user->role == 'Ka Dept') {
				$total = $total->where('status', 2)
		                        // ->where('m_departments.npk', $user->npk)		// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_2', $user->npk)
								->first();

				$total = $total->total;
			}
			elseif ($user->role == 'GM') {
				$total = $total->where('status', 3)
		                        // ->where('m_divisions.npk', $user->npk)		// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_3', $user->npk)
								->first();

				$total = $total->total;
			}
			else {	// tampungan selain kondisi diatas
				$total = 0;
			}
		}
		else {		// realisasi : is_planning = false

			if ($user->role == 'Supervisor') {
				$total = $total->where('status', 4)
		                        // ->where('m_sections.npk', $user->npk)					// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_1', $user->npk)
		                        ->where('t_spkl_details.start_actual','!=','00:00:00')		// dev-1.9, Ferry, 20160801, Query different
								->where('t_spkl_details.end_actual','!=','00:00:00')		// dev-1.9, Ferry, 20160801, Query different
								->first();

				$total = $total->total;
			}
			elseif ($user->role == 'Ka Dept') {
				$total = $total->where('status', 5)
		                        // ->where('m_departments.npk', $user->npk)					// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_2', $user->npk)
								->first();

				$total = $total->total;
			}
			elseif ($user->role == 'GM') {
				$total = $total->where('status', 6)
		                        // ->where('m_divisions.npk', $user->npk)					// dev-1.9, Ferry, 20160801, pakai npk approver
								->where('t_spkls.npk_3', $user->npk)
								->first();

				$total = $total->total;
			}
			else {	// tampungan selain kondisi diatas
				$total = 0;
			}
		}

	    return ($total);
	}

	// dev-2.1, Ferry, 20160830, Mencari SPKL yang sudah diinput jam realisasi
	public static function get_total_mp_realization ($id_spkl)
	{
		return self::where('id_spkl', $id_spkl)
						->where('start_actual','!=','00:00:00')
						->where('end_actual','!=','00:00:00')
						->count();
	}

	// dev-2.0, Merio, 20160825, Mencari SPKL yang sudah diinput jam realisasi
	public static function search_spkl_input_realization($id_spkl)
	{
		$total = self::where('id_spkl', $id_spkl)
						->where ( function ($q) {
		                $q->where('status','4')
			                ->orWhere('status','5')
			                ->orWhere('status','6');
		                })
						->where('start_actual','!=','00:00:00')
						->where('end_actual','!=','00:00:00')
						->select(DB::raw('count(npk) as total'))
						->first();
	    return $total;
	}

	// dev-2.0, Merio, 20160825, Mencari SPKL yang sudah diinput jam realisasi
	public static function search_spkl_not_input_realization($id_spkl)
	{
		$total = self::where('id_spkl', $id_spkl)
						->where ( function ($q) {
		                $q->where('status','4')
			                ->orWhere('status','5')
			                ->orWhere('status','6');
		                })
						->where('start_actual','==','00:00:00')
						->where('end_actual','==','00:00:00')
						->select(DB::raw('count(npk)'))
						->first();
	    return ($total);
	}

	// hotfix-2.0.6, Merio, 20160831, Mencari durasi SPKL untuk report daily ot mp
	public static function getduration($npk,$day,$month)
	{
		$duration = self::where('npk', $npk)
						->where ( function ($q) {
		                $q->where('status','1')
			                ->orWhere('status','2')
			                ->orWhere('status','3')
			                ->orWhere('status','4')
			                ->orWhere('status','5')
			                ->orWhere('status','6')
			                ->orWhere('status','7')
			                ->orWhere('status','8');
		                })
		                ->where(DB::raw('month(start_date)'),''.$month.'')
		                ->where(DB::raw('day(start_date)'),''.$day.'')
						->select('quota_ot as ot_plan_rounded','quota_ot_actual as ot_actual_rounded')
						->first();
	    return ($duration);
	}

	public static function getsumquotaused($month,$year,$code)
	{
		if ($month == '1') {
			$par = "quota_used_1";
		} else if ($month == '2') {
			$par = "quota_used_2";
		} else if ($month == '3') {
			$par = "quota_used_3";
		} else if ($month == '4') {
			$par = "quota_used_4";
		} else if ($month == '5') {
			$par = "quota_used_5";
		} else if ($month == '6') {
			$par = "quota_used_6";
		} else if ($month == '7') {
			$par = "quota_used_7";
		} else if ($month == '8') {
			$par = "quota_used_8";
		} else if ($month == '9') {
			$par = "quota_used_9";
		} else if ($month == '10') {
			$par = "quota_used_10";
		} else if ($month == '11') {
			$par = "quota_used_11";
		} else if ($month == '12') {
			$par = "quota_used_12";
		}
		$total = employee::select( DB::raw('sum(m_employees.'.$par.') as actual') )
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('m_departments.code','=',$code)
						->where ( function ($q) {
		                $q->where('m_employees.occupation','OPR')
			                ->orWhere('m_employees.occupation','LDR');
		                })
		                ->where('m_employees.status_emp','1')
						->first();

	    return ($total);
	}

	public static function getlastdate($npk)
	{
		$last_date = self::select( DB::raw('max(start_date) as last_date') )
						->where('npk','=',$npk)
						->first();

	    return ($last_date);
	}

}
