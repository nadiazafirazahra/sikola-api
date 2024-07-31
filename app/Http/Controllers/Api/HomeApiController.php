<?php

namespace App\Http\Controllers\Api;

use Config;
use App\t_spkl;
use App\m_section;
use Carbon\Carbon;
use App\m_category;
use App\m_division;
use App\Models\m_employee;
use App\m_transport;
use App\Models\User;
use App\m_department;
use App\m_occupation;
use App\Http\Requests;
use App\m_sub_section;
use App\t_spkl_detail;
use Illuminate\Http\Request;
use App\Http\Controllers\Api;
use App\t_employee_attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\m_break_ot; // hotfix-1.5.21, by Merio Aji, 20160525, add master break
use App\Models\m_open_access; // hotfix-1.5.20, by Merio Aji, 20160524, add open access for overtime late

class HomeApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Home Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders your application's "dashboard" for users that
	| are authenticated. Of course, you are free to change or remove the
	| controller as you wish. It is just here to get your app started!
	|
	*/

	/**
	 * Show the application dashboard to the user.
	 *
	 * @return Response
	 */
	public function index() {
    	$m 		= Carbon::now()->format('n');
		$y 		= Carbon::now()->format('Y');
		if ($m == '1') {
			$par1 = "quota_used_1";
			$par2 = "quota_remain_1";
		} else if ($m == '2') {
			$par1 = "quota_used_2";
			$par2 = "quota_remain_2";
		} else if ($m == '3') {
			$par1 = "quota_used_3";
			$par2 = "quota_remain_3";
		} else if ($m == '4') {
			$par1 = "quota_used_4";
			$par2 = "quota_remain_4";
		} else if ($m == '5') {
			$par1 = "quota_used_5";
			$par2 = "quota_remain_5";
		} else if ($m == '6') {
			$par1 = "quota_used_6";
			$par2 = "quota_remain_6";
		} else if ($m == '7') {
			$par1 = "quota_used_7";
			$par2 = "quota_remain_7";
		} else if ($m == '8') {
			$par1 = "quota_used_8";
			$par2 = "quota_remain_8";
		} else if ($m == '9') {
			$par1 = "quota_used_9";
			$par2 = "quota_remain_9";
		} else if ($m == '10') {
			$par1 = "quota_used_10";
			$par2 = "quota_remain_10";
		} else if ($m == '11') {
			$par1 = "quota_used_11";
			$par2 = "quota_remain_11";
		} else if ($m == '12') {
			$par1 = "quota_used_12";
			$par2 = "quota_remain_12";
		}
        $user   = Auth::user();
        $graph_query = DB::select(' select
        m_departments.alias as name_department,
        m_departments.code as code_department,
        sum(m_employees.'.$par1.') as used,
        sum(m_employees.'.$par2.') as remain
        from m_employees
        join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
        join m_sections on (m_sections.code = m_sub_sections.code_section)
        join m_departments on (m_departments.code = m_sections.code_department)
        where m_employees.occupation in ("OPR","LDR")
        group by m_departments.name ');
        //dd($graph_query);
        //dd($b);
        $result_graph 	= new Collection($graph_query);

        $graph_query2 = DB::select(' select
        m_departments.alias as name_department,
        sum('.$par1.') as used,
        sum('.$par2.') as plan
        from m_employees
        join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
        join m_sections on (m_sections.code = m_sub_sections.code_section)
        join m_departments on (m_departments.code = m_sections.code_department)
        where m_employees.occupation in ("OPR","LDR")
        group by m_departments.name ');
        $d1=$graph_query2[0]->name_department;
        $d2=$graph_query2[1]->name_department;
        $d3=$graph_query2[2]->name_department;
        $d4=$graph_query2[3]->name_department;
        $d5=$graph_query2[4]->name_department;
        $d6=$graph_query2[5]->name_department;
        $d7=$graph_query2[6]->name_department;
        $d8=$graph_query2[7]->name_department;
        $d9=$graph_query2[8]->name_department;
        $d10=$graph_query2[9]->name_department;
        $d11=$graph_query2[10]->name_department;
        $d12=$graph_query2[11]->name_department;
        $d13=$graph_query2[12]->name_department;
        $d14=$graph_query2[13]->name_department;
        $d15=$graph_query2[14]->name_department;

        $u1=round($graph_query2[0]->used/60,2);
        $u2=round($graph_query2[1]->used/60,2);
        $u3=round($graph_query2[2]->used/60,2);
        $u4=round($graph_query2[3]->used/60,2);
        $u5=round($graph_query2[4]->used/60,2);
        $u6=round($graph_query2[5]->used/60,2);
        $u7=round($graph_query2[6]->used/60,2);
        $u8=round($graph_query2[7]->used/60,2);
        $u9=round($graph_query2[8]->used/60,2);
        $u10=round($graph_query2[9]->used/60,2);
        $u11=round($graph_query2[10]->used/60,2);
        $u12=round($graph_query2[11]->used/60,2);
        $u13=round($graph_query2[12]->used/60,2);
        $u14=round($graph_query2[13]->used/60,2);
        $u15=round($graph_query2[14]->used/60,2);

        $p1=round($graph_query2[0]->plan/60,2);
        $p2=round($graph_query2[1]->plan/60,2);
        $p3=round($graph_query2[2]->plan/60,2);
        $p4=round($graph_query2[3]->plan/60,2);
        $p5=round($graph_query2[4]->plan/60,2);
        $p6=round($graph_query2[5]->plan/60,2);
        $p7=round($graph_query2[6]->plan/60,2);
        $p8=round($graph_query2[7]->plan/60,2);
        $p9=round($graph_query2[8]->plan/60,2);
        $p10=round($graph_query2[9]->plan/60,2);
        $p11=round($graph_query2[10]->plan/60,2);
        $p12=round($graph_query2[11]->plan/60,2);
        $p13=round($graph_query2[12]->plan/60,2);
        $p14=round($graph_query2[13]->plan/60,2);
        $p15=round($graph_query2[14]->plan/60,2);

        if ($user->role == 'HR Admin' || $user->role == 'General Affair') {
        } else {
	        $user2 	= User::join('m_employees','m_employees.npk','=','users.npk')
	        				->where('users.npk',$user->npk)
	        				->get();
			foreach ($user2 as $user2) {
				$role 			= $user2->role;
				$sub_section 	= $user2->sub_section;
			}
		}

        $mm 	= Carbon::now()->format('m');
		$y 		= Carbon::now()->format('Y');
		if ($mm == '01') {
			$m = '1';
		} else if ($mm == '02') {
			$m = '2';
		} else if ($mm == '03') {
			$m = '3';
		} else if ($mm == '04') {
			$m = '4';
		} else if ($mm == '05') {
			$m = '5';
		} else if ($mm == '06') {
			$m = '6';
		} else if ($mm == '07') {
			$m = '7';
		} else if ($mm == '08') {
			$m = '8';
		} else if ($mm == '09') {
			$m = '9';
		} else if ($mm == '10') {
			$m = '10';
		} else if ($mm == '11') {
			$m = '11';
		} else if ($mm == '12') {
			$m = '12';
		}

		$check_limit_overtime_late = m_open_access::where('is_active','=','1')->get();
		foreach ($check_limit_overtime_late as $check_limit_overtime_late) {
			$month = $check_limit_overtime_late->month;
			$year  = $check_limit_overtime_late->year;
		}
		//hotfix-2.3.7, by Merio, 20161130, query untuk melihat mp yang mendekati over quota
		if ($user->role == 'HR Admin' || $user->role == 'General Affair') {
			$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
									'm_employees.quota_used_'.$m.' as quota_used',
									'm_employees.quota_remain_'.$m.' as quota_remain',
									'm_sub_sections.name as sub_section_name',
									'm_sections.name as section_name',
									'm_departments.name as department_name')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->join('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
			                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
									->orderBy('m_employees.npk')
									->get();
		} else {
			if ($role == "Leader") {
				$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
										'm_employees.quota_used_'.$m.' as quota_used',
										'm_employees.quota_remain_'.$m.' as quota_remain',
										'm_sub_sections.name as sub_section_name',
										'm_sections.name as section_name',
										'm_departments.name as department_name')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->where ( function ($q) {
				                			$q->where('m_employees.occupation','OPR')
				                    		->orWhere('m_employees.occupation','LDR');
				                		})
				                		->where('m_sub_sections.code','=',$sub_section)
				                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
										->orderBy('m_employees.npk')
										->get();
			} else if ($role == "Supervisor") {
				$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
										'm_employees.quota_used_'.$m.' as quota_used',
										'm_employees.quota_remain_'.$m.' as quota_remain',
										'm_sub_sections.name as sub_section_name',
										'm_sections.name as section_name',
										'm_departments.name as department_name')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->where ( function ($q) {
				                			$q->where('m_employees.occupation','OPR')
				                    		->orWhere('m_employees.occupation','LDR');
				                		})
				                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
				                		->where('m_sections.npk','=',$user->npk)
										->orderBy('m_employees.npk')
										->get();
			} else if ($role == "Ka Dept") {
				$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
										'm_employees.quota_used_'.$m.' as quota_used',
										'm_employees.quota_remain_'.$m.' as quota_remain',
										'm_sub_sections.name as sub_section_name',
										'm_sections.name as section_name',
										'm_departments.name as department_name')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->where ( function ($q) {
				                			$q->where('m_employees.occupation','OPR')
				                    		->orWhere('m_employees.occupation','LDR');
				                		})
				                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
				                		->where('m_departments.npk','=',$user->npk)
										->orderBy('m_employees.npk')
										->get();
			} else if ($role == "GM") {
				$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
										'm_employees.quota_used_'.$m.' as quota_used',
										'm_employees.quota_remain_'.$m.' as quota_remain',
										'm_sub_sections.name as sub_section_name',
										'm_sections.name as section_name',
										'm_departments.name as department_name')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->join('m_divisions','m_divisions.code','=','m_departments.code_division')
										->where ( function ($q) {
				                			$q->where('m_employees.occupation','OPR')
				                    		->orWhere('m_employees.occupation','LDR');
				                		})
				                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
				                		->where('m_divisions.code','=',$sub_section)
										->orderBy('m_employees.npk')
										->get();
			} else {
				$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama',
										'm_employees.quota_used_'.$m.' as quota_used',
										'm_employees.quota_remain_'.$m.' as quota_remain',
										'm_sub_sections.name as sub_section_name',
										'm_sections.name as section_name',
										'm_departments.name as department_name')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->join('m_divisions','m_divisions.code','=','m_departments.code_division')
										->where ( function ($q) {
				                			$q->where('m_employees.occupation','OPR')
				                    		->orWhere('m_employees.occupation','LDR');
				                		})
				                		->where('m_employees.status_emp',1) //hotfix-3.1.1. by yudo , 20170427, karyawan yang masih aktif
										->orderBy('m_employees.npk')
										->get();
			}
		}

		if ($month == $m && $year == $y) {
	        $user = Auth::user();
			if ($user->status_user == 2) {
                return response()->json('auth/logout');
			} else {
	        	if ($user->role == "Ka Dept") {
		        	return response()->json(compact('employees','result_graph','result_graph2',
		        		'd1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15',
		        		'u1','u2','u3','u4','u5','u6','u7','u8','u9','u10','u11','u12','u13','u14','u15',
		        		'p1','p2','p3','p4','p5','p6','p7','p8','p9','p10','p11','p12','p13','p14','p15',
		        		'pd1','pd2','pd3','pd4','pd5','pd6','pd7','pd8','pd9','pd10','pd11','pd12',
		        		'ud1','ud2','ud3','ud4','ud5','ud6','ud7','ud8','ud9','ud10','ud11','ud12'
		        		));
	        	} else {
		        	return response()->json(compact('employees','result_graph','result_graph2',
		        		'd1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15',
		        		'u1','u2','u3','u4','u5','u6','u7','u8','u9','u10','u11','u12','u13','u14','u15',
		        		'p1','p2','p3','p4','p5','p6','p7','p8','p9','p10','p11','p12','p13','p14','p15'));
	        	}
	        }
    	} else {
    		$queries   = DB::select('update m_open_accesses set is_active=2 where is_active=1 ');
            $result    = new Collection($queries);
            //dev-2.1, 20160902, by Merio, update open akses untuk spkl terlambat di dept head sekarang tidak perlu di set variabel
    		$npk_dept_head = User::where('role','Ka Dept')->get();
            foreach ($npk_dept_head as $npk_dept_head) {
                $new_limit_access = new m_open_access;
                $new_limit_access->npk_user  = $npk_dept_head->npk;
                $new_limit_access->limit     = "0";
                $new_limit_access->month     = $m;
                $new_limit_access->year      = $y;
                $new_limit_access->is_active = "1";
                $new_limit_access->save();
            }
            $user = Auth::user();
            if ($user->status_user == 2) {
                return response()->json('auth/logout');
            } else {
                if ($user->role == "Ka Dept") {
		        	return response()->json(compact('employees','result_graph','result_graph2',
		        		'd1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15',
		        		'u1','u2','u3','u4','u5','u6','u7','u8','u9','u10','u11','u12','u13','u14','u15',
		        		'p1','p2','p3','p4','p5','p6','p7','p8','p9','p10','p11','p12','p13','p14','p15',
		        		'pd1','pd2','pd3','pd4','pd5','pd6','pd7','pd8','pd9','pd10','pd11','pd12',
		        		'ud1','ud2','ud3','ud4','ud5','ud6','ud7','ud8','ud9','ud10','ud11','ud12'
		        		));
	        	} else {
		        	return response()->json(compact('employees','result_graph','result_graph2',
		        		'd1','d2','d3','d4','d5','d6','d7','d8','d9','d10','d11','d12','d13','d14','d15',
		        		'u1','u2','u3','u4','u5','u6','u7','u8','u9','u10','u11','u12','u13','u14','u15',
		        		'p1','p2','p3','p4','p5','p6','p7','p8','p9','p10','p11','p12','p13','p14','p15'));
	        	}
            }
    	}

	}

	public function data_graph() {
		$graph_query2 = DB::select(' select
        m_departments.name as name_department
        from m_employees
        join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
        join m_sections on (m_sections.code = m_sub_sections.code_section)
        join m_departments on (m_departments.code = m_sections.code_department)
        where m_employees.occupation in ("OPR","LDR")
        group by m_departments.name ');
        $result_graph2 	= new Collection($graph_query2);
        return response()->json($graph_query2);
	}

}
