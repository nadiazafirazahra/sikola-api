<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Datatables;
use App\Models\m_holiday;
use App\Models\m_section;
use Carbon\Carbon;
use App\Models\m_employee;
use App\Models\m_department;
use Maatwebsite\Excel\Facades\Excel;
// hotfix-2.3.6, by Merio, 20161129
use App\Http\Requests;
use App\Models\m_sub_section;
use App\Models\t_spkl_detail;
use Illuminate\Http\Request;
use App\Http\Controllers\Api;
use PhpParser\Node\Stmt\Return_;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Console\Input\Input;


class ReportApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Report Controller
	|--------------------------------------------------------------------------
	|
	| dev-1.6.0 by Ferry, 20160520, Mengatur semua terkait view Report dan Generate
	| ke Excel.
	|
	*/


	///////////////////////////////////// VIEW Collections //////////////////////////////////////////
	public function rekap_makan_view()
	{
		$filter_date = Carbon::now()->toDateString();
		$filter_date_view = Carbon::now()->toFormattedDateString();

		// Normal approval manager up
		$dtMakanLembur = $this->getReportMakan($filter_date);
		$shifts = $this->getShiftMakanLembur($filter_date);

		// All lemburan yang approve dan tidak
		$dtMakanLemburAll = $this->getReportMakan($filter_date, '1');
		$shiftsAll = $this->getShiftMakanLembur($filter_date, '1');

		// hotfix-1.6.3 by Ferry, 20160621, menghitung rekap khusus PPIC

		// Normal approval manager up
		$dtMakanSection = $this->getReportMakanSection(['PP1', 'PP2', 'PP3'], $filter_date);
		$shiftsSections = $this->getShiftMakanLemburSection(['PP1', 'PP2', 'PP3'], $filter_date);

		// All lemburan yang approve dan tidak
		$dtMakanSectionAll = $this->getReportMakanSection(['PP1', 'PP2', 'PP3'], $filter_date, '1');
		$shiftsSectionAll = $this->getShiftMakanLemburSection(['PP1', 'PP2', 'PP3'], $filter_date, '1');

        $data = [
            'filter_date' => $filter_date,
            'filter_date_view' => $filter_date_view,
            'dtMakanLembur' => $dtMakanLembur,
            'shifts' => $shifts,
            'dtMakanLemburAll' => $dtMakanLemburAll,
            'shiftsAll' => $shiftsAll,
            'dtMakanSection' => $dtMakanSection,
            'shiftsSections' => $shiftsSections,
            'dtMakanSectionAll' => $dtMakanSectionAll,
            'shiftsSectionAll' => $shiftsSectionAll,
        ];

        return response()->json($data);
		// end hotfix-1.6.3
	}

	public function rekap_makan_post()
	{
		$input 	= request::all();
		if (empty($input['filter_date'])) {
            return response()->json($input);
		}

        // Input Validation : for sophisticated view of message of validator Input
        $messages = [
            // 'filter_date.required'      => 'Settlement date must not empty',
            'filter_date.date_format'   => 'Format data salah, seharusnya: dd/mm/yyyy'
        ];

        $v = Validator::make($input, [
            'filter_date'   => 'required|date_format:d/m/Y'
            ],
        $messages); // Added by Ferry, July 5th 2015 to customize required field warning input

        // Output error message ke app.blade.php
        if ($v->fails())
        {
            return response()->json('report/rekap/makan/post')->withErrors($v);
        }
        // End Input Validation

		$filter_date 	= Carbon::createFromFormat('d/m/Y', trim($input['filter_date']))->toDateString();
		$filter_date_view 	= Carbon::createFromFormat('d/m/Y', trim($input['filter_date']))->toFormattedDateString();

		// Normal approval manager up
		$dtMakanLembur = $this->getReportMakan($filter_date);
		$shifts = $this->getShiftMakanLembur($filter_date);

		// All lemburan yang approve dan tidak
		$dtMakanLemburAll = $this->getReportMakan($filter_date, '1');
		$shiftsAll = $this->getShiftMakanLembur($filter_date, '1');

		// hotfix-1.6.3 by Ferry, 20160621, menghitung rekap khusus PPIC

		// Normal approval manager up
		$dtMakanSection = $this->getReportMakanSection(['PP1', 'PP2', 'PP3'], $filter_date);
		$shiftsSections = $this->getShiftMakanLemburSection(['PP1', 'PP2', 'PP3'], $filter_date);

		// All lemburan yang approve dan tidak
		$dtMakanSectionAll = $this->getReportMakanSection(['PP1', 'PP2', 'PP3'], $filter_date, '1');
		$shiftsSectionAll = $this->getShiftMakanLemburSection(['PP1', 'PP2', 'PP3'], $filter_date, '1');

		$data = [
            'filter_date' => $filter_date,
            'filter_date_view' => $filter_date_view,
            'dtMakanLembur' => $dtMakanLembur,
            'shifts' => $shifts,
            'dtMakanLemburAll' => $dtMakanLemburAll,
            'shiftsAll' => $shiftsAll,
            'dtMakanSection' => $dtMakanSection,
            'shiftsSections' => $shiftsSections,
            'dtMakanSectionAll' => $dtMakanSectionAll,
            'shiftsSectionAll' => $shiftsSectionAll,
        ];

        return response()->json($data);
		// end hotfix-1.6.3
	}

	public function rekap_transport_view()
	{
		$filter_date = Carbon::now()->toDateString();
		$filter_date_view = Carbon::now()->toFormattedDateString();

		// Normal approval manager up
		$dtTransportLembur = $this->getReportTransport($filter_date);
		$shifts = $this->getTransportLembur($filter_date);

		// All lemburan yang approve dan tidak
		$dtTransportLemburAll = $this->getReportTransport($filter_date, '1');
		$shiftsAll = $this->getTransportLembur($filter_date, '1');

        $data = [
            'filter_date' => $filter_date,
            'filter_date_view' => $filter_date_view,
            'dtTransportLembur' => $dtTransportLembur,
            'shifts' => $shifts,
            'dtTransportLemburAll' => $dtTransportLemburAll,
            'shiftsAll' => $shiftsAll,
        ];

        return response()->json($data);
	}

	public function rekap_transport_post()
	{
		$input 	= request::all();
		if (empty($input['filter_date'])) {
			return response()->json($input);
		}

        // Input Validation : for sophisticated view of message of validator Input
        $messages = [
            // 'filter_date.required'      => 'Settlement date must not empty',
            'filter_date.date_format'   => 'Format data salah, seharusnya: dd/mm/yyyy'
        ];

        $v = Validator::make($input, [
            'filter_date'   => 'required|date_format:d/m/Y'
            ],
        $messages); // Added by Ferry, July 5th 2015 to customize required field warning input

        // Output error message ke app.blade.php
        if ($v->fails())
        {
            return response()->json('report/rekap/transport/post')->withErrors($v);
        }
        // End Input Validation

		$filter_date 	= Carbon::createFromFormat('d/m/Y', trim($input['filter_date']))->toDateString();
		$filter_date_view 	= Carbon::createFromFormat('d/m/Y', trim($input['filter_date']))->toFormattedDateString();

		// Normal approval manager up
		$dtTransportLembur = $this->getReportTransport($filter_date);
		$shifts = $this->getTransportLembur($filter_date);

		// All lemburan yang approve dan tidak
		$dtTransportLemburAll = $this->getReportTransport($filter_date, '1');
		$shiftsAll = $this->getTransportLembur($filter_date, '1');

        $data = [
            'filter_date' => $filter_date,
            'filter_date_view' => $filter_date_view,
            'dtTransportLembur' => $dtTransportLembur,
            'shifts' => $shifts,
            'dtTransportLemburAll' => $dtTransportLemburAll,
            'shiftsAll' => $shiftsAll,
        ];

        return response()->json($data);
	}

	///////////////////////////////// MODEL Query Collections //////////////////////////////////////////////

	// dev-1.6.0 by Ferry, 20160530, mendapatkan seluruh dept yang lembur
	public function getDepartmentLembur($filter_date, $min_approved = '3') {
        // $departments = t_spkl_detail::whereNotNull('kd_shift_makan')
        //                     ->where('status', '>=', $min_approved)
        //                     ->where('start_date', $filter_date)
        //                     ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
        //                     ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
        //                     ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
        //                     ->groupBy('code_department', 'm_departments.alias')
        //                     ->orderBy('m_departments.code')		// hotfix-1.6.3, Ferry, 20160620, before tspkl.id
        //                     ->select('code_department', 'm_departments.alias')
        //                     ->get();
        //hotfix-2.2.3, by Merio, 20161003, merubah order makan menjadi per section
        $departments = t_spkl_detail::whereNotNull('kd_shift_makan')
                            //dev-2.3, by Merio Aji, 20161102, menambahkan query id spkl tidak boleh kosong
                            ->where('id_spkl','!=','')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            // ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
                            ->groupBy('code_section', 'm_sections.alias')
                            ->orderBy('m_sections.code')		// hotfix-1.6.3, Ferry, 20160620, before tspkl.id
                            ->select('code_section','m_sections.name as name_section', 'm_sections.alias')
                            ->get();
		return response()->json($departments);
	}

	// dev-1.6.0 by Ferry, 20160606, mendapatkan seluruh domain shift yg lembur
	public function getShiftMakanLembur($filter_date, $min_approved = '3') {
        $shifts = t_spkl_detail::whereNotNull('kd_shift_makan')
        					//dev-2.3, by Merio Aji, 20161102, menambahkan query id spkl tidak boleh kosong
                            ->where('id_spkl','!=','')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->join('m_shifts', 'm_shifts.kode', '=', 'kd_shift_makan' )
                            ->orderBy('m_shifts.nama')
                            ->select('m_shifts.kode', 'm_shifts.nama',
                            		'tipe_hari_desc', 'time_cutoff1', 'time_cutoff2')	// hotfix-1.6.3, Ferry, 20160620
                            ->distinct()
                            ->get();

        // die($shifts);
		return response()->json ($shifts);
	}

	// dev-1.6.0 by Ferry, 20160606, mendapatkan seluruh domain shift yg lembur
	public function getCountMakanLembur($dept, $shift, $filter_date, $min_approved = '3') {
        // $tot_makan = t_spkl_detail::where('status', '>=', $min_approved)
        //                     ->where('code_department', $dept)
        //                     ->where('kd_shift_makan', $shift)
        //                     ->where('start_date', $filter_date)
        //                     ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
        //                     ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
        //                     ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
        //                     ->count();
        //hotfix-2.2.3, by Merio, 20161003, merubah order makan menjadi per section
        $tot_makan = t_spkl_detail::where('status', '>=', $min_approved)
                            ->where('code_section', $dept)
                            ->where('kd_shift_makan', $shift)
                            ->where('start_date', $filter_date)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->count();

                            return response()->json ($tot_makan);
	}

	// dev-1.6.0 by Ferry, 20160606, mendapatkan seluruh domain shift yg lembur
	public function getReportMakan($filter_date, $min_approved = '3') {
		$departments = $this->getDepartmentLembur($filter_date, $min_approved);
		$shifts = $this->getShiftMakanLembur($filter_date, $min_approved);

		// Menata ke array 2 Dimensi

		foreach ($departments as $dept) {

			$arrShiftCount = array();
			foreach ($shifts as $shift) {
				$arrShiftCount[$shift->nama] =
					$this->getCountMakanLembur($dept->code_section, $shift->kode, $filter_date, $min_approved);
			}

			$dtMakanLembur[$dept->name_section] = $arrShiftCount;
		}

		return response()->json($dtMakanLembur);
	}

	// dev-1.6.0 by Ferry, 20160610, mendapatkan seluruh dept yang lembur
	public function getDepartmentTransport($filter_date, $min_approved = '3') {
        $departments = t_spkl_detail::whereNotNull('kd_trans')
        					->whereNotNull('kd_shift_trans')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
                            ->groupBy('code_department', 'm_departments.alias')
                            ->orderBy('m_departments.code')		// hotfix-1.6.3, Ferry, 20160620, before tspkl.id
                            ->select('code_department', 'm_departments.alias')
                            ->get();

		return response()->json($departments);
	}

	// dev-1.6.0 by Ferry, 20160610, mendapatkan seluruh dept yang lembur
	public function getTransportLembur($filter_date, $min_approved = '3') {
        $shifts = t_spkl_detail::whereNotNull('kd_trans')
        					->whereNotNull('kd_shift_trans')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->join('m_shifts', 'm_shifts.kode', '=', 'kd_shift_trans' )
                            ->join('m_transports', 'm_transports.code', '=', 'kd_trans' )
                            ->orderBy('m_transports.route')
                            ->orderBy('m_shifts.nama')
                            ->select('m_transports.code', 'm_transports.route',
                            			'm_shifts.kode', 'm_shifts.nama',
                            			'm_shifts.time_in', 'm_shifts.time_out')
                            ->distinct()
                            ->get();

        // die($shifts);
		return response()->json($shifts);
	}

	// dev-1.6.0 by Ferry, 20160610, mendapatkan seluruh domain shift yg lembur
	public function getCountTransportLembur($dept, $trans_code, $trans_shift, $filter_date, $min_approved = '3') {
        $tot_makan = t_spkl_detail::where('status', '>=', $min_approved)
                            ->where('code_department', $dept)
                            ->where('kd_trans', $trans_code)
                            ->where('kd_shift_trans', $trans_shift)
                            ->where('start_date', $filter_date)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
                            ->count();

		return response()->json($tot_makan);
	}

	// dev-1.6.0 by Ferry, 20160606, mendapatkan seluruh domain shift yg lembur
	public function getReportTransport($filter_date, $min_approved = '3') {
		$departments = $this->getDepartmentTransport($filter_date, $min_approved);
		$transports = $this->getTransportLembur($filter_date, $min_approved);

		// Menata ke array 2 Dimensi

		foreach ($departments as $dept) {

			$arrTransCount = array();
			foreach ($transports as $transport) {
				$arrTransCount[$transport->nama] =
					$this->getCountTransportLembur($dept->code_department,
						$transport->code, $transport->kode, $filter_date, $min_approved);
			}

			$dtTransportLembur[$dept->code_department] = $arrTransCount;
		}

		return response()->json($dtTransportLembur);
	}

	/////////////////////// Rekap Makan khusus PPIC unit / Body ////////////////////////////////////
	// hotfix-1.6.3 by Ferry, 20160621, mendapatkan seluruh section dari Department
	public function getSectionLembur($arr_Section, $filter_date, $min_approved = '3') {
        $sections = t_spkl_detail::whereNotNull('kd_shift_makan')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->whereIn('m_sections.code', $arr_Section)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->groupBy('m_sections.code', 'm_sections.alias')
                            ->orderBy('m_sections.code')
                            ->select('m_sections.code', 'm_sections.alias')
                            ->get();

		return response()->json($sections);
	}

	// hotfix-1.6.3 by Ferry, 20160621, mendapatkan seluruh domain shift yg lembur
	public function getShiftMakanLemburSection($arr_Section, $filter_date, $min_approved = '3') {
        $shifts = t_spkl_detail::whereNotNull('kd_shift_makan')
        					//dev-2.3, by Merio Aji, 20161102, menambahkan query id spkl tidak boleh kosong
        					->where('id_spkl','!=','')
                            ->where('status', '>=', $min_approved)
                            ->where('start_date', $filter_date)
                            ->whereIn('m_sections.code', $arr_Section)
                            ->join('m_shifts', 'm_shifts.kode', '=', 'kd_shift_makan' )
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->orderBy('m_shifts.nama')
                            ->select('m_shifts.kode', 'm_shifts.nama',
                            		'tipe_hari_desc', 'time_cutoff1', 'time_cutoff2')
                            ->distinct()
                            ->get();

		return response()->json($shifts);
	}

	// hotfix-1.6.3 by Ferry, 20160621, mendapatkan seluruh domain shift yg lembur
	public function getCountMakanLemburSection($section, $shift, $filter_date, $min_approved = '3') {
        $tot_makan = t_spkl_detail::where('status', '>=', $min_approved)
                            ->where('m_sections.code', $section)
                            ->where('kd_shift_makan', $shift)
                            ->where('start_date', $filter_date)
                            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->count();

		return response()->json($tot_makan);
	}

	// hotfix-1.6.3 by Ferry, 20160621, mendapatkan seluruh domain shift yg lembur
	public function getReportMakanSection($arr_Section, $filter_date, $min_approved = '3') {
		$sections = $this->getSectionLembur($arr_Section, $filter_date, $min_approved);
		$shifts = $this->getShiftMakanLemburSection($arr_Section, $filter_date, $min_approved);

		// Menata ke array 2 Dimensi

		foreach ($sections as $section) {

			$arrShiftCount = array();
			foreach ($shifts as $shift) {
				$arrShiftCount[$shift->nama] =
					$this->getCountMakanLemburSection($section->code_section, $shift->kode, $filter_date, $min_approved);
			}

			$dtMakanLembur[$section->alias] = $arrShiftCount;
		}

		return response()->json($dtMakanLembur);
	}

	//hotfix-2.3.6, by Merio, 20161129, menambahkan report untuk melihat data quota used dan limit untuk semua mp level operator dan leader
	public function download_quota() {
		$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
								->get();
		Excel::load('/storage/template/Report_Quota.xlsx', function($file) use($data_mp){
			$a="3";
			foreach ($data_mp as $data_mp){
				$file->setActiveSheetIndex(0)->setCellValue('A'.$a.'', $data_mp->npk_mp);
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $data_mp->nama);
				$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', $data_mp->name_department);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $data_mp->name_section);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $data_mp->name_sub_section);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', round($data_mp->quota_used_1/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', round($data_mp->quota_remain_1/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', round($data_mp->quota_used_2/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', round($data_mp->quota_remain_2/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', round($data_mp->quota_used_3/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('K'.$a.'', round($data_mp->quota_remain_3/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('L'.$a.'', round($data_mp->quota_used_4/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('M'.$a.'', round($data_mp->quota_remain_4/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('N'.$a.'', round($data_mp->quota_used_5/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('O'.$a.'', round($data_mp->quota_remain_5/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('P'.$a.'', round($data_mp->quota_used_6/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Q'.$a.'', round($data_mp->quota_remain_6/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('R'.$a.'', round($data_mp->quota_used_7/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('S'.$a.'', round($data_mp->quota_remain_7/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('T'.$a.'', round($data_mp->quota_used_8/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('U'.$a.'', round($data_mp->quota_remain_8/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('V'.$a.'', round($data_mp->quota_used_9/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('W'.$a.'', round($data_mp->quota_remain_9/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('X'.$a.'', round($data_mp->quota_used_10/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Y'.$a.'', round($data_mp->quota_remain_10/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Z'.$a.'', round($data_mp->quota_used_11/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AA'.$a.'', round($data_mp->quota_remain_11/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AB'.$a.'', round($data_mp->quota_used_12/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AC'.$a.'', round($data_mp->quota_remain_12/60,2));
				$a++;
			}
		})->export('xlsx');

        return response()->json($data_mp);
	}

	public function download_ot_line($filter_month = 'Now') { //ver 3.3.0 by Ario R 20170823 --> ambil data dari quota_daily_ajax.blade, data di ajax controller
		$user_logged_in    = Auth::user();

		switch ($user_logged_in->role) {
			case 'Leader':
				$level = config('constant.level.ldr');

				$sub_sections = $user_logged_in->hasEmployee->hasManySubSection;
				break;

			case 'Supervisor':
				$level = config('constant.level.spv');

				$sub_sections = $user_logged_in->hasEmployee->hasSection->hasManySubSection;
				break;

			case 'Ka Dept':
				$level = config('constant.level.mgr');

				$sub_sections = $user_logged_in->hasEmployee->hasDepartment->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			case 'GM':
				$level = config('constant.level.gm');

				$sub_sections = $user_logged_in->hasEmployee->hasDivision->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			default:
				return $data['error'] = 'PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database';
				break;
		}

		$filter_month = ($filter_month == 'Now') ? Carbon::now()->format('Y-m') : $filter_month;
		// $last_day = ($filter_month == 'Now') ? \Carbon::now()->daysInMonth :
		// 										\Carbon::parse($filter_month.'-01')->daysInMonth;

		$last_day = 31;	// ditangani di javascript saja utk visible: false
		$month_number = ($filter_month == 'Now') ? Carbon::now()->daysInMonth :
												Carbon::parse($filter_month.'-01')->format('n');

		$data_mps = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama', 'm_employees.line_code as line_code' ,
									// 'm_employees.quota_used_7 as quota_rounded',
									// 'm_employees.quota_remain_7 as quota_rounded_2',
									'm_employees.quota_used_'.$month_number.' as quota_rounded',
									'm_employees.quota_remain_'.$month_number.' as quota_rounded_2',
									'm_sub_sections.name as sub_section_name',
									'm_sections.name as section_name',
									'm_departments.name as department_name')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								//->whereIn('m_employees.sub_section', $sub_sections->lists('code'))
								->where('m_employees.status_emp', config('constant.employee.status.active'))
								->where('m_employees.line_code','<>','')
								->whereNotIn('m_employees.line_code',['null',0])
								->orderBy('m_employees.npk')
								->get();
		Excel::load('/storage/template/Report_ot_line.xlsx', function($file) use($data_mps){
			$a="3";
			$last_day = 31;
			foreach ($data_mps as $data_mp){
				// for ($i=1; $i <= $last_day; $i++) {
				// $file->setAttribute('day'.$i, 0);
				// }
				$file->setActiveSheetIndex(0)->setCellValue('A'.$a.'', $data_mp->npk_mp);
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $data_mp->nama);
				$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', $data_mp->line_code);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $data_mp->sub_section_name);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $data_mp->section_name);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', $data_mp->department_name);
				// $file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', $data_mp->setAttribute('subtotal', $data_mp->quota_rounded));
				// $file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $data_mp->setAttribute('subtotal2', round($data_mp->quota_rounded_2 / 60,2)));
				// $file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $data_mp->setAttribute('subtotal3', round(($data_mp->quota_rounded_2/60)-$data_mp->quota_rounded,2)));
				$file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', $data_mp->quota_rounded);
				$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', round($data_mp->quota_rounded_2 / 60,2));
				$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', round(($data_mp->quota_rounded_2/60)-$data_mp->quota_rounded,2));
				$a++;
			}
		})->export('xlsx');
	}

	//hotfix-2.3.6, by Merio, 20161129, menambahkan report untuk melihat data quota used dan limit untuk semua mp level operator dan leader
	public function download_quota_filter_export(Request $request) {
		$department = $request->department;
		$startmonth = $request->startmonth;
		$endmonth = $request->endmonth;
		$section = $request->section;
		$cellLetters = ['F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC'];
		$role = auth()->user()->role;
		$npk = auth()->user()->npk;
		$sub_section = auth()->user()->hasEmployee->sub_section;


		$months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEPT', 'OCT', 'NOV', 'DEC'];

		$data_mp = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section','m_sections.alias as name_section','m_departments.alias as name_department')
					->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
					->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
					->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
					->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
					->where ( function ($q) {
						$q->where('m_employees.occupation','OPR')
						->orWhere('m_employees.occupation','LDR');
						});
		if ($role == "GM") {
			$data_mp = $data_mp->where('m_divisions.code','=',$sub_section);
			if ($department) {
				$data_mp = $data_mp->where('m_departments.code','=',$department);
			};
			if ($section) {
				$data_mp = $data_mp->where('m_sections.code','=',$section);
			};
		} else if ($role == "Ka Dept") {
			$data_mp = $data_mp->where('m_departments.npk','=',$npk);
			if ($section) {
				$data_mp = $data_mp->where('m_sections.code','=',$section);
			};
		} else if ($role == "Supervisor") {
			$data_mp = $data_mp->where('m_sections.npk','=',$npk);
		};
		$data_mp = $data_mp->where('status_emp','<>','2')->get();
		Excel::load('/storage/template/Report_Quota_R1.xlsx', function($file) use($data_mp, $startmonth, $endmonth, $cellLetters, $months){
			$header = 1;
			$x = 0;
			for($h=$startmonth; $h<=$endmonth;$h++){
				// dd($months[$h-1]);
				$file->setActiveSheetIndex(0)->setCellValue($cellLetters[$x].$header, $months[$h-1]);
				$file->setActiveSheetIndex(0)->setCellValue($cellLetters[$x].($header+1), 'USED');
				$file->setActiveSheetIndex(0)->setCellValue($cellLetters[$x+1].($header+1), 'LIMIT');
				$x+=2;
			}
			$a="3";
			foreach ($data_mp as $data_mp){
				$file->setActiveSheetIndex(0)->setCellValue('A'.$a.'', $data_mp->npk_user);
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $data_mp->nama);
				$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', $data_mp->name_department);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $data_mp->name_section);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $data_mp->name_sub_section);
				$y = 0;
				for($i = $startmonth ; $i <= $endmonth ; $i++) {
					$file->setActiveSheetIndex(0)->setCellValue($cellLetters[$y].$a.'', round($data_mp->{'quota_used_'.$i}/60,2));
					$file->setActiveSheetIndex(0)->setCellValue($cellLetters[$y+1].$a.'', round($data_mp->{'quota_remain_'.$i}/60,2));
					$y+=2;

				}
				$a++;
			}
		})->export('xlsx');
	}
	//hotfix-2.3.6, by Merio, 20161129, menambahkan report untuk melihat data quota used dan limit untuk semua mp level operator dan leader
	public function download_quota_filter($id) {
		$user = User::join('m_employees','m_employees.npk','=','users.npk')->where('users.npk',$id)->get();
		foreach ($user as $user) {
			$role 			= $user->role;
			$sub_section 	= $user->sub_section;
		}
		if ($role == "Supervisor") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
									'm_sections.name as name_section','m_sub_sections.name as name_sub_section')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
			                		->where('m_sections.npk','=',$id)
									->get();
		} else if ($role == "Ka Dept") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
		                		->where('m_departments.npk','=',$id)
								->get();
		} else if ($role == "GM") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
		                		->where('m_divisions.code','=',$sub_section)
								->get();
		}
		Excel::load('/storage/template/Report_Quota.xlsx', function($file) use($data_mp){
			$a="3";
			foreach ($data_mp as $data_mp){
				$file->setActiveSheetIndex(0)->setCellValue('A'.$a.'', $data_mp->npk_mp);
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $data_mp->nama);
				$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', $data_mp->name_department);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $data_mp->name_section);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $data_mp->name_sub_section);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', round($data_mp->quota_used_1/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', round($data_mp->quota_remain_1/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', round($data_mp->quota_used_2/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', round($data_mp->quota_remain_2/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', round($data_mp->quota_used_3/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('K'.$a.'', round($data_mp->quota_remain_3/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('L'.$a.'', round($data_mp->quota_used_4/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('M'.$a.'', round($data_mp->quota_remain_4/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('N'.$a.'', round($data_mp->quota_used_5/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('O'.$a.'', round($data_mp->quota_remain_5/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('P'.$a.'', round($data_mp->quota_used_6/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Q'.$a.'', round($data_mp->quota_remain_6/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('R'.$a.'', round($data_mp->quota_used_7/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('S'.$a.'', round($data_mp->quota_remain_7/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('T'.$a.'', round($data_mp->quota_used_8/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('U'.$a.'', round($data_mp->quota_remain_8/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('V'.$a.'', round($data_mp->quota_used_9/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('W'.$a.'', round($data_mp->quota_remain_9/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('X'.$a.'', round($data_mp->quota_used_10/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Y'.$a.'', round($data_mp->quota_remain_10/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('Z'.$a.'', round($data_mp->quota_used_11/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AA'.$a.'', round($data_mp->quota_remain_11/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AB'.$a.'', round($data_mp->quota_used_12/60,2));
				$file->setActiveSheetIndex(0)->setCellValue('AC'.$a.'', round($data_mp->quota_remain_12/60,2));
				$a++;
			}
		})->export('xlsx');
	}

	public function download_quota_add() {
		$month 	= Carbon::now()->format('n');
		$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section',
								'm_departments.code as code_dept')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
								->get();
		Excel::load('/storage/template/import_sikola.xlsx', function($file) use($data_mp,$month){
			$a="2";
			if ($month == 1) {
				$quota_used 	= "quota_used_1";
				$quota_remain 	= "quota_remain_1";
			} else if ($month == 2) {
				$quota_used 	= "quota_used_2";
				$quota_remain 	= "quota_remain_2";
			} else if ($month == 3) {
				$quota_used 	= "quota_used_3";
				$quota_remain 	= "quota_remain_3";
			} else if ($month == 4) {
				$quota_used 	= "quota_used_4";
				$quota_remain 	= "quota_remain_4";
			} else if ($month == 5) {
				$quota_used 	= "quota_used_5";
				$quota_remain 	= "quota_remain_5";
			} else if ($month == 6) {
				$quota_used 	= "quota_used_6";
				$quota_remain 	= "quota_remain_6";
			} else if ($month == 7) {
				$quota_used 	= "quota_used_7";
				$quota_remain 	= "quota_remain_7";
			} else if ($month == 8) {
				$quota_used 	= "quota_used_8";
				$quota_remain 	= "quota_remain_8";
			} else if ($month == 9) {
				$quota_used 	= "quota_used_9";
				$quota_remain 	= "quota_remain_9";
			} else if ($month == 10) {
				$quota_used 	= "quota_used_10";
				$quota_remain 	= "quota_remain_10";
			} else if ($month == 11) {
				$quota_used 	= "quota_used_11";
				$quota_remain 	= "quota_remain_11";
			} else if ($month == 12) {
				$quota_used 	= "quota_used_12";
				$quota_remain 	= "quota_remain_12";
			}
			foreach ($data_mp as $data_mp){
				if ((($data_mp->$quota_remain-$data_mp->$quota_used)/60) <= 11) {
					$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', sprintf("%06s",$data_mp->npk_mp));
					$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', round(($data_mp->$quota_remain-$data_mp->$quota_used)/60,2)+11);
					$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $month);
					$file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', $data_mp->code_dept);
					$a++;
				}
			}
		})->export('xlsx');
	}

	public function download_quota_add_filter($id) {
		$user2 	= Auth::user();
		$month 	= Carbon::now()->format('n');
		$year 	= Carbon::now()->format('Y');
		$user 	= User::join('m_employees','m_employees.npk','=','users.npk')
						->where('users.npk','=',$id)
						->get();

		$code_div=""; //hotfixes-3.0.2 , by yudo on 20170201, bugs download form quota request
		$npk_approval = ""; //hotfixes-3.0.2 , by yudo on 20170201, bugs download form quota request

		foreach ($user as $user) {
			$role 			= $user->role;
			$sub_section 	= $user->sub_section;
			$requester 		= $user->npk;
		}
		if ($role == "Supervisor") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
									'm_sections.name as name_section','m_sub_sections.name as name_sub_section',
									'm_departments.code as code_dept')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
			                		->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang aktif
			                		->where('m_sections.npk','=',$id)
									->get();
		} else if ($role == "Ka Dept") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section',
								'm_departments.code as code_dept')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
		                		->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang aktif
		                		->where('m_departments.npk','=',$id)
								->get();
		} else if ($role == "GM") {
			$data_mp = m_employee::select('*','m_employees.npk as npk_mp','m_departments.name as name_department',
								'm_sections.name as name_section','m_sub_sections.name as name_sub_section',
								'm_departments.code as code_dept')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->where ( function ($q) {
		                			$q->where('m_employees.occupation','OPR')
		                    		->orWhere('m_employees.occupation','LDR');
		                		})
		                		->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang aktif
		                		->where('m_divisions.code','=',$sub_section)
								->get();
		}
		$division = m_department::where('code',$sub_section)->get();
		foreach ($division as $division) {
			$code_div = $division->code_division;
		}
		if ($code_div == 'ADM' || $code_div == 'MKT') {
			$npk_approval = '';
		} else {
			$approval = m_employee::where('sub_section',$code_div)->get();
			foreach ($approval as $approval) {
				$npk_approval = $approval->npk;
			}
		}

		Excel::load('/storage/template/import_sikola.xlsx', function($file) use($data_mp,$month,$year,$requester,$npk_approval){
			$a="2";
			if ($month == 1) {
				$quota_used 	= "quota_used_1";
				$quota_remain 	= "quota_remain_1";
			} else if ($month == 2) {
				$quota_used 	= "quota_used_2";
				$quota_remain 	= "quota_remain_2";
			} else if ($month == 3) {
				$quota_used 	= "quota_used_3";
				$quota_remain 	= "quota_remain_3";
			} else if ($month == 4) {
				$quota_used 	= "quota_used_4";
				$quota_remain 	= "quota_remain_4";
			} else if ($month == 5) {
				$quota_used 	= "quota_used_5";
				$quota_remain 	= "quota_remain_5";
			} else if ($month == 6) {
				$quota_used 	= "quota_used_6";
				$quota_remain 	= "quota_remain_6";
			} else if ($month == 7) {
				$quota_used 	= "quota_used_7";
				$quota_remain 	= "quota_remain_7";
			} else if ($month == 8) {
				$quota_used 	= "quota_used_8";
				$quota_remain 	= "quota_remain_8";
			} else if ($month == 9) {
				$quota_used 	= "quota_used_9";
				$quota_remain 	= "quota_remain_9";
			} else if ($month == 10) {
				$quota_used 	= "quota_used_10";
				$quota_remain 	= "quota_remain_10";
			} else if ($month == 11) {
				$quota_used 	= "quota_used_11";
				$quota_remain 	= "quota_remain_11";
			} else if ($month == 12) {
				$quota_used 	= "quota_used_12";
				$quota_remain 	= "quota_remain_12";
			}
			foreach ($data_mp as $data_mp){
				if ((($data_mp->$quota_remain-$data_mp->$quota_used)/60) <= 11) {
					$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', sprintf("%06s",$data_mp->npk_mp));
					$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', round($data_mp->$quota_remain+660)/60,2);
					$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $month);
					$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $year);
					$a++;
				}
			}
		})->export('xlsx');
	}
    // End ReportController

    public function download_daily_ot(Request $request)
    {

		$filter_month = $request->query('filter_month', date('Y-m'));
		$dept = $request->query('department');
		$sect = $request->query('section');

        // user authorizarion
		$user_logged_in    = Auth::user();

		switch ($user_logged_in->role) {
			case 'Leader':
				$sub_sections = $user_logged_in->hasEmployee->hasManySubSection;
				$adminSection = m_section::where('npk_admin', $user_logged_in->npk)->get();
				if ($adminSection->count() > 0) {
					foreach ($adminSection as $value) {
						$sub_sections = $sub_sections->merge($value->hasManySubSection);
					}
				}

				break;

			case 'Supervisor':
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
				$sub_sections = $user_logged_in->hasEmployee->hasDepartment->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			case 'GM':
				$sub_sections = $user_logged_in->hasEmployee->hasDivision->hasManySubSection();	// khusus ini pakai tag () yaa, krn relationship gk murni
				break;

			case 'HR Admin':
				$sub_sections = m_sub_section::get();
				break;

			default:
				return $data['error'] = 'PERINGATAN: Anda mencoba hacking, perbuatan akan dicatat ke database';
				break;
		}
		// End user authorization

		$filter_month = $filter_month;
		$year = (int) date('Y', strtotime($filter_month));
		$month = (int) date('m', strtotime($filter_month));
		$totalDay = cal_days_in_month(CAL_GREGORIAN, $month, $year);

		$deptName = 'All';
		$sectName = 'All';
		// dapatkan list employee terkait
		$employees = m_employee::select('m_employees.npk as npk_mp', 'm_employees.nama', 'm_employees.line_code as line_code' ,
									'm_employees.quota_used_'.$month.' as quota_rounded',
									'm_employees.quota_remain_'.$month.' as quota_rounded_2',
									'm_sub_sections.name as sub_section_name',
									'm_sections.name as section_name',
									'm_departments.name as department_name')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->whereIn('m_employees.sub_section', $sub_sections->lists('code'))
								->where('m_employees.status_emp', config('constant.employee.status.active'));


		if ($dept != 'All') {
			$employees = $employees->where('m_departments.code', $dept);
			$deptName = m_department::where('code', $dept)->first()->name;
		}

		if ($sect != 'All') {
			$employees = $employees->where('m_sections.code', $sect);
			$sectName = m_section::where('code', $sect)->first()->name;
		}

		$employees = $employees->orderBy('m_employees.npk')
								->get();

		// membangun row records untuk datatable
		$list = [];

		foreach ($employees as $employee) {
			$data = [];

			// cari subtotal dari seluruh npk untuk seluruh tanggal dalam 1 query
			$spkls = t_spkl_detail::select('npk', 'start_date',
											DB::raw('SUM(CASE WHEN quota_ot_actual > 0 THEN quota_ot_actual ELSE quota_ot END) as sum_rounded'))
									->where('start_date', 'like', $filter_month.'%')
									->whereIn('status', config('constant.spkl.all_status'))
									->where('npk', $employee->npk_mp)
									->groupBy('npk', 'start_date')
									->orderBy('npk')
									->get();

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
			$total_quota_weekday = $quota_weekday->sum_weekday ? $quota_weekday->sum_weekday : 0;

			$data['NPK'] = $employee->npk_mp;
			$data['NAMA'] = $employee->nama;
			$data['DEPARTMENT'] = $employee->department_name;
			$data['QUOTA'] =  round($employee->quota_rounded_2 / 60,2) ? round($employee->quota_rounded_2 / 60,2) : '0';
			$data['QUOTA USED WEEKDAY'] = round($total_quota_weekday / 60,2) ? round($total_quota_weekday / 60,2) : '0';
			$data['QUOTA USED HOLIDAY'] = round($total_quota_holiday / 60,2) ? round($total_quota_holiday / 60,2) : '0';
			$data['QUOTA REMAIN'] =  round(($employee->quota_rounded_2/60)-$employee->quota_rounded,2) ? round(($employee->quota_rounded_2/60)-$employee->quota_rounded,2) : '0';

			for ($i = 1; $i <= $totalDay; $i++) {
				$data['TANGGAL '.$i] = '0';
			}

			foreach ($spkls as $spkl) {
				$data['TANGGAL '.Carbon::parse($spkl->start_date)->day] = $spkl->sum_rounded;
			}

			$list[] = $data;
		}


		ob_end_clean(); // this
		ob_start(); // and this

		Excel::create('Rekap_Daily_OT_' . date('F', strtotime($filter_month)) . '_' . $deptName . '_' . $sectName , function($excel) use($list) {
            $excel->sheet('Sheet 1', function($sheet) use($list) {
                $sheet->fromArray($list);
            });
        })->export('xlsx');
	}

	public function download_daily_ot_section(Request $request)
    {
		$month = $request->query('month', Carbon::now()->format('Y-m'));
		$firstDay = Carbon::parse($month)->startOfMonth();
		$user = Auth::user();
		$employee = $user->hasEmployee;
		$sections = collect([]);

		switch ($user->role) {
			case config('constant.role.spv'):
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

		$newData = array_map(function ($vals) {
			$newArr = [];
			foreach ($vals as $key => $val) {
				$newArr[strtoupper(str_replace('_', ' ', $key))] = $val;
			}

			return $newArr;
		}, $data);

		ob_end_clean(); // this
		ob_start(); // and this

		Excel::create('Rekap_Daily_OT_Section_' . date('F', strtotime($month)), function($excel) use($newData) {
            $excel->sheet('Sheet 1', function($sheet) use($newData) {
                $sheet->fromArray($newData);
            });
        })->export('xlsx');
    }


	public function download_template_holiday() {
		Excel::load('/storage/template/import_holiday.xlsx', function($file){})->export('xlsx');
	}

}
