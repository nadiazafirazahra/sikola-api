<?php

namespace App\Http\Controllers\Api;

use App\Models\t_spkl;
use App\Models\m_holiday;
use App\Models\m_section;
use Carbon\Carbon;
use App\Models\m_department;
use App\Http\Requests;
use App\Models\m_sub_section;
use App\Models\t_spkl_detail;
use Illuminate\Http\Request;
use App\Http\Controllers\Api;
use Doctrine\DBAL\Schema\Index;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Input\Input;
use App\Models\m_employee;	// dev-2.2, Ferry, 20160907
use Datatables;		// dev-2.1 : Server side datatables

class AjaxApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Ajax Controller
	|--------------------------------------------------------------------------
	|
	| dev-2.0 by Ferry, 20160816, Mengatur semua terkait Ajax Request di Sikola
	|
	*/

	/**
	 * version	: dev-2.2
	 * fungsi	: Menampilkan list quota daily per MP
	 * blade 	: .blade.php
	 * @return 	: list ajax dari hasil perhitungan kuota masing-masing user
	 */
	public function getQuotaDailyList($filter_month = 'Now', $department = 'All', $sectionn = 'All')
	{
		// user authorizarion
		$user_logged_in    = Auth::user();

		switch ($user_logged_in->role) {
			case 'Leader':
				$level = config('constant.level.ldr');
				$sub_sections = $user_logged_in->hasEmployee->hasManySubSection;
				$adminSection = m_section::where('npk_admin', $user_logged_in->npk)->get();
				if ($adminSection->count() > 0) {
					foreach ($adminSection as $key => $value) {
						$sub_sections = $sub_sections->merge($value->hasManySubSection);
					}
				}

				break;

			case 'Supervisor':
				$level = config('constant.level.spv');
				$sub_sections = [];
				$sections = $user_logged_in->hasEmployee->hasManySection;
				foreach ($sections as $section) {
					foreach ($section->hasManySubSection as $value) {
						$sub_sections[] = $value;
					}
				}
				$sub_sections = collect($sub_sections);
				break;

			case 'Ka Dept':
				$level = config('constant.level.mgr');

				$sub_sections = $user_logged_in->hasEmployee->hasDepartment->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			case 'GM':
				$level = config('constant.level.gm');

				$sub_sections = $user_logged_in->hasEmployee->hasDivision->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			case 'HR Admin':
				$sub_sections = m_sub_section::get();
				break;

			default:
  return response()->json (['error' => 'PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database']);
				break;
		}

        return response()->json($sub_sections);
		// End user authorization

		// Init
		$filter_month = ($filter_month == 'Now') ? Carbon::now()->format('Y-m') : $filter_month;
		// $last_day = ($filter_month == 'Now') ? \Carbon::now()->daysInMonth :
		// 										\Carbon::parse($filter_month.'-01')->daysInMonth;

		$last_day = 31;	// ditangani di javascript saja utk visible: false
		$month_number = ($filter_month == 'Now') ? Carbon::now()->daysInMonth :
												Carbon::parse($filter_month.'-01')->format('n');
		// dapatkan list employee terkait
		$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama', 'm_employees.line_code as line_code' ,
									'm_employees.quota_used_'.$month_number.' as quota_rounded',
									'm_employees.quota_remain_'.$month_number.' as quota_rounded_2',
									'm_sub_sections.name as sub_section_name',
									'm_sections.name as section_name',
									'm_departments.name as department_name')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->whereIn('m_employees.sub_section', $sub_sections->lists('code'))
								->where('m_employees.status_emp', config('constant.employee.status.active'));

		if ($department != 'All' && $department != 'undefined') {

			$employees = $employees->where('m_departments.code', $department);
		}

		if ($sectionn != 'All' && $sectionn != 'undefined') {
			$employees = $employees->where('m_sections.code', $sectionn);
		}

		$employees = $employees->orderBy('m_employees.npk')
								->get();

		// membangun row records untuk datatable
		$spkl_coll = collect();
		$spkl_copy = $sub_sections->first()->replicate();

		foreach ($employees as $employee) {
			for ($i=1; $i <= $last_day; $i++) {
				$spkl_copy->setAttribute('day'.$i, 0);
			}

			// cari subtotal dari seluruh npk untuk seluruh tanggal dalam 1 query
			$spkls = t_spkl_detail::select('npk', 'start_date',
											DB::raw('SUM(CASE WHEN quota_ot_actual > 0 THEN quota_ot_actual ELSE quota_ot END) as sum_rounded'))
									->where('start_date', 'like', $filter_month.'%')
									->whereIn('status', config('constant.spkl.all_status'))
									->where('npk', $employee->npk_mp)
									->groupBy('npk', 'start_date')
									->orderBy('npk')
									->get();
			foreach ($spkls as $spkl) {
				$spkl_copy->{'day'.Carbon::parse($spkl->start_date)->day} = $spkl->sum_rounded;
			}

			$tgl_holiday = m_holiday::where('date_holiday', 'like', $filter_month.'%')->get();
			$arr = [];
			foreach ($tgl_holiday as $hol) {
				array_push($arr, $hol->date_holiday);
			}

			$status = array ("-1","-2","-3","-4","-5","-6","-7");

			$quota_holiday = t_spkl_detail::select(DB::raw('SUM(CASE WHEN quota_ot_actual > 0 THEN quota_ot_actual ELSE quota_ot END) as sum_holiday'))
								->whereIn('start_date', $arr)
								->whereNotIn('status', $status)
								->where('npk', $employee->npk_mp)
								->first();
			$total_quota_holiday = is_null($quota_holiday) ? 0 :  $quota_holiday->sum_holiday;
								// $jumlah = $quota_holiday->sum('sum_holiday');

			$quota_weekday = t_spkl_detail::select(DB::raw('SUM(CASE WHEN quota_ot_actual > 0 THEN quota_ot_actual ELSE quota_ot END) as sum_weekday'))
								->whereNotIn('start_date', $arr)
								->where('start_date', 'like', $filter_month.'%')
								->whereNotIn('status', $status)
								->where('npk', $employee->npk_mp)
								->first();
			$total_quota_weekday = is_null($quota_weekday) ? 0 : $quota_weekday->sum_weekday;

			$spkl_copy->npk_mp 					= $employee->npk_mp;
			$spkl_copy->name 					= $employee->nama;
			$spkl_copy->line_code 				= $employee->line_code;
			$spkl_copy->sub_section_name 		= $employee->sub_section_name;
			$spkl_copy->section_name 			= $employee->section_name;
			$spkl_copy->department_name 		= $employee->department_name;
			$spkl_copy->setAttribute('subtotal', round($total_quota_weekday / 60,2));
			$spkl_copy->setAttribute('subtotal4', round($total_quota_holiday / 60,2));
			$spkl_copy->setAttribute('subtotal2', round($employee->quota_rounded_2 / 60,2));
			$spkl_copy->setAttribute('subtotal3', round(($employee->quota_rounded_2/60)-$employee->quota_rounded,2));
			$spkl_copy->last_day_real			= Carbon::parse($filter_month)->daysInMonth;
			$spkl_copy->last_day 				= $last_day;

			$spkl_coll->push($spkl_copy);
			$spkl_copy = $spkl_copy->replicate();

		}

		// kalau gak pakai server-side
		$spkl_result['data'] = $spkl_coll;

		  return response()->json (['data' => $spkl_coll]);
		// kalau pakai server-side
		// return Datatables::of($spkl_coll)->make(true);
	}

	/**
	 * version	: dev-2.1
	 * fungsi	: Menampilkan list spkl list dengan metode ajax dan server-side datatables
	 * blade 	: spkl_list_ajax.blade.php
	 * @return 	: list ajax dari seluruh jenis SPKL
	 */
	public function getSpklList($type, $filter_month = 'Now')
	{
		// user authorizarion
		$user_logged_in = Auth::user();

		// Init
		$filter_month = ($filter_month == 'Now') ? Carbon::now()->format('Y-m') : $filter_month;

		switch ($user_logged_in->role) {
			case 'Supervisor':
				$level = config('constant.level.spv');
				break;
			case 'Ka Dept':
				$level = config('constant.level.mgr');
				break;
			case 'GM':
				$level = config('constant.level.gm');
				break;
			case 'HR Admin':
				$level = config('constant.level.hra');
				break;
			case 'General Affair':
				$level = config('constant.level.gaa');
				break;
			default:
			  return response()->json($user_logged_in)['error'] = 'PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database';
				break;
		}
		// End user authorization

		// common query
		$spkls 	= t_spkl::select('t_spkls.id_spkl as id_spkl',
									'm_departments.name as department_name',
									'm_sections.name as section_name',
									'm_categories.name as category_name',
									't_spkl_details.start_date as start_date_formatted',
									't_spkls.is_late',
									DB::raw('count(t_spkl_details.npk) as jml'))
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftjoin('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkl_details.start_date','like', $filter_month.'%')
						->groupBy('t_spkls.id_spkl')
						->orderBy('t_spkls.id', 'DESC');

		// query sedikit dimodifikasi untuk type tertentu
		switch ($type) {
			case config('constant.spkl.step.planning'):
				$spkls = $spkls->whereIn('t_spkl_details.status', config('constant.spkl.planning.approved'))
								->where('t_spkl_details.start_actual','00:00:00')
								->where('t_spkl_details.end_actual','00:00:00');
				break;
			case config('constant.spkl.step.realization'):
				$spkls = $spkls->whereIn('t_spkl_details.status', config('constant.spkl.actual.noGM'))
								->where('t_spkl_details.start_actual','!=','00:00:00')
								->where('t_spkl_details.end_actual','!=','00:00:00');
				break;
			case config('constant.spkl.step.done'):
				$spkls = $spkls->whereIn('t_spkl_details.status', config('constant.spkl.actual.done'));

				break;
			case config('constant.spkl.step.rejected'):
				$spkls = $spkls->whereIn('t_spkl_details.status',
											array_merge(array_values(config('constant.spkl.planning.rejected')),
														array_values(config('constant.spkl.actual.rejected'))) );
				break;
			default:
			   throw new \Exception('PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database');
				break;
		}

		// query sedikit dimodifikasi untuk level level tertentu
		if ( ($level == config('constant.level.hra')) || ($level == config('constant.level.gaa')) ) {
			$spkls = $spkls->get();
		}
		else {
			$spkls = $spkls->where('t_spkls.npk_'.$level, $user_logged_in->npk)->get();
		}

		// Init before loops
		$spkl_coll = collect();
		$i = 1;

		foreach ($spkls as $spkl) {
			// pemberian No Urut
			$spkl->setAttribute('rowNo', $i);

			// khusus realization and done
			if ( ($type == config('constant.spkl.step.realization')) || ($type == config('constant.spkl.step.done')) ) {
				$spkl->setAttribute('mp_realisasi', t_spkl_detail::get_total_mp_realization($spkl->id_spkl));
			}
			else {
				$spkl->setAttribute('mp_realisasi', 0);
			}

			$spkl_coll->push($spkl);
			$i++;
		}

		// kalau gak pakai server-side
		$spkl_result['data'] = $spkl_coll;
	  return response()->json($spkl_result);

		// kalau pakai server-side
		// return Datatables::of($spkl_coll)->make(true);

	}

	/**
	 * fungsi	: Menampilkan list approval level ==> level : constant.jabatan , status : constant.spkl.planning
	 * blade 	: spkl_approval_ajax_view.blade.php
	 * @return 	: list ajax dari SPKL yang butuh approval
	 */
	public function getApprovalList($type, $step)
	{
		// user authorizarion
		$user_logged_in    = Auth::user();

		switch ($user_logged_in->role) {
			case 'Supervisor':
				$level = config('constant.level.spv');

				if ($step == config('constant.spkl.step.waiting')) {
					$status_viewed = config('constant.spkl.'.$type.'.approved.ldr');
				}
				elseif ($step == config('constant.spkl.step.edit')) {
					$status_viewed = empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->npk ) ?
											( empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->hasDivision->npk ) ?
											config('constant.spkl.'.$type.'.approved.gm') :
											config('constant.spkl.'.$type.'.approved.mgr') ) :
											config('constant.spkl.'.$type.'.approved.spv');
				}
				elseif ($step == config('constant.spkl.step.rejected')) {

					// hotfix-2.2.1, Ferry, 20160920, di komen, karena reject tidak mengenal jumping approval
					// $status_viewed = empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->npk ) ?
					// 						( empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->hasDivision->npk ) ?
					// 						config('constant.spkl.'.$type.'.rejected.gm') :
					// 						config('constant.spkl.'.$type.'.rejected.mgr') ) :
					// 						config('constant.spkl.'.$type.'.rejected.spv');

					$status_viewed = config('constant.spkl.'.$type.'.rejected.spv');
				}
				break;

			case 'Ka Dept':
				$level = config('constant.level.mgr');

				if ($step == config('constant.spkl.step.waiting')) {
					$status_viewed = config('constant.spkl.'.$type.'.approved.spv');
				}
				elseif ($step == config('constant.spkl.step.edit')) {
					$status_viewed = empty( $user_logged_in->hasEmployee->hasDepartment->hasDivision->npk ) ?
											config('constant.spkl.'.$type.'.approved.gm') :
											config('constant.spkl.'.$type.'.approved.mgr');
				}
				elseif ($step == config('constant.spkl.step.rejected')) {

					// hotfix-2.2.1, Ferry, 20160920, di komen, karena reject tidak mengenal jumping approval
					// $status_viewed = empty( $user_logged_in->hasEmployee->hasDepartment->hasDivision->npk ) ?
					// 						config('constant.spkl.'.$type.'.rejected.gm') :
					// 						config('constant.spkl.'.$type.'.rejected.mgr');

					$status_viewed = config('constant.spkl.'.$type.'.rejected.mgr');
				}
				break;

			case 'GM':
				$level = config('constant.level.gm');

				if ($step == config('constant.spkl.step.waiting')) {
					$status_viewed = config('constant.spkl.'.$type.'.approved.mgr');
				}
				elseif ($step == config('constant.spkl.step.edit')) {
					$status_viewed = config('constant.spkl.'.$type.'.approved.gm');
				}
				elseif ($step == config('constant.spkl.step.rejected')) {
					$status_viewed = config('constant.spkl.'.$type.'.rejected.gm');
				}
				break;

			default:
			  throw new \Exception('PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database');
				break;
		}
		// End user authorization

		// $month = \Carbon::now()->format('n');
		// Bulan mengacu ke masing-masing spkl yg mau diproses
		$months = t_spkl::selectRaw('month(t_spkl_details.start_date) as number')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.npk_'.$level, $user_logged_in->npk)
							->where('t_spkls.is_late','!=','')
							->where ('t_spkl_details.status', $status_viewed)
							->whereRaw ('month(t_spkl_details.start_date) > 0 AND month(t_spkl_details.start_date) <= 12')
							->orderBy('t_spkl_details.start_date', 'DESC')
							->distinct()
							->get();

		// Init before loops
		$spkl_coll = collect();
		$i = 1;

		foreach ($months as $month_each) {
			$month = $month_each->number;

			$spkls = t_spkl::select('t_spkls.id_spkl',
											'm_departments.name as department_name',
											'm_categories.name as category_name',
											't_spkl_details.start_date as start_date_formatted',
											't_spkls.note as note_capitalized',
											't_spkls.is_late',
											DB::raw('max(m_employees.quota_used_'.$month.') as quota_used'),
											DB::raw('count(t_spkl_details.npk) as jml'),
											DB::raw('sum(t_spkl_details.quota_ot) as sum_hours_plan'),
											DB::raw('CONCAT_WS(\'|\', max(m_employees.quota_used_'.$month.') , m_employees.quota_remain_'.$month.' ) as max_hours_rounded'))
									->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees', 'm_employees.npk', '=', 't_spkl_details.npk' )
									->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
									->leftJoin('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
									->leftJoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftJoin('m_departments','m_departments.code','=','m_sections.code_department')
									->where('t_spkls.npk_'.$level, $user_logged_in->npk)
									->where('t_spkls.is_late','!=','')									// hotfix 1.5.5 by andre menambahkan parameter input spkl terlambat
									->where ('t_spkl_details.status', $status_viewed)
									->whereRaw ('month(t_spkl_details.start_date) = '.$month)
									->groupBy('t_spkls.id_spkl')
									->orderBy('t_spkls.created_at', 'DESC');

			// dev-2.0, Ferry, 20160824, Pengecualian untuk SPV yg akan approve actual dr SPKL Leader
			if ($type == 'actual') {
				$spkls = $spkls->where('t_spkl_details.start_actual','!=','00:00:00')
								->where('t_spkl_details.end_actual','!=','00:00:00')
								->get();
			}
			elseif ($type == 'planning') {
				$spkls = $spkls->get();
			}

			// dev-2.0, Ferry, 20160819, karena query sulit digabung semua (kolom temannya max quota tidak bisa diretur bersesuaian)
			// 			Berikut algoritma proses satu2 dengan mencari NPK yg memiliki kesesuaian max quota
			foreach ($spkls as $spkl) {
				// get npk max quota in every SPKL

				$spkl->setAttribute('rowNo', $i);

				$spkl_max_npk = t_spkl_detail::select('t_spkl_details.id_spkl', 't_spkl_details.npk', 'm_employees.nama', 'm_employees.quota_used_'.$month)
										->join('m_employees', 'm_employees.npk', '=', 't_spkl_details.npk' )
										->where ('t_spkl_details.id_spkl', $spkl->id_spkl)
										->where('m_employees.quota_used_'.$month, $spkl->quota_used)
										->first();

				$spkl->setAttribute('mp_max_hours_rounded', $spkl_max_npk->nama.', '.$spkl->max_hours_rounded);
				$spkl->setAttribute('mp_max_npk', $spkl_max_npk->npk);

				$spkl_coll->push($spkl);

				$i++;
			}
		}

		$spkl_op['data'] = $spkl_coll;

		  return response()->json ($spkl_op);
	}


	//////////////////////////////////////// COLLECTION OF METHOD POST //////////////////////////////////////////

	/**
	 * posting konfirmasi approval planning / actual ke Database
	 *
	 * @return Response
	 */
	public function postSaveApproval()
	{
		// get values dari html blade
		$input = Request::all();
		$id_spkl = $input['id_spkl'];
		$type = $input['type'];
		$type_alias = ($type == 'actual') ? 'realisasi' : $type;	// database nya pakai istilah "realisasi"
		$quota_ot = ($type == 'actual') ? 'quota_ot_actual' : 'quota_ot';	// database nya pakai istilah "quota_ot_actual"

		// user authorizarion
		$user_logged_in    = Auth::user();
		switch ($user_logged_in->role) {
			case 'Supervisor':
				$level = config('constant.level.spv');
				$status_last_rejected = empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->npk ) ?
											( empty( $user_logged_in->hasEmployee->hasSection->hasDepartment->hasDivision->npk ) ?
											config('constant.spkl.'.$type.'.rejected.gm') :
											config('constant.spkl.'.$type.'.rejected.mgr') ) :
											config('constant.spkl.'.$type.'.rejected.spv');
				$status_after_approved = config('constant.spkl.'.$type.'.approved.spv');
				break;
			case 'Ka Dept':
				$level = config('constant.level.mgr');
				$status_last_rejected = empty( $user_logged_in->hasEmployee->hasDepartment->hasDivision->npk ) ?
											config('constant.spkl.'.$type.'.rejected.gm') :
											config('constant.spkl.'.$type.'.rejected.mgr');
				$status_after_approved = config('constant.spkl.'.$type.'.approved.mgr');
				break;
			case 'GM':
				$level = config('constant.level.gm');
				$status_last_rejected = config('constant.spkl.'.$type.'.rejected.gm');
				$status_after_approved = config('constant.spkl.'.$type.'.approved.gm');
				break;

			default:
			  throw new \Exception('PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database');
				break;
		}
		// End user authorization

		$today 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('n');
		$spkls = t_spkl_detail::where('id_spkl', $id_spkl);
		// hotfix-2.0.3, Ferry, 20160829, Yang belum realisasi jangan diapprove
		if ($type == 'actual') {
			$spkls = $spkls->where('t_spkl_details.start_actual','!=','00:00:00')
							->where('t_spkl_details.end_actual','!=','00:00:00')
							->get();
		}
		elseif ($type == 'planning') {
			$spkls = $spkls->get();
		}

		foreach ($spkls as $spkl) {
			// jika status terakhir sdh terlanjur reject dirinya sendiri
			// maka algoritma pengembalian kuota dijalankan
			if ($spkl->status == $status_last_rejected) {

				$spkl->employee->{'quota_used_'.$month} += $spkl->$quota_ot;
				$spkl->employee->save();
			}

			// jika GM gak mau approve (== '') maka level=mgr langsung lompat approve GM
			if ($level == config('constant.level.mgr')) {

				if ( empty($spkl->hasSubSection->hasSection->hasDepartment->hasDivision->npk) ) {
					if ($spkl->status == config('constant.spkl.actual.done.hr') ) {
						$status_after_approved = config('constant.spkl.actual.done.hr');
					}
					else {
						$status_after_approved = config('constant.spkl.'.$type.'.approved.gm');
					}
					$spkl->{'approval_'.config('constant.level.gm').'_'.$type_alias.'_date'} = $today;
				}
				else {
					if ($spkl->status == config('constant.spkl.actual.done.hr') ) {
						$status_after_approved = config('constant.spkl.actual.done.hr');
					}
					else {
						$status_after_approved = config('constant.spkl.'.$type.'.approved.gm');
					}
				}
			}
			// jika MGR, GM gak mau approve (== '') maka level=mgr langsung lompat approve GM
			elseif ($level == config('constant.level.spv')) {

				if ( empty($spkl->hasSubSection->hasSection->hasDepartment->npk) ) {

					if ( empty($spkl->hasSubSection->hasSection->hasDepartment->hasDivision->npk) ) {
						$status_after_approved = config('constant.spkl.'.$type.'.approved.gm');
						$spkl->{'approval_'.config('constant.level.gm').'_'.$type_alias.'_date'} = $today;
					}
					else {
						$status_after_approved = config('constant.spkl.'.$type.'.approved.mgr');
						$spkl->{'approval_'.config('constant.level.mgr').'_'.$type_alias.'_date'} = $today;
					}
				}
				else {
					$status_after_approved = config('constant.spkl.'.$type.'.approved.spv');
				}
			}

		$spkl->status = $status_after_approved;
			$spkl->{'approval_'.$level.'_'.$type_alias.'_date'} = $today;
			$spkl->save();
		}

		$data['success'] = 'SPKL '.$id_spkl.' berhasil di approve di system';
	  return response()->json ($data);
	}

	/**
	 * Get list of spesial limit history
	 * @return array
	 */
	public function getSpesialLimitHistory()
	{
		$user = auth()->user();
        return response()->json($user);
	}

	/**
	 * Get list of allowed mp log
	 * @return array
	 */
	public function getAllowedMpLog()
	{
		$user = auth()->user();
	 return response()->json($user);
	}

	public function getDepartmentDivision() {
		$employee = m_employee::where('npk', auth()->user()->npk)->first();

		if ($employee) {
			$division = $employee->hasDivision;
			$department = $division->hasManyDepartment()->select('code', 'name')->get();
		} else {
			$department = 'false';
		}
  		return response()->json ($department);
	}

	public function getSectionDepartment($code) {
		$data = m_section::select('code', 'name')->where('code_department', $code)->get();
	  return response()->json ($data);
	}

	/**
	 * Get section quota detail
	 */
	public function getQuotaSectionDaily(Request $request)
	{
		$month = $request->query('month', Carbon::now()->format('Y-m'));
		$firstDay = Carbon::parse($month)->startOfMonth();
		$user = Auth::user();
		$employee = $user->hasEmployee;
		$sections = collect([]);
		switch ($user->role) {
			case config('constant.role.spv'):
				$sections = $employee->hasManySection;
				break;

			case config('constant.role.mgr'):
				$sections = $employee->hasDepartment->hasManySection;
				break;

			case config('constant.role.hra'):
				$sections = m_section::get();
				break;

			case config('constant.role.gm'):
				$departments = $employee->hasDivision->hasManyDepartment;
				foreach ($departments as $department) {
					foreach ($department->hasManySection as $section) {
						$sections->push($section);
					}
				}
				break;

			default:
				break;
		}

		$data = [];

		foreach ($sections as $section) {
			// dd($section);
			$subSections = $section->hasManySubSection->lists('code');
			$department = $section->hasDepartment;
			$intMonth = (int) $firstDay->format('m');
			$mpQuota = m_employee::select(DB::raw("SUM(quota_used_$intMonth) / 60 as total_used, SUM(quota_remain_$intMonth) /60 as total_remain"))
				->where('status_emp', config('constant.employee.status.active'))
				->whereIn('sub_section', $subSections)
				->first();

			// dd($subSections);

			$employees = m_employee::where('status_emp', config('constant.employee.status.active'))
				->whereIn('sub_section', $subSections)->get();

			$spklDetails = t_spkl_detail::where('start_date', 'LIKE', $month . '%')
				->whereIn('npk', $employees->lists('npk'))
				->get();

			$sectionData = [
				'section_code' => $section->code,
				'section_name' => $section->name,
				'department_name' => $department->name,
				'quota' => round($mpQuota->total_remain, 2),
				'quota_used' => round($mpQuota->total_used, 2),
				'quota_remain' => round($mpQuota->total_remain - $mpQuota->total_used, 2),
			];

			for ($i = 1; $i <=31; $i++) {
				$sectionData['day_' . $i] = 0;
			}

			foreach ($spklDetails as $detail) {
				$date = (int) date('d', strtotime($detail->start_date));
				$quota = $detail->quota_ot_actual > 0 ? $detail->quota_ot_actual : $detail->quota_ot;
				$sectionData['day_' . $date] = round($quota / 60, 2) + $sectionData['day_' . $date];
			}

			$data[] = $sectionData;
		}

		  return response()->json ($data);
	}

	///////////////////////////////////// COLLECTION OF METHOD PRIMITIVE ///////////////////////////////////////
}
