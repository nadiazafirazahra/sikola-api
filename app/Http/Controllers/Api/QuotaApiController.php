<?php

namespace App\Http\Controllers\Api;

use Excel;
use Config;
use App\User;
use App\t_spkl;
use App\m_shift;
use App\m_holiday;
use App\m_section;
use Carbon\Carbon;
use App\m_break_ot;
use App\m_category;
use App\m_division;
use App\Models\m_employee;
use App\m_transport;
use App\Models\m_department;
use App\m_occupation;
use App\m_quota_real;
use App\m_quota_used;
use App\Http\Requests;
use App\m_open_access;
use App\m_sub_section;
use App\t_spkl_detail;
use App\Models\m_quota_department;
use Illuminate\Http\Request;
use App\Http\Controllers\Api;
use App\t_employee_attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Console\Input\Input;

class QuotaApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Quota Controller
	|--------------------------------------------------------------------------
	|
	| v3.0 by Merio, Mengatur semua terkait Quota Management
	|
	*/

	public function quota_department_view() {
		$m_department = m_department::all();
		$m_quota_department = m_quota_department::select('*' , 'm_quota_departments.id as id_primary', 'm_departments.name as department_name')
			->leftjoin('m_departments','m_departments.code','=','m_quota_departments.code_department')
			->leftjoin('m_employees','m_employees.npk','=','m_departments.npk')
			->orderby('m_quota_departments.year','DESC')
			->orderby('m_quota_departments.month','DESC')
			->get();

            $data = [
                'departments' => $m_department,
                'm_quota_department' => $m_quota_department
            ];

            return response()->json($data);
	}

	public function quota_department_create() {
		$user 	= Auth::user();
		$input 	= Request::all();

		$department_code 	= $input['department_code'];
		$month 				= $input['month'];
		$year 				= $input['year'];

		$department = m_department::where('code','=',$department_code)->get();
		foreach ($department as $department) {
			$department_name = $department->name;
		}

		$check_data = DB::select('select count(id) as jml from m_quota_departments where year = "'.$year.'"
			and month = "'.$month.'" and code_department = "'.$department_code.'" ');
        $result_check = new Collection($check_data);

        foreach ($result_check as $result_check) {
        	$jml = $result_check->jml;
        }

        if ($jml > 0) {
        	Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, quota department '.$department_name.' untuk bulan '.$month.' dan tahun '.$year.' sudah tersimpan di sistem, silakan lakukan update data untuk mengubah quota plan ');

            return response()->json([
                    'status' => 'Error',
                    'message' => 'quota department '.$department_name.' untuk bulan '.$month.' dan tahun '.$year.' sudah tersimpan di sistem, silakan lakukan update data untuk mengubah quota plan'
                ]);
        }

        $m_quota_department_save 						= new m_quota_department;
        $m_quota_department_save->code_department 		= $department_code;
        $m_quota_department_save->month 				= $month;
        $m_quota_department_save->year 					= $year;
        $m_quota_department_save->quota_plan 			= $input['quota_plan'];
        $m_quota_department_save->id_admin 				= $user->id;
        $m_quota_department_save->save();

        Session::flash('flash_type','alert-success');
	    Session::flash('flash_message','Sukses, quota department '.$department_name.' untuk bulan '.$month.' dan tahun '.$year.' berhasil disimpan di sistem');
          return response()->json([
            'status' => 'success',
            'message' => ' quota department '.$department_name.' untuk bulan '.$month.' dan tahun '.$year.' berhasil disimpan di sistem'
        ]);
	}

	public function quota_department_delete($id) {
		m_quota_department::destroy($id);
		Session::flash('flash_type','alert-success');
	    Session::flash('flash_message','Sukses, quota department berhasil dihapus');
        return response()->json([
            'status' => 'success',
            'message' => 'Quota department berhasil dihapus'
        ]);
    }


	public function quota_department_revise_hr($id) {
		$m_quota_department = m_quota_department::select('*','m_quota_departments.id as id_department')
												->join('m_departments','m_departments.code','=','m_quota_departments.code_department')
												->join('m_employees','m_employees.npk','=','m_departments.npk')
												->where('m_quota_departments.id','=',$id)
												->first();
                                                if ($m_quota_department) {
                                                    return response()->json([
                                                        'status' => 'success',
                                                        'data' => $m_quota_department
                                                    ]);
                                                } else {
                                                    return response()->json([
                                                        'status' => 'error',
                                                        'message' => 'Quota department not found'
                                                    ]);
                                                }
	}

	public function quota_department_overview_gm() {
		$user 	= Auth::user();
		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$check_division = m_employee::where('npk','=',$user->npk)->get();
		foreach ($check_division as $check_division) {
			$code_division = $check_division->sub_section;
		}

		$list_department = m_department::select('m_divisions.code as code_division','m_departments.name as nama_department','m_employees.nama as nama_dept_head',
										'm_employees.npk as npk_dept_head','m_quota_departments.month as month_quota',
										'm_quota_departments.year as year_quota','m_quota_departments.quota_plan as quota_plan',
										'm_departments.code as code_department','m_quota_departments.quota_used as quota_used')
										->join('m_divisions','m_divisions.code','=','m_departments.code_division')
										->join('m_employees','m_employees.npk','=','m_departments.npk')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_departments.code')
										->where('m_quota_departments.month','=',$month)
										->where('m_quota_departments.year','=',$year)
										->where('m_divisions.code','=',$code_division)
										->get();
                                        return response()->json([
                                            'status' => 'success',
                                            'data' => $list_department
                                        ]);

	}

	public function quota_department_revise() {
		$user 	= Auth::user();

		$input 		= Request::all();
		$id_quota 	= $input['id_quota'];
		$quota_plan = $input['quota_plan'];
		$quota_used = $input['quota_used'];

		if ($quota_plan < $quota_used) {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, Quota Plan tidak boleh lebih kecil dari total quota yang sudah di upload oleh Dept. Head');
	        return response()->json([
                'status' => 'Error',
                'message' => 'Quota Plan tidak boleh lebih kecil dari total quota yang sudah di upload oleh Dept. Head',
            ]);
		} else {
			$update 			= m_quota_department::findOrFail($id_quota);
			$update->quota_plan = $quota_plan;
			$update->save();
			Session::flash('flash_type','alert-success');
	        Session::flash('flash_message','Sukses, quota department berhasil di update ');
	        return response()->json([
                'status' => 'success',
                'message' => 'Quota department berhasil di update'
            ]);
	    }
    }

    public function quota_dept_overview() {
		$user 	= Auth::user();
		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $m_department = m_department::where('npk','=',$user->npk)->get();

		if ($user->role == "Ka Dept") {
	        $graph_query = DB::select(' select
				sum(m_employees.quota_used_1) as used_1,
				sum(m_employees.quota_remain_1) as plan_1,
				sum(m_employees.quota_used_2) as used_2,
				sum(m_employees.quota_remain_2) as plan_2,
				sum(m_employees.quota_used_3) as used_3,
				sum(m_employees.quota_remain_3) as plan_3,
				sum(m_employees.quota_used_4) as used_4,
				sum(m_employees.quota_remain_4) as plan_4,
				sum(m_employees.quota_used_5) as used_5,
				sum(m_employees.quota_remain_5) as plan_5,
				sum(m_employees.quota_used_6) as used_6,
				sum(m_employees.quota_remain_6) as plan_6,
				sum(m_employees.quota_used_7) as used_7,
				sum(m_employees.quota_remain_7) as plan_7,
				sum(m_employees.quota_used_8) as used_8,
				sum(m_employees.quota_remain_8) as plan_8,
				sum(m_employees.quota_used_9) as used_9,
				sum(m_employees.quota_remain_9) as plan_9,
				sum(m_employees.quota_used_10) as used_10,
				sum(m_employees.quota_remain_10) as plan_10,
				sum(m_employees.quota_used_11) as used_11,
				sum(m_employees.quota_remain_11) as plan_11,
				sum(m_employees.quota_used_12) as used_12,
				sum(m_employees.quota_remain_12) as plan_12,
				m_departments.alias as name_department
				FROM `m_employees`
				join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
				join m_sections on (m_sections.code = m_sub_sections.code_section)
				join m_departments on (m_departments.code = m_sections.code_department)
				where m_departments.npk = "'.$user->npk.'"
				group by m_departments.code ');

            $data = ['$pd1=round($graph_query[0]->plan_1/60,2);
	        $pd2=round($graph_query[0]->plan_2/60,2);
	        $pd3=round($graph_query[0]->plan_3/60,2);
	        $pd4=round($graph_query[0]->plan_4/60,2);
	        $pd5=round($graph_query[0]->plan_5/60,2);
	        $pd6=round($graph_query[0]->plan_6/60,2);
	        $pd7=round($graph_query[0]->plan_7/60,2);
	        $pd8=round($graph_query[0]->plan_8/60,2);
	        $pd9=round($graph_query[0]->plan_9/60,2);
	        $pd10=round($graph_query[0]->plan_10/60,2);
	        $pd11=round($graph_query[0]->plan_11/60,2);
	        $pd12=round($graph_query[0]->plan_12/60,2);

	        $ud1=round($graph_query[0]->used_1/60,2);
	        $ud2=round($graph_query[0]->used_2/60,2);
	        $ud3=round($graph_query[0]->used_3/60,2);
	        $ud4=round($graph_query[0]->used_4/60,2);
	        $ud5=round($graph_query[0]->used_5/60,2);
	        $ud6=round($graph_query[0]->used_6/60,2);
	        $ud7=round($graph_query[0]->used_7/60,2);
	        $ud8=round($graph_query[0]->used_8/60,2);
	        $ud9=round($graph_query[0]->used_9/60,2);
	        $ud10=round($graph_query[0]->used_10/60,2);
	        $ud11=round($graph_query[0]->used_11/60,2);
	        $ud12=round($graph_query[0]->used_12/60,2)'];

	        return response()->json([
                'status' => 'success',
                'data' => [
                    'department_name' => $graph_query[0]->name_department,
                    'plan' => $data['plan'],
                    'used' => $data['used']
                ]
            ], 200);
        } else {
            return response()->json([
                'status' => 'Error',
                'message' => 'User does not have the required role'
            ], 403);
	    }
    }

    //dev-3.0, 20160102, by Merio, normalisasi quota
    public function quota_normalisasi() {
    	$user 	= Auth::user();
    	if ($user->role == 'HR Admin') {
    		 return response()->json([
            'status' => 'success',
            'redirect' => 'quota/dept/overview'
        ], 200);
    } else {
        Session::flash('flash_type', 'alert-danger');
        Session::flash('flash_message', 'Error, anda tidak mempunyai akses untuk melakukan fungsi normalisasi quota');
        return response()->json([
            'status' => 'Error',
            'message' => 'Anda tidak mempunyai akses untuk melakukan fungsi normalisasi quota'
        ], 403);
    	}
    }

    public function quota_normalisasi_proses() {

    	$user 	= Auth::user();
		$input 	= Request::all();

		//pertama, mendapatkan bulan dan tahun yang akan dinormalisasi
		$month 	= $input['month'];
		$year 	= $input['year'];

		// jangan lupa ubah format bulan menjadi 1 digit untuk jan - sep, dan 2 digit untuk okt - des, menyesuaikan format field quota used di database
		if ($month == '01') {
			$m = '1';
		} else if ($month == '02') {
			$m = '2';
		} else if ($month == '03') {
			$m = '3';
		} else if ($month == '04') {
			$m = '4';
		} else if ($month == '05') {
			$m = '5';
		} else if ($month == '06') {
			$m = '6';
		} else if ($month == '07') {
			$m = '7';
		} else if ($month == '08') {
			$m = '8';
		} else if ($month == '09') {
			$m = '9';
		} else if ($month == '10') {
			$m = '10';
		} else if ($month == '11') {
			$m = '11';
		} else if ($month == '12') {
			$m = '12';
		}

		// sebelum di normalisasi, kosongkan quota used untuk semua mp
		$hapus_quota = DB::select('update m_employees
        		set quota_used_'.$m.' = "0" ');

		// cari jumlah quota planning
		$quota_planning =  DB::select('select npk, sum(quota_ot) as planning
			from t_spkl_details
			where
			id_spkl != "" and
			status in ("1","2","3","4") and
			start_actual = "00:00:00" and
			end_actual = "00:00:00" and
			start_date like "'.$year.'-'.$month.'-%"
			group by npk');
        $result_planning = new Collection($quota_planning);

        // update quota used sesuai dengan quota planning terlebih dahulu
        foreach ($result_planning as $result_planning) {
        	$npk_planning 	= $result_planning->npk;
        	$planning 		= $result_planning->planning;
        	$update_planning = DB::select('update m_employees
        		set quota_used_'.$m.' = '.$planning.'
        		where npk = '.$npk_planning.' ');
        }

        // lalu cari jumlah quota actual
        $quota_actual =  DB::select('select npk, sum(quota_ot_actual) as actual
			from t_spkl_details
			where
			id_spkl != "" and
			status in ("4","5","6","7","8") and
			(start_actual != "00:00:00" or end_actual != "00:00:00") and
			start_date like "'.$year.'-'.$month.'-%"
			group by npk');
        $result_actual = new Collection($quota_actual);

        foreach ($result_actual as $result_actual) {
        	$npk_actual 	= $result_actual->npk;
        	$actual 		= $result_actual->actual;

        	// dapatkan dahulu jumlah quota planning dengan melakukan query ke database
        	$check_quota_planning = DB::select(' select quota_used_'.$m.' as quota_used from m_employees
        	where npk = '.$npk_actual.' ');
        	$result_quota_planning = new Collection($check_quota_planning);

        	foreach ($result_quota_planning as $result_quota_planning) {
        		$quota_used = $result_quota_planning->quota_used;
        	}

        	//jumlahkan quota planning dengan quota actual, untuk mp dengan quota planning 0 maka hanya akan di dapat jumlah quota actual saja
        	$final = $quota_used+$actual;

        	// tahap terakhir, update quota used dengan variable final (hasil penjumlahan quota planning ditambah dengan quota actual)
        	$update_actual = DB::select('update m_employees
        		set quota_used_'.$m.' = '.$final.'
        		where npk = '.$npk_actual.' ');
        }

		Session::flash('flash_type','alert-success');
	    Session::flash('flash_message','Sukses, proses normalisasi quota berhasil dilakukan');
    	return response()->json([
            'status' => 'success',
            'message' => 'Proses normalisasi quota berhasil dilakukan'
        ], 200);
    }
}
