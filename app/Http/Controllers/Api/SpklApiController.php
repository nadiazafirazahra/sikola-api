<?php

namespace App\Http\Controllers\Api;

use Excel;
use Config;
use App\Models\User;
use App\Models\t_spkl;
use App\Models\m_section;
use Carbon\Carbon;
use App\Models\m_category;
use App\Models\m_division;
use App\Models\m_employee;
use App\Models\m_transport;
use App\Models\m_department;
use App\Models\m_occupation;
use App\Models\m_quota_real;
use App\Models\m_quota_used;
use App\Http\Requests;
use App\Models\m_sub_section;
use App\Models\t_spkl_detail;
use App\Models\t_spkl_employee;
use App\Models\m_weekday_holiday;
use Illuminate\Http\Request;
use App\Http\Controllers\Api;
use App\Models\t_employee_attribute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Symfony\Component\Console\Input\Input;
use App\Models\m_spesial_limits; // dev-3.4.0, Fahrul Sudarusman, 20171211
use App\Models\t_approved_limit_spesial; // dev-3.4.0, Fahrul Sudarusman, 20171211
use App\Models\m_break_ot; // hotfix-1.5.21, by Merio Aji, 20160525, add master break
use App\Models\m_holiday; 	// dev-1.7, Merio, 20160624, add m_holiday for check type spkl
use App\Models\m_shift;	// dev-1.6.0, Ferry, 20160512, Assign shift automatically utk makan
use App\Models\m_open_access; // hotfix-1.5.20, by Merio Aji, 20160524, add open access for overtime late

class SpklApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| SPKL Controller
	|--------------------------------------------------------------------------
	|
	| v1.0 by Ferry, 20151230, Mengatur semua terkait entry SPKL baik Planning
	| atau actual.
	|
	*/

	public function spkl_on_progress_view()
	{
		$user 		  	= Auth::user();
		$sub_sections  	= m_employee::where('npk',$user->npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$spkl_op = t_spkl::select('*', 't_spkls.id_spkl as id_spkls',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.sub_section','=',$sub_section)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4');
	                			})
							->where('t_spkl_details.start_actual','=','00:00:00')
							->where('t_spkl_details.end_actual','=','00:00:00')
							->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','=','DESC')
							->get();
		$spkl_realization = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
								DB::raw('count(t_spkl_details.npk) as jml'))
								->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
								->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->where('t_spkl_details.sub_section','=',$sub_section)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6');
		                			})
								->groupBy('t_spkls.id_spkl')
								->orderBy('t_spkls.id','=','DESC')
								->get();
		$qty_realization = $spkl_realization->count();
		$spkl_download = t_spkl_detail::where('sub_section','=',$sub_section)
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
										->where('is_closed','=','1')
										->orderBy('id','=','DESC')
				                		->groupBy('id_spkl')
										->get();

                                        $data = [
                                            'spkl_op' => $spkl_op,
                                            'spkl_realization' => $spkl_realization,
                                            'qty_realization' => $qty_realization,
                                            'spkl_download' => $spkl_download,
                                        ];

                                        return response()->json($data);
	}

	public function spkl_done_view()
	{
		$user 		  	= Auth::user();
		$sub_sections  	= m_employee::where('npk',$user->npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$spkl_op = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.sub_section',$sub_section)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6');
	                			})
							->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','DESC')
							->get();
		$qty_realization 	= $spkl_op->count();
		$spkl_download 		= t_spkl_detail::where('sub_section',$sub_section)
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
											->where('is_closed','1')
											->orderBy('id','DESC')
					                		->groupBy('id_spkl')
											->get();

                                            $data = [
                                                'spkl_op' => $spkl_op,
                                                'qty_realization' => $qty_realization,
                                                'spkl_download' => $spkl_download,
                                            ];

                                            return response()->json($data);
                                        }
	public function spkl_history()
	{
		$user 		  	= Auth::user();
		$npk 		  	= $user->npk;
		$sub_sections  	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$spkl_op = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.sub_section','=',$sub_section)
							->where('t_spkl_details.status','7')
				            ->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','=','DESC')
							->get();
		$spkl_realization = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
								DB::raw('count(t_spkl_details.npk) as jml'))
								->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
								->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->where('t_spkl_details.sub_section','=',$sub_section)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6');
		                			})
								->groupBy('t_spkls.id_spkl')
								->orderBy('t_spkls.id','=','DESC')
								->get();
		$qty_realization = $spkl_realization->count();
		$spkl_download = t_spkl_detail::where('sub_section','=',$sub_section)
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
										->where('is_closed','=','1')
										->orderBy('id','=','DESC')
				                		->groupBy('id_spkl')
										->get();

                                        $data = [
                                            'spkl_op' => $spkl_op,
                                            'qty_realization' => $qty_realization,
                                            'spkl_download' => $spkl_download,
                                        ];

                                        return response()->json($data);
	}

	public function spkl_done_hrd()
	{
		$user 		  	= Auth::user();
		$npk 		  	= $user->npk;
		$sub_sections  	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$spkl_op = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.sub_section','=',$sub_section)
							->where('t_spkl_details.status','8')
				            ->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','=','DESC')
							->get();
		$spkl_realization = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
								DB::raw('count(t_spkl_details.npk) as jml'))
								->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
								->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->where('t_spkl_details.sub_section','=',$sub_section)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6');
		                			})
								->groupBy('t_spkls.id_spkl')
								->orderBy('t_spkls.id','=','DESC')
								->get();
		$qty_realization = $spkl_realization->count();
		$spkl_download = t_spkl_detail::where('sub_section','=',$sub_section)
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
										->where('is_closed','=','1')
										->orderBy('id','=','DESC')
				                		->groupBy('id_spkl')
										->get();

                                        $data = [
                                            'spkl_op' => $spkl_op,
                                            'qty_realization' => $qty_realization,
                                            'spkl_download' => $spkl_download,
                                        ];

                                        return response()->json($data);
	}

	public function spkl_reject_view()
	{
		$user 		  	= Auth::user();
		$npk 		  	= $user->npk;
		$sub_sections  	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$spkl_op = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.sub_section','=',$sub_section)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
	                			})
							->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','=','DESC')
							->get();
		$spkl_realization = t_spkl::select('*','t_spkls.id_spkl as id_spkls',
									DB::raw('count(t_spkl_details.npk) as jml'))
									->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
									->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->where('t_spkl_details.sub_section','=',$sub_section)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                			})
									->groupBy('t_spkls.id_spkl')
									->orderBy('t_spkls.id','=','DESC')
									->get();
		$qty_realization = $spkl_realization->count();
		$spkl_download = t_spkl_detail::where('sub_section','=',$sub_section)
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
										->where('is_closed','=','1')
										->orderBy('id','=','DESC')
				                		->groupBy('id_spkl')
										->get();

                                        $data = [
                                            'spkl_op' => $spkl_op,
                                            'qty_realization' => $qty_realization,
                                            'spkl_download' => $spkl_download,
                                        ];

                                        return response()->json($data);
	}

	public function spkl_actual_approval_1_view()
	{
		// $user 		  = \Auth::user();
		// $spkl_op = t_spkl::select('*','m_sub_sections.name as sub_section_name','m_sections.name as section_name',
		// 					'm_departments.name as department_name','m_categories.name as category_name',
		// 					DB::raw('count(t_spkl_details.npk) as jml'),'t_spkls.id_spkl as id_spkls')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftjoin('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
		// 					->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
		// 					->leftJoin('m_departments','m_departments.code','=','m_sections.code_department')
		// 					->where('t_spkls.npk_1',$user->npk)
		// 					->where ( function ($q) {
	 //                			$q->where('t_spkl_details.status','4');
	 //                			})
		// 					->where('t_spkl_details.start_actual','!=','00:00:00')
		// 					->where('t_spkl_details.end_actual','!=','00:00:00')
		// 					->groupBy('t_spkls.id_spkl')
		// 					->orderBy('t_spkls.id','=','DESC')
		// 					->get();
		// $type 	= "Waiting Approval";
		// return response()->json('spkl.spkl_actual_approval_1_view', compact('spkl_op','type'));

		// dev-2.0, Ferry, 20160824, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		// $level = config('constant.level.spv');
		$level = '';	// pengecualian kalau spv

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }
	public function spkl_actual_reject_1_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');

		// hotfix-2.2.1, 20160920, commented - bugs reject tidak mengenal jump process
		// if ( empty(\Auth::user()->hasEmployee->hasSection->hasDepartment->npk) ) {
		// 	if ( empty(\Auth::user()->hasEmployee->hasSection->hasDepartment->hasDivision->npk) ) {
		// 		$level = config('constant.level.gm');
		// 	}
		// 	else {
		// 		$level = config('constant.level.mgr');
		// 	}
		// }
		// else {
		// 	// $level = config('constant.level.spv');
		// 	$level = '';	// pengecualian kalau spv
		// }
		$level = '';	// pengecualian kalau spv di blade

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_actual_history_1_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');

		if ( empty(Auth::user()->hasEmployee->hasSection->hasDepartment->npk) ) {
			if ( empty(Auth::user()->hasEmployee->hasSection->hasDepartment->hasDivision->npk) ) {
				$level = config('constant.level.gm');
			}
			else {
				$level = config('constant.level.mgr');
			}
		}
		else {
			// $level = config('constant.level.spv');
			$level = '';	// pengecualian kalau spv
		}

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_actual_approval_2_view()
	{
		// dev-2.0, Ferry, 20160820, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		$level = config('constant.level.mgr');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }
	//hotfix-1.5.10, by Merio Aji, 20160403, history approval realization by ka dept
	public function spkl_actual_history_2_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');

		if ( empty(Auth::user()->hasEmployee->hasDepartment->hasDivision->npk) ) {
			$level = config('constant.level.gm');
		}
		else {
			$level = config('constant.level.mgr');
		}

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }
	//hotfix-1.5.10, by Merio Aji, 20160403, reject approval realization by ka dept
	public function spkl_actual_reject_2_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');

		// hotfix-2.2.1, 20160920, commented - bugs reject tidak mengenal jump process
		// if ( empty(\Auth::user()->hasEmployee->hasDepartment->hasDivision->npk) ) {
		// 	$level = config('constant.level.gm');
		// }
		// else {
		// 	$level = config('constant.level.mgr');
		// }
		$level = config('constant.level.mgr');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_actual_approval_3_view()
	{
		// dev-2.0, Ferry, 20160824, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }


	public function spkl_actual_history_3_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }


	public function spkl_actual_reject_3_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_approval_1_view()
	{
		// dev-2.0, Ferry, 20160820, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		// $level = config('constant.level.spv');
		$level = '';	// pengecualian kalau spv

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_reject_1_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');

		// hotfix-2.2.1, 20160920, commented - bugs reject tidak mengenal jump process
		// if ( empty(\Auth::user()->hasEmployee->hasSection->hasDepartment->npk) ) {
		// 	if ( empty(\Auth::user()->hasEmployee->hasSection->hasDepartment->hasDivision->npk) ) {
		// 		$level = config('constant.level.gm');
		// 	}
		// 	else {
		// 		$level = config('constant.level.mgr');
		// 	}
		// }
		// else {
		// 	// $level = config('constant.level.spv');
		// 	$level = '';	// pengecualian kalau spv di blade nya gk pakai angka
		// }
		$level = '';	// pengecualian kalau spv di blade nya gk pakai angka

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_history_1_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');

		if ( empty(Auth::user()->hasEmployee->hasSection->hasDepartment->npk) ) {
			if ( empty(Auth::user()->hasEmployee->hasSection->hasDepartment->hasDivision->npk) ) {
				$level = config('constant.level.gm');
			}
			else {
				$level = config('constant.level.mgr');
			}
		}
		else {
			// $level = config('constant.level.spv');
			$level = '';	// pengecualian kalau spv
		}

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_approval_2_view()
	{
		// dev-2.0, Ferry, 20160820, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		$level = config('constant.level.mgr');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
	}
	//hotfix-1.5.10, by Merio Aji, 20160403, reject planning Ka Dept
	public function spkl_planning_reject_2_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');

		// hotfix-2.2.1, 20160920, commented - bugs reject tidak mengenal jump process
		// if ( empty(\Auth::user()->hasEmployee->hasDepartment->hasDivision->npk) ) {
		// 	$level = config('constant.level.gm');
		// }
		// else {
		// 	$level = config('constant.level.mgr');
		// }
		$level = config('constant.level.mgr');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	//hotfix-1.5.10, by Merio Aji, 20160403, history approval planning Ka Dept
	public function spkl_planning_history_2_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');

		if ( empty(Auth::user()->hasEmployee->hasDepartment->hasDivision->npk) ) {
			$level = config('constant.level.gm');
		}
		else {
			$level = config('constant.level.mgr');
		}

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_approval_3_view()
	{
		// dev-2.0, Ferry, 20160820, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.waiting');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	public function spkl_planning_history_3_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.edit');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
	}

	public function spkl_planning_reject_3_view()
	{
		// dev-2.0, Ferry, 20160822, Eloquent query sebelumnya dihapus dan pindah ke AjaxController utk efisiensi
		$step = config('constant.spkl.step.rejected');
		$level = config('constant.level.gm');

        $data = [
            'step' => $step,
            'level' => $level
        ];

		return response()->json($data);
    }

	// ************* SPKL Planning Here **************** //
	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_input()
	{
		$user 		  = Auth::user();
		$sub_section  = m_employee::where('npk',$user->npk)->get();
		foreach ($sub_section as $sub_section) {
			$sub_sections = $sub_section->sub_section;
		}
		$m_employee = m_employee::whereNotIn('npk', function($q) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','0');
									})
									->where('status_emp','=','1')
									->where('sub_section',$sub_sections)
									->get();
		$m_category = m_category::all();
		$t_spkl_employee = t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
										->where('t_spkl_details.sub_section',$sub_sections)
										->where('t_spkl_details.is_closed','=',"0")
										->where('t_spkl_details.is_clv','=',"0")
										->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where sub_section = "'.$sub_sections.'"
			and is_closed = "0" and is_clv = "0"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            'm_employee' => $m_employee,
            'm_category' => $m_category,
            't_spkl_employee' => $t_spkl_employee,
            'jml' => $check_employee,
        ];

		return response()->json($data);
	}

	public function spkl_planning_clv_input()
	{
		$user 		  = Auth::user();
		$sub_section  = m_employee::select('*','m_departments.code as code_department',
									'm_sub_sections.code as code_sub_section')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->where('m_employees.npk',$user->npk)
									->get();
		foreach ($sub_section as $sub_section) {
			$code_department 	= $sub_section->code_department;
			$code_sub_section 	= $sub_section->code_sub_section;
		}

		$m_employee = m_employee::select('*','m_employees.npk as npk_user')
        							->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
        							->whereNotIn('m_employees.npk', function($q) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','1');
									})
									->where('m_employees.status_emp','=','1')
									->where('m_departments.code',$code_department)
									->get();
		$m_category 		= m_category::all();
		$t_spkl_employee 	= t_spkl_detail::select('*','t_spkl_details.sub_section as sub_sections')
										->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
										->join('m_transports','m_transports.code','=','m_employees.transport')
										->where('t_spkl_details.sub_section','=',$code_sub_section)
										->where('t_spkl_details.is_closed','=',"0")
										->where('t_spkl_details.is_clv','=',"1")
										->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			sub_section = "'.$code_sub_section.'" and is_closed = "0" and is_clv = "1" and status = 2 ');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            'm_employee' => $m_employee,
            'm_category' => $m_category,
            't_spkl_employee' => $t_spkl_employee,
            'jml' => $check_employee,
        ];

		return response()->json($data);
	}

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_delete($id, $id2)
	{
		$t_spkl_employee = t_spkl_detail::where('t_spkl_details.sub_section','=',$id2)
					->where('t_spkl_details.npk','=',$id)
					->where('t_spkl_details.is_closed','=','0')
					->where('t_spkl_details.is_clv','=','0')
					->get();
		foreach ($t_spkl_employee as $t_spkl_employee) {
			t_spkl_detail::destroy($t_spkl_employee->id);
		}
		$m_employee = m_employee::where('npk',$id)->get();
		foreach ($m_employee as $m_employee) {
			$nama = $m_employee->nama;
		}
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, '.$nama.' ('.$id.') berhasil dihapus dari List Employee');
        return response()->json([
			'status' => 'success',
            'message' => 'Employee successfully deleted from planning list'
        ]);
	}

	public function spkl_planning_delete_all($id)
	{
		$t_spkl = t_spkl::where('id_spkl','=',$id)->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkl) {
			t_spkl::destroy($t_spkl->id);
		}

		$t_spkl_detail = t_spkl_detail::where('id_spkl','=',$id)->get();
		foreach ($t_spkl_detail as $t_spkl_detail) {

			//hotfix-1.8.2
			$npk 				= $t_spkl_detail->npk;
			$quota_ot_planning 	= $t_spkl_detail->quota_ot;
			$quota_ot_actual 	= $t_spkl_detail->quota_ot_actual;

			$employee 	= m_employee::where('npk',$npk)->get();
			foreach ($employee as $employee) {
				$employee_id = $employee->id;
				if ($month_ot == "01") {
					$quota_used 	= $employee->quota_used_1;
					$quota_remain 	= $employee->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month_ot == "02") {
					$quota_used 	= $employee->quota_used_2;
					$quota_remain 	= $employee->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month_ot == "03") {
					$quota_used 	= $employee->quota_used_3;
					$quota_remain 	= $employee->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month_ot == "04") {
					$quota_used 	= $employee->quota_used_4;
					$quota_remain 	= $employee->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month_ot == "05") {
					$quota_used 	= $employee->quota_used_5;
					$quota_remain 	= $employee->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month_ot == "06") {
					$quota_used 	= $employee->quota_used_6;
					$quota_remain 	= $employee->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month_ot == "07") {
					$quota_used 	= $employee->quota_used_7;
					$quota_remain 	= $employee->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month_ot == "08") {
					$quota_used 	= $employee->quota_used_8;
					$quota_remain 	= $employee->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month_ot == "09") {
					$quota_used 	= $employee->quota_used_9;
					$quota_remain 	= $employee->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month_ot == "10") {
					$quota_used 	= $employee->quota_used_10;
					$quota_remain 	= $employee->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month_ot == "11") {
					$quota_used 	= $employee->quota_used_11;
					$quota_remain 	= $employee->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month_ot == "12") {
					$quota_used 	= $employee->quota_used_12;
					$quota_remain 	= $employee->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}
			$quota_pengurangan 				= $quota_used-$quota_ot_planning;
			$pengurangan_quota  			= m_employee::findOrFail($employee_id);
			$pengurangan_quota->$quota_par	= $quota_pengurangan;
			$pengurangan_quota->save();

			t_spkl_detail::destroy($t_spkl_detail->id);
		}
        Session::flash('flash_type','alert-success');
        Session::flash('flash_message','SPKL was successfully delete');
        return response()->json([
			'status' => 'success',
            'message' => 'SPKL was successfully deleted'
        ]);
	}

	public function spkl_planning_delete_clv($id, $id2)
	{
		$t_spkl_employee = t_spkl_detail::where('t_spkl_details.npk',$id)
					->where('t_spkl_details.sub_section',$id2)
					->where('t_spkl_details.is_closed',0)
					->where('t_spkl_details.is_clv',1)
					->get();
		foreach ($t_spkl_employee as $t_spkl_employee) {
			t_spkl_detail::destroy($t_spkl_employee->id);
		}
		$m_employee = m_employee::where('npk',$id)->get();
		foreach ($m_employee as $m_employee) {
			$nama = $m_employee->nama;
		}
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, '.$nama.' ('.$id.') berhasil dihapus dari List Employee');
        return response()->json([
            'status' => 'success',
            'message' => 'Employee successfully deleted from planning list'
        ]);
	}

	public function spkl_planning_2_delete($id, $id2, $id3)
	{
		$user 		=Auth::user();
		$t_spkl_employee = t_spkl_detail::where('t_spkl_details.sub_section','=',$id2)
					->where('t_spkl_details.npk','=',$id)
					->where('t_spkl_details.id_spkl','=',$id3)
					->get();

		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id3)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl_employee as $t_spkl_employee) {

			//hotfix-1.8.2
			$status_terakhir 	= $t_spkl_employee->status;
			$quota_ot_planning 	= $t_spkl_employee->quota_ot;
			$quota_ot_actual 	= $t_spkl_employee->quota_ot_actual;

			$employee 	= m_employee::where('npk',$id)->get();
			foreach ($employee as $employee) {
				$employee_id = $employee->id;
				if ($month_ot == "01") {
					$quota_used 	= $employee->quota_used_1;
					$quota_remain 	= $employee->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month_ot == "02") {
					$quota_used 	= $employee->quota_used_2;
					$quota_remain 	= $employee->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month_ot == "03") {
					$quota_used 	= $employee->quota_used_3;
					$quota_remain 	= $employee->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month_ot == "04") {
					$quota_used 	= $employee->quota_used_4;
					$quota_remain 	= $employee->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month_ot == "05") {
					$quota_used 	= $employee->quota_used_5;
					$quota_remain 	= $employee->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month_ot == "06") {
					$quota_used 	= $employee->quota_used_6;
					$quota_remain 	= $employee->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month_ot == "07") {
					$quota_used 	= $employee->quota_used_7;
					$quota_remain 	= $employee->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month_ot == "08") {
					$quota_used 	= $employee->quota_used_8;
					$quota_remain 	= $employee->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month_ot == "09") {
					$quota_used 	= $employee->quota_used_9;
					$quota_remain 	= $employee->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month_ot == "10") {
					$quota_used 	= $employee->quota_used_10;
					$quota_remain 	= $employee->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month_ot == "11") {
					$quota_used 	= $employee->quota_used_11;
					$quota_remain 	= $employee->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month_ot == "12") {
					$quota_used 	= $employee->quota_used_12;
					$quota_remain 	= $employee->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}
			$quota_pengurangan 				= $quota_used-$quota_ot_planning;
			$pengurangan_quota  			= m_employee::findOrFail($employee_id);
			$pengurangan_quota->$quota_par	= $quota_pengurangan;
			$pengurangan_quota->save();
			t_spkl_detail::destroy($t_spkl_employee->id);
		}
		//bila sudah tidak ada mp di spkl, maka t_spkls di delete juga
		$check_employee = DB::select('select count(npk) as jml from t_spkl_details where id_spkl = "'.$id3.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }
        if ($jml <= 0) {
        	$check_id = t_spkl::where('id_spkl','=',$id3)->get();
        	foreach ($check_id as $check_id) {
        		t_spkl::destroy($check_id->id);
        	}
        	Session::flash('flash_type','alert-success');
	        Session::flash('flash_message','SPKL was successfully delete');
	        return response()->json([
				'status' => 'success',
				'message' => 'Successfully deleted SPKL records'
			]);
        }

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee was successfully delete from List Employee Check');
        return response()->json([
			'status' => 'success',
			'message' => 'successfully deleted employee from the list'
		]);
	}

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_add_save()
	{
		$user 			= Auth::user();
		$date 			= Carbon::now()->format('Ymd');
		$bulan_aktif 	= Carbon::now()->format('m');
		$input 			= request::all();

		// hotfix-3.5.6, 20190905, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimePlanning = $today . ' ' . $input['start_time_planning'];
		$endTimePlanning = $today . ' ' . $input['end_time_planning'];

		if (strtotime($input['start_time_planning']) > strtotime($input['end_time_planning'])) {
			$endTimePlanning = date('Y-m-d H:i', strtotime($endTimePlanning . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimePlanning) - strtotime($startTimePlanning)) / 60,2);

		if ($inMinute < 30) {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}

		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_time_planning'] == $input['end_time_planning']) {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
            return response()->json([
				'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}
		if ($input['npk'] == "a"){
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, data overtime harus diisi dengan lengkap, silakan ulangi proses!');
            return response()->json([
				'status' => 'Error',
                'message' => 'data overtime harus diisi dengan lengkap, silahkan ulangi proses!'
            ]);
		}
		//metode block berdasarkan limit quota per mp saat add employee
		$check_quota_limit	= m_employee::where('npk',$input['npk'])->get();
		foreach ($check_quota_limit as $check_quota_limit) {
			$sub_section_code 	= $check_quota_limit->sub_section;
			$nama 				= $check_quota_limit->nama;
			if ($bulan_aktif == "01") {
				$quota_used 	= $check_quota_limit->quota_used_1;
				$quota_remain 	= $check_quota_limit->quota_remain_1;
			} else if ($bulan_aktif == "02") {
				$quota_used 	= $check_quota_limit->quota_used_2;
				$quota_remain 	= $check_quota_limit->quota_remain_2;
			} else if ($bulan_aktif == "03") {
				$quota_used 	= $check_quota_limit->quota_used_3;
				$quota_remain 	= $check_quota_limit->quota_remain_3;
			} else if ($bulan_aktif == "04") {
				$quota_used 	= $check_quota_limit->quota_used_4;
				$quota_remain 	= $check_quota_limit->quota_remain_4;
			} else if ($bulan_aktif == "05") {
				$quota_used 	= $check_quota_limit->quota_used_5;
				$quota_remain 	= $check_quota_limit->quota_remain_5;
			} else if ($bulan_aktif == "06") {
				$quota_used 	= $check_quota_limit->quota_used_6;
				$quota_remain 	= $check_quota_limit->quota_remain_6;
			} else if ($bulan_aktif == "07") {
				$quota_used 	= $check_quota_limit->quota_used_7;
				$quota_remain 	= $check_quota_limit->quota_remain_7;
			} else if ($bulan_aktif == "08") {
				$quota_used 	= $check_quota_limit->quota_used_8;
				$quota_remain 	= $check_quota_limit->quota_remain_8;
			} else if ($bulan_aktif == "09") {
				$quota_used 	= $check_quota_limit->quota_used_9;
				$quota_remain 	= $check_quota_limit->quota_remain_9;
			} else if ($bulan_aktif == "10") {
				$quota_used 	= $check_quota_limit->quota_used_10;
				$quota_remain 	= $check_quota_limit->quota_remain_10;
			} else if ($bulan_aktif == "11") {
				$quota_used 	= $check_quota_limit->quota_used_11;
				$quota_remain 	= $check_quota_limit->quota_remain_11;
			} else if ($bulan_aktif == "12") {
				$quota_used 	= $check_quota_limit->quota_used_12;
				$quota_remain 	= $check_quota_limit->quota_remain_12;
			}
		}

		$check_employee = DB::select('select count(npk) as jml from t_spkl_details where npk = "'.$input['npk'].'"
			and is_closed = "0" and is_clv = "0" ');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        if ($jml != "0") {
        	Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, karyawan sudah dimasukkan ke dalam List Employee');
            return response()->json([
                'status' => 'Error',
                'message' => 'karyawan sudah dimasukkan ke dalam List Employee'
            ]);
        }

        $check_employee3 = DB::select('select count(npk) as jml from t_spkl_details where is_closed = "0"
        	and sub_section = "'.$sub_section_code.'" and is_clv = "0" ');
        $check_employee4 = new Collection($check_employee3);
        foreach ($check_employee4 as $check_employee4) {
        	$jml2 = $check_employee4->jml;
        }
        //hotfix-2.2.8, by Merio, 20161025, menghapus role satu spkl maksimal 25 MP
        // if ($jml2 >= "25") {
        // 	\Session::flash('flash_type','alert-danger');
	       //  \Session::flash('flash_message','Error, jumlah karyawan dalam 1 SPKL tidak boleh melebihi 25 MP');
	       //  return response()->json('spkl_planning/input');
        // }
        $check_sub_section2 = m_sub_section::select('m_sections.code as section_code','m_sections.npk as npk_section',
        									'm_sections.code_department as department_code')
        									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
        									->where('m_sub_sections.code','=',$sub_section_code)->get();
       	foreach ($check_sub_section2 as $check_sub_section2) {
       		$code_section 		= $check_sub_section2->section_code;
       		$npk_section 		= $check_sub_section2->npk_section;
       		$code_department 	= $check_sub_section2->department_code;
       	}
       	//bypass status
       	$status = 1;
       	if ($npk_section == "") {
       		$status = $status+1;
       		$check_department = m_department::where('code','=',$code_department)->get();
       		foreach ($check_department as $check_department) {
	       		$npk_department 	= $check_department->npk;
	       		$code_division 		= $check_department->code_division;
       		}
       		if ($npk_department == "") {
       			$status = $status+1;
       			$check_division = m_division::where('code','=',$code_division)->get();
	       		foreach ($check_division as $check_division) {
		       		$npk_division 	= $check_division->npk;
	       		}
	       		if ($npk_division == "") {
	       			$status = $status+1;
	       		} else {
	       			$status = $status;
	       		}
       		} else {
       			$status = $status;
       		}
       	} else {
       		$status = $status;
       	}

		$start_time_planning	= $input['start_time_planning'];
		$end_time_planning		= $input['end_time_planning'];
		$start_time_planning_2 	= substr($start_time_planning, -3, 1);
		$end_time_planning_2 	= substr($end_time_planning, -3, 1);
		if ($start_time_planning_2 != ":" || $end_time_planning_2 != ":") {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, format start time/end time salah, format = Jam:Menit, contoh = 16:20, silakan ulangi proses!');
	        return response()->json([
				'status' => 'Error',
                'message' => 'format start time/end time salah, format - Jam:Menit, contoh = 16:20, silakan ulangi proses!'
            ]);
		}

		$start_2  = strlen($start_time_planning);
		$end_2    = strlen($end_time_planning);
		if ($start_2 != "5" || $end_2 != "5") {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, format start time/end time salah, format = Jam:Menit, contoh = 16:20, silakan ulangi proses');
	        return response()->json([
				'status' => 'Error',
                'message' => 'format start time/end time salah, format = Jam:Menit, contoh = 16:20, silakan ulangi proses!'
            ]);
		}

		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$start     = date('H:i',strtotime($start_time_planning));
		$end       = date('H:i',strtotime($end_time_planning));
		// $diff 			= date_diff($end_time-$start_time);
		// $diff 			= $date1->diffInMinutes($date2);
		$start_time 		= Carbon::createFromFormat('H:i', $start_time_planning);
		$end_time 			= Carbon::createFromFormat('H:i', $end_time_planning);
		$hasil_selisih	    = $start_time->diffInMinutes($end_time);

		$quota = m_employee::where('npk', $input['npk'])->first();
		$jumlahkan_quota = $quota->{'quota_used_'.$month} + $hasil_selisih;

		// if ($hasil_selisih > ) {
		// 	// code...
		// }
		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$t_spkl_planning 					= new t_spkl_detail;
		$t_spkl_planning->npk 				= $input['npk'];
		$t_spkl_planning->start_planning	= $start_time_planning;
		$t_spkl_planning->end_planning		= $end_time_planning;
		$t_spkl_planning->is_closed			= "0";
		$t_spkl_planning->is_clv			= "0";
		$t_spkl_planning->sub_section		= $sub_section_code;
		$t_spkl_planning->status			= $status;
		$t_spkl_planning->notes				= $input['notes'];
		$t_spkl_planning->ref_code 			= $input['ref_no'];

		$t_spkl_planning->npk_leader = $user->npk;
		$t_spkl_planning->save();

				Session::flash('flash_type','alert-success');
	        	Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');
	        	return response()->json([
                    'status' => 'success',
                    'message' => 'data berhasil disimpan'
                ]);

		//dev-3.4.0, by Fahrul Sudarusman, 20171211, validasi quota spesial limit
		// $npk_spkl = m_employee::where('npk',$input['npk'])->first();

		// if ($npk_spkl) {

		// 	//mencari sub section
		// 	$div_code = $npk_spkl->hasSubSection->hasSection->hasDepartment->hasDivision->code;

		// 	//menemukan quota limit GM
		// 	// $special_limit = m_spesial_limits::where('sub_section', $div_code)->pluck('quota_limit');

		// 	// $cek_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->first();

		// 	//delete npk ketika sudah ditambah ke tabel approved
		// 	// $delete_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->delete();

		// 	// //menghitung selisih waktu
		// 	$start_date_planning = $input['start_date_planning'];
		// 	$end_date_planning   = $input['start_date_planning'];

		// 	$carbon_start_dt 		   = Carbon::parse($start_date_planning.' '.$t_spkl_planning->start_planning);
		// 	$carbon_end_dt 			   = Carbon::parse($end_date_planning.' '.$t_spkl_planning->end_planning);

		// 	$t_spkl_planning->end_date = $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
		// 									$carbon_end_dt->addDay()->toDateString();
		// 	$end_date_planning2		   = $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
		// 									$carbon_end_dt->addDay()->toDateString();
		// 	// end dev-1.6.0
		// 	$date1 				= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
		// 	$date2 				= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
		// 	$hasil_selisih	    = $date1->diffInMinutes($date2);

		// 	if(($npk_spkl->{'quota_used_'.$month}+$hasil_selisih > $special_limit) && (!$cek_approved)){				$special_limit = $special_limit/60;
		// 		\Session::flash('flash_type','alert-danger');
		// 	    \Session::flash('flash_message','Error, Quota anda sudah melebihi '.$special_limit.' jam parameter, silakan hubungi GM untuk membuka akses membuat SPKL');
		// 	    return response()->json('spkl_planning/input');
		// 	}elseif(($npk_spkl->{'quota_used_'.$month}+$hasil_selisih > $special_limit) && ($cek_approved)){
		// 	//hotfix-2.2.6, by Merio, 20161017, untuk mencari tahu siapa leader yang menambahkan mp tersebut
		// 		$t_spkl_planning->npk_leader = $user->npk;

		// 		$t_spkl_planning->save();
		// 		$delete_approved;

		// 		\Session::flash('flash_type','alert-success');
	 //        	\Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');
	 //        	return response()->json('spkl_planning/input');
		// 	}else{
		// 		$t_spkl_planning->npk_leader = $user->npk;
		// 		$t_spkl_planning->save();

		// 		\Session::flash('flash_type','alert-success');
	 //        	\Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');
	 //        	return response()->json('spkl_planning/input');
		// 	}
		// }
    }

    //v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_add_employee_save()
	{
		$user 			= Auth::user();
		$input 			= request::all();
		$date 			= Carbon::now()->format('Ymd');
		$jam_server2 	= Carbon::now()->format('Hi');

		$npk 			= $input['npk'];
		$id_spkl 		= $input['id_spkl'];
		//jumlah karyawan di spkl tidak boleh melebihi 25 mp
		$check_employee3 = DB::select('select count(npk) as jml from t_spkl_details where id_spkl = "'.$id_spkl.'" ');
        $check_employee4 = new Collection($check_employee3);
        foreach ($check_employee4 as $check_employee4) {
        	$jml2 = $check_employee4->jml;
		}

		// hotfix-3.7.4, 20200916, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimeActual = $today . ' ' . $input['start_time_planning'];
		$endTimeActual = $today . ' ' . $input['end_time_planning'];

		if (strtotime($input['start_time_planning']) > strtotime($input['end_time_planning'])) {
			$endTimeActual = date('Y-m-d H:i', strtotime($endTimeActual . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimeActual) - strtotime($startTimeActual)) / 60,2);

		if ($inMinute < 30) {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
            return response()->json([
				'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}

        //hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_time_planning'] == $input['end_time_planning']) {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
            return response()->json([
				'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}
		//hotfix-2.2.8, by Merio, 20161025, menghapus role tidak boleh dalam satu spkl lebih dari 25 MP
        // if ($jml2 >= "25") {
        // 	\Session::flash('flash_type','alert-danger');
	       //  \Session::flash('flash_message','Error, jumlah karyawan dalam 1 SPKL tidak boleh melebihi 25 MP');
	       //  return response()->json('spkl/planning/view/search_result/'.$id_spkl.'');
        // }
        //agar tidak ada data duplicate
        $check_employee = DB::select('select count(npk) as jml from t_spkl_details where npk = "'.$npk.'"
			and id_spkl = "'.$id_spkl.'" ');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }
        if ($jml != "0") {
        	Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, karyawan yang anda pilih sudah dimasukkan ke dalam system');
            return response()->json([
				'status' => 'Error',
                'message' => 'karyawan yang anda pilih sudah dimasukkan ke dalam system'
            ]);
        }

        $start_time_planning	= $input['start_time_planning'];
		$end_time_planning		= $input['end_time_planning'];
		$start_time_planning_2 	= substr($start_time_planning, -3, 1);
		$end_time_planning_2 	= substr($end_time_planning, -3, 1);

		if ($start_time_planning_2 != ":" || $end_time_planning_2 != ":") {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses');
            return response()->json([
				'status' => 'Error',
                'message' => 'format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses'
            ]);
		}

		$start_2  = strlen($start_time_planning);
		$end_2    = strlen($end_time_planning);
		if ($start_2 != "5" || $end_2 != "5") {
			Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses');
            return response()->json([
				'status' => 'Error',
                'message' => 'format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses'
            ]);
		}
		$check_tanggal 		= t_spkl_detail::where('id_spkl','=',$id_spkl)->get();
		foreach ($check_tanggal as $check_tanggal) {
			$start_date_ot_planning		= Carbon::parse($check_tanggal->start_date)->format('Ymd');
		}
		$bulan_aktif = Carbon::parse($start_date_ot_planning)->format('n');
		if ($start_date_ot_planning < $date) {
			Session::flash('flash_type','alert-danger');
		    Session::flash('flash_message','Error, anda tidak dapat menambahkan karyawan untuk SPKL di masa lampau');
            return response()->json([
				'status' => 'Error',
                'message' => 'anda tidak dapat menambahkan karyawan untuk SPKL di masa lampau'
            ]);
		} else if ($start_date_ot_planning == $date) {
			//batas waktu penginputan SPKL untuk tanggal sekarang
			$check_department_user = m_employee::select('*','m_departments.code as name_department','m_sections.code as name_section')
											->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->join('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$user->npk)->get();
			foreach ($check_department_user as $hasil_department) {
				$department_name = $hasil_department->name_department;
				$section_name = $hasil_department->name_section;
			}
			$open  = "0600";
			if ($department_name == "EGB" || $department_name == "EGU" || $department_name == "ITD" ||
				$department_name == "MTE" || $department_name == "QAS" || $department_name == "QBC" || $department_name == "QEC"
				|| $department_name == "OMC") {
				$closed  = "1600";
			} else {
				$closed  = "1500";
			}
			if (($jam_server2 < $open || $jam_server2 > $closed) && $user->ot_par == "1" && $user->limit_mp <= 0) {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, anda tidak dapat menambahkan karyawan untuk SPKL di hari ini melebihi batas waktu pembuatan SPKL, yaitu jam 06:00 sampai 15:00 WIB, silakan hubungi Dept Head untuk pembukaan akses overtime terlambat');
                return response()->json([
					'status' => 'Error',
                    'message' => 'anda tidak dapat menambahkan karyawan untuk SPKL di hari ini melebihi batas waktu pembuatan SPKL, yaitu jam 06:00 sampai 15:00 WIB, silakan hubungi Dept Head untuk pembukaan akses overtime terlambat'
                ]);
			}
		}
		//metode block berdasarkan limit quota per mp saat add employee
		$check_quota_limit	 = m_employee::where('npk','=',$npk)->get();
		foreach ($check_quota_limit as $check_quota_limit) {
			if ($bulan_aktif == "1") {
				$quota_used 	= $check_quota_limit->quota_used_1;
				$quota_remain 	= $check_quota_limit->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($bulan_aktif == "2") {
				$quota_used 	= $check_quota_limit->quota_used_2;
				$quota_remain 	= $check_quota_limit->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($bulan_aktif == "3") {
				$quota_used 	= $check_quota_limit->quota_used_3;
				$quota_remain 	= $check_quota_limit->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($bulan_aktif == "4") {
				$quota_used 	= $check_quota_limit->quota_used_4;
				$quota_remain 	= $check_quota_limit->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($bulan_aktif == "5") {
				$quota_used 	= $check_quota_limit->quota_used_5;
				$quota_remain 	= $check_quota_limit->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($bulan_aktif == "6") {
				$quota_used 	= $check_quota_limit->quota_used_6;
				$quota_remain 	= $check_quota_limit->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($bulan_aktif == "7") {
				$quota_used 	= $check_quota_limit->quota_used_7;
				$quota_remain 	= $check_quota_limit->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($bulan_aktif == "8") {
				$quota_used 	= $check_quota_limit->quota_used_8;
				$quota_remain 	= $check_quota_limit->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($bulan_aktif == "9") {
				$quota_used 	= $check_quota_limit->quota_used_9;
				$quota_remain 	= $check_quota_limit->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($bulan_aktif == "10") {
				$quota_used 	= $check_quota_limit->quota_used_10;
				$quota_remain 	= $check_quota_limit->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($bulan_aktif == "11") {
				$quota_used 	= $check_quota_limit->quota_used_11;
				$quota_remain 	= $check_quota_limit->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($bulan_aktif == "12") {
				$quota_used 	= $check_quota_limit->quota_used_12;
				$quota_remain 	= $check_quota_limit->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
		}
		//hotfix-2.2.6, by Merio, 20161019, menghapus aturan jika quota usedlebih besar sama dengan quota remain maka akan digagalkan saat input
		// if ($quota_used >= $quota_remain) {
		// 	\Session::flash('flash_type','alert-danger');
	 //        \Session::flash('flash_message','Error, karyawan yang dipilih tidak dapat dilemburkan karena quota overtime untuk karyawan tersebut sudah habis, silakan hubungi Dept Head');
	 //        return response()->json('spkl/planning/view/search_result/'.$id_spkl.'');
		// }

		$check_m_employee 		= m_employee::where('npk','=',$user->npk)->get();
		foreach ($check_m_employee as $check_m_employee) {
			$sub_section_code = $check_m_employee->sub_section;
		}

        $check_sub_section = m_employee::where('npk','=',$user->npk)->get();
        foreach ($check_sub_section as $check_sub_section) {
        	$sub_section_check = $check_sub_section->sub_section;
        }
       	$check_sub_section2 = m_sub_section::where('code','=',$sub_section_check)->get();
       	foreach ($check_sub_section2 as $check_sub_section2) {
       		$code_section = $check_sub_section2->code_section;
       	}
       	$check_section = m_section::where('code','=',$code_section)->get();
       	foreach ($check_section as $check_section) {
       		$npk_section 		= $check_section->npk;
       		$code_department 	= $check_section->code_department;
       	}
       	//bypass status
       	$status = 1;
       	if ($npk_section == "") {
       		$status = $status+1;
       		$check_department = m_department::where('code','=',$code_department)->get();
       		foreach ($check_department as $check_department) {
	       		$npk_department 	= $check_department->npk;
	       		$code_division 		= $check_department->code_division;
       		}
       		if ($npk_department == "") {
       			$status = $status+1;
       			$check_division = m_division::where('code','=',$code_division)->get();
	       		foreach ($check_division as $check_division) {
		       		$npk_division 	= $check_division->npk;
	       		}
	       		if ($npk_division == "") {
	       			$status = $status+1;
	       		} else {
	       			$status = $status;
	       		}
       		} else {
       			$status = $status;
       		}
       	} else {
       		$status = $status;
       	}

		$start_time     = date('H:i',strtotime($start_time_planning));
		$end_time       = date('H:i',strtotime($end_time_planning));
		//mencari tanggal awal dan akhir lembur
		$check_date 	= DB::select('select * from t_spkl_details
			join t_spkls on (t_spkls.id_spkl = t_spkl_details.id_spkl)
			where t_spkls.id_spkl = "'.$id_spkl.'" ');
        $result_date 	= new Collection($check_date);
        foreach ($result_date as $result_date) {
        	$start_date_planning 	= $result_date->start_date;
        	$end_date_planning 		= $result_date->end_date;
        	$type					= $result_date->type;
        }

		$sub_section 	= User::select('m_sub_sections.code as code_sub_section')
							->join('m_employees','m_employees.npk','=','users.npk')
							->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->where('m_employees.npk','=',$user->npk)->get();
		foreach ($sub_section as $sub_section) {
			$code_sub_section = $sub_section->code_sub_section;
		}
		$t_spkl_planning 					= new t_spkl_detail;
		$t_spkl_planning->npk 				= $npk;
		//add function save start and end date overtime
		$t_spkl_planning->start_date		= $start_date_planning;

		// dev-1.6.0, Ferry, 20160512, commented and replaced with $end_date_planning + 1 for tomorrow morning
		// $t_spkl_planning->end_date		= $end_date_planning;
		$carbon_start_dt	= Carbon::parse($start_date_planning.' '.$start_time);
		$carbon_end_dt 		= Carbon::parse($start_date_planning.' '.$end_time);
		$t_spkl_planning->end_date		= $carbon_start_dt->lt($carbon_end_dt) ? $start_date_planning :
												$carbon_end_dt->addDay()->toDateString();

		// Tambah shift makan
		$t_spkl_planning->kd_shift_makan = m_shift::generateShiftMakan($carbon_start_dt, $carbon_end_dt, $type);

		// Tambah kode transport otomatis
		$npk_trans_code = m_employee::where('npk', $npk)->pluck('transport');
		$trans_gen_code = m_shift::generateCodeTransport($carbon_start_dt, $carbon_end_dt, $npk_trans_code, $type);
		$t_spkl_planning->kd_shift_trans = $trans_gen_code->kd_shift;
		$t_spkl_planning->kd_trans = $trans_gen_code->code;

		$t_spkl_planning->start_planning	= $start_time_planning;
		$t_spkl_planning->end_planning		= $end_time_planning;
		$t_spkl_planning->is_closed			= "1";
		$t_spkl_planning->sub_section		= $code_sub_section;
		$t_spkl_planning->status			= $status;
		$t_spkl_planning->id_spkl			= $id_spkl;
		$t_spkl_planning->notes				= $input['notes'];
		$t_spkl_planning->ref_code			= $input['ref_no'];
		$t_spkl_planning->npk_leader 		= $user->npk;

		//menambahkan perhitungan quota planning overtime
		$date1	 						= Carbon::parse($start_date_planning.' '.$start_time_planning);
		$date2 							= Carbon::parse($t_spkl_planning->end_date.' '.$end_time_planning);
		$total_ot_temp					= $date1 ->diffInMinutes($date2);
		//merubah format hari untuk break
		$start_day 						= Carbon::parse($start_date_planning)->format('N');
		$end_day 						= Carbon::parse($t_spkl_planning->end_date)->format('N');

        $start_plannings        	= date('Hi',strtotime($start_time_planning));
        $end_plannings 		= date('Hi',strtotime($end_time_planning));
    	//untuk menghitung durasi break
        if ($start_day == $end_day) {
	        $check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
				            			->where('day_break','=',$start_day)
				            			->where('start_break','>=',$start_plannings)
				            			->where('end_break','<=',$end_plannings)
				            			->where('status_break','=','1')->get();
	        foreach ($check_break as $check_break) {
	        	$jml_duration = $check_break->jml;
	        }
       	} else {
	        $check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
	            			->where('day_break','=',$start_day)
	            			->where('start_break','>=',$start_plannings)
	            			->where('end_break','<=','2400')
	            			->where('status_break','=','1')
	            			->get();
	        $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
	            			->where('day_break','=',$end_day)
	            			->where('start_break','>=','0000')
	            			->where('end_break','<=',$end_plannings)
	            			->where('status_break','=','1')
	            			->get();
	        foreach ($check_break1 as $check_break1) {
	        	$jml_duration1 = $check_break1->jml1;
	        }
	        foreach ($check_break2 as $check_break2) {
	         $jml_duration2 = $check_break2->jml2;
	        }
	        $jml_duration = $jml_duration1+$jml_duration2;
    	}
        $total_ot 					= $total_ot_temp-$jml_duration;
        $pengecekan_quota 			= $quota_used+$total_ot;
        if ($pengecekan_quota > $quota_remain) {
        	\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, kuota overtime untuk karyawan yang anda pilih sudah habis, silakan hubungi Dept. Head');
            return response()->json([
				'status' => 'Error',
                'message' => 'kuota overtime untuk karyawan yang anda pilih sudah habis, silakan hubungi Dept. Head'
            ]);
        } else if ($pengecekan_quota <= $quota_remain) {
	        $t_spkl_planning->quota_ot 	= $total_ot;
			$t_spkl_planning->save();

			$update_quota_emp = m_employee::findOrFail($check_quota_limit->id);
			$update_quota_emp->$quota_par = $pengecekan_quota;
			$update_quota_emp->save();

			\Session::flash('flash_type','alert-success');
	        \Session::flash('flash_message','Employee was successfully added to SPKL List');
            return response()->json([
                'status' => 'success',
                'message' => 'Employee was successfully added to SPKL List '
            ]);
		}
    }

    //v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_add_clv_save()
	{
		$user 			= Auth::user();
		$date 			= Carbon::now()->format('Ymd');
		$bulan_aktif 	= Carbon::now()->format('m');
		$input 			= request::all();

		// hotfix-3.5.6, 20190905, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimePlanning = $today . ' ' . $input['start_time_planning'];
		$endTimePlanning = $today . ' ' . $input['end_time_planning'];

		if (strtotime($input['start_time_planning']) > strtotime($input['end_time_planning'])) {
			$endTimePlanning = date('Y-m-d H:i', strtotime($endTimePlanning . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimePlanning) - strtotime($startTimePlanning)) / 60,2);

		if ($inMinute < 30) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
            return response()->json([
                'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}

		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_time_planning'] == $input['end_time_planning']) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
            return response()->json([
                'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}

		if ($input['npk'] == "a"){
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, data overtime harus diisi dengan lengkap, silakan ulangi proses!');
            return response()->json([
				'status' => 'Error',
                'message' => 'data overtime harus diisi dengan lengkap, silakan ulangi proses!'
            ]);
		}

		$start_time_planning	= $input['start_time_planning'];
		$end_time_planning		= $input['end_time_planning'];
		$start_time_planning_2 	= substr($start_time_planning, -3, 1);
		$end_time_planning_2 	= substr($end_time_planning, -3, 1);
		if ($start_time_planning_2 != ":" || $end_time_planning_2 != ":") {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, format start time/end time salah, format = Jam:Menit, contoh : 16:20, silakan ulangi proses');
            return response()->json([
				'status' =>  'Error',
                'message' => 'format start time/end time salah, format = Jam:Menit, contoh : 16:20, silakan ulangi proses'
            ]);
		}

		$start_2  = strlen($start_time_planning);
		$end_2    = strlen($end_time_planning);
		if ($start_2 != "5" || $end_2 != "5") {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, format start time/end time salah, format = Jam:Menit, contoh : 16:20, silakan ulangi proses');
            return response()->json([
				'status' => 'Error',
                'message' => 'format start time/end time salah, format = Jam:Menit, contoh : 16:20, silakan ulangi proses'
            ]);
		}
		//metode block berdasarkan limit quota per mp saat add employee
		$check_quota_limit	 = m_employee::where('npk',$input['npk'])->get();
		foreach ($check_quota_limit as $check_quota_limit) {
			$sub_section_code 	= $check_quota_limit->sub_section;
			$nama 				= $check_quota_limit->nama;
			if ($bulan_aktif == "01") {
				$quota_used 	= $check_quota_limit->quota_used_1;
				$quota_remain 	= $check_quota_limit->quota_remain_1;
			} else if ($bulan_aktif == "02") {
				$quota_used 	= $check_quota_limit->quota_used_2;
				$quota_remain 	= $check_quota_limit->quota_remain_2;
			} else if ($bulan_aktif == "03") {
				$quota_used 	= $check_quota_limit->quota_used_3;
				$quota_remain 	= $check_quota_limit->quota_remain_3;
			} else if ($bulan_aktif == "04") {
				$quota_used 	= $check_quota_limit->quota_used_4;
				$quota_remain 	= $check_quota_limit->quota_remain_4;
			} else if ($bulan_aktif == "05") {
				$quota_used 	= $check_quota_limit->quota_used_5;
				$quota_remain 	= $check_quota_limit->quota_remain_5;
			} else if ($bulan_aktif == "06") {
				$quota_used 	= $check_quota_limit->quota_used_6;
				$quota_remain 	= $check_quota_limit->quota_remain_6;
			} else if ($bulan_aktif == "07") {
				$quota_used 	= $check_quota_limit->quota_used_7;
				$quota_remain 	= $check_quota_limit->quota_remain_7;
			} else if ($bulan_aktif == "08") {
				$quota_used 	= $check_quota_limit->quota_used_8;
				$quota_remain 	= $check_quota_limit->quota_remain_8;
			} else if ($bulan_aktif == "09") {
				$quota_used 	= $check_quota_limit->quota_used_9;
				$quota_remain 	= $check_quota_limit->quota_remain_9;
			} else if ($bulan_aktif == "10") {
				$quota_used 	= $check_quota_limit->quota_used_10;
				$quota_remain 	= $check_quota_limit->quota_remain_10;
			} else if ($bulan_aktif == "11") {
				$quota_used 	= $check_quota_limit->quota_used_11;
				$quota_remain 	= $check_quota_limit->quota_remain_11;
			} else if ($bulan_aktif == "12") {
				$quota_used 	= $check_quota_limit->quota_used_12;
				$quota_remain 	= $check_quota_limit->quota_remain_12;
			}
		}
		$sub_section_check  = m_employee::select('*','m_sections.npk as npk_1','m_departments.npk as npk_2'
        								,'m_divisions.npk as npk_3','m_sub_sections.code as sub_section_code')
        								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
        								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
        								->join('m_departments','m_departments.code','=','m_sections.code_department')
        								->join('m_divisions','m_departments.code_division','=','m_divisions.code')
        								->where('m_employees.npk','=',$user->npk)->get();
        foreach ($sub_section_check as $sub_section_check) {
        	$npk_section 		= $sub_section_check->npk_1;
        	$npk_department 	= $sub_section_check->npk_2;
        	$npk_division 		= $sub_section_check->npk_3;
        	$code_sub_section   = $sub_section_check->sub_section_code;
        }

		// if ($quota_used >= $quota_remain) {
		// 	\Session::flash('flash_type','alert-danger');
	 //        \Session::flash('flash_message','Error, karyawan tidak dapat dilemburkan karena quota overtime untuk bulan ini sudah habis, silakan hubungi atasan langsung!');
	 //        return response()->json('spkl_planning/clv/input');
		// }

		$check_employee = DB::select('select count(npk) as jml from t_spkl_details where npk = "'.$input['npk'].'"
			and is_closed = "0" and is_clv = "1" ');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }
        if ($jml != "0") {
        	\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, karyawan sudah dimasukkan ke dalam List Employee');
	        return response()->json([
				'status' => 'Error',
                'message' => 'karyawan sudah dimasukkan ke dalam List Employee'
            ]);
        }

        $check_employee3 = DB::select('select count(npk) as jml from t_spkl_details where is_closed = "0"
        	and sub_section = "'.$code_sub_section.'" and is_clv = "1" ');
        $check_employee4 = new Collection($check_employee3);
        foreach ($check_employee4 as $check_employee4) {
        	$jml2 = $check_employee4->jml;
        }
        //hotfix-2.2.8, by Merio, 20161025, menghapus role satu spkl tidak boleh lebih dari 25 MP
        // if ($jml2 >= "25") {
        // 	\Session::flash('flash_type','alert-danger');
	       //  \Session::flash('flash_message','Error,  jumlah karyawan dalam 1 SPKL tidak boleh melebihi 25 MP');
	       //  return response()->json('spkl_planning/clv/input');
        // }
       	//bypass status
       	$status = 2;
       	if ($npk_department == "") {
       		$status = $status+1;
	       	if ($npk_division == "" && $npk_department == "") {
	       		$status = $status+1;
	       	} else {
	       		$status = $status;
	     	}
       	} else {
       		$status = $status;
      	}

		$start_time     = date('H:i',strtotime($start_time_planning));
		$end_time       = date('H:i',strtotime($end_time_planning));

		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$t_spkl_planning 					= new t_spkl_detail;
		$t_spkl_planning->npk 				= $input['npk'];
		$t_spkl_planning->start_planning	= $start_time_planning;
		$t_spkl_planning->end_planning		= $end_time_planning;
		$t_spkl_planning->is_closed			= "0";
		$t_spkl_planning->is_clv			= "1";
		$t_spkl_planning->sub_section		= $code_sub_section;
		$t_spkl_planning->status			= $status;
		$t_spkl_planning->notes				= $input['notes'];
		$t_spkl_planning->ref_code 			= $input['ref_no'];

		$t_spkl_planning->npk_leader = $user->npk;
		$t_spkl_planning->save();

				\Session::flash('flash_type','alert-success');
	        	\Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');

				return response()->json([
					'status' => 'success',
					'message' => 'Sukses, ' . $nama . ' (' . $input['npk'] . ') berhasil dimasukkan ke dalam List Employee'
				]);

		//dev-3.4.0, by Fahrul Sudarusman, 20171211, validasi quota spesial limit
		// $npk_spkl = m_employee::where('npk',$input['npk'])->first();

		// if ($npk_spkl) {

		// 	//mencari sub section
		// 	$div_code = $npk_spkl->hasSubSection->hasSection->hasDepartment->hasDivision->code;

		// 	//menemukan quota limit GM
		// 	$special_limit = m_spesial_limits::where('sub_section', $div_code)->pluck('quota_limit');

		// 	$cek_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->first();

		// 	//delete npk ketika sudah ditambah ke tabel approved
		// 	$delete_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->delete();

		// 	// //menghitung selisih waktu
		// 	$start_date_planning = $input['start_date_planning'];
		// 	$end_date_planning   = $input['start_date_planning'];

		// 	$carbon_start_dt 		   = Carbon::parse($start_date_planning.' '.$t_spkl_planning->start_planning);
		// 	$carbon_end_dt 			   = Carbon::parse($end_date_planning.' '.$t_spkl_planning->end_planning);

		// 	$t_spkl_planning->end_date = $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
		// 									$carbon_end_dt->addDay()->toDateString();
		// 	$end_date_planning2		   = $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
		// 									$carbon_end_dt->addDay()->toDateString();
		// 	// end dev-1.6.0
		// 	$date1 				= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
		// 	$date2 				= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
		// 	$hasil_selisih	    = $date1->diffInMinutes($date2);

		// 	if(($npk_spkl->{'quota_used_'.$month}+$hasil_selisih > $special_limit) && (!$cek_approved)){
		// 		$special_limit = $special_limit/60;
		// 		\Session::flash('flash_type','alert-danger');
		// 	    \Session::flash('flash_message','Error, Quota anda sudah melebihi '.$special_limit.' jam parameter, silakan hubungi GM untuk membuka akses membuat SPKL');
		// 	    return response()->json('spkl_planning/clv/input');
		// 	}elseif(($npk_spkl->{'quota_used_'.$month}+$hasil_selisih > $special_limit) && ($cek_approved)){
		// 	//hotfix-2.2.6, by Merio, 20161017, untuk mencari tahu siapa leader yang menambahkan mp tersebut
		// 		$t_spkl_planning->npk_leader = $user->npk;

		// 		$t_spkl_planning->save();
		// 		$delete_approved;

		// 		\Session::flash('flash_type','alert-success');
	 //        	\Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');
	 //        	return response()->json('spkl_planning/clv/input');
		// 	}else{
		// 		$t_spkl_planning->npk_leader = $user->npk;
		// 		$t_spkl_planning->save();

		// 		\Session::flash('flash_type','alert-success');
	 //        	\Session::flash('flash_message','Sukses, '.$nama.' ('.$input['npk'].') berhasil dimasukkan ke dalam List Employee');
	 //        	return response()->json('spkl_planning/clv/input');
		// 	}
		// }
    }

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_input_save()
	{
		$input 				= request::all();
		$date 				= Carbon::now()->format('Ymd');
		$date_approval		= Carbon::now()->format('Y-m-d H:i:s');
		$jam_server 		= Carbon::now()->format('Hi');
		$month  			= Carbon::now()->format('m');
		$year				= Carbon::now()->format('y');
		$user 				= \Auth::user();
		//refisi jam lembur untuk supporting department
		$check_department_user = m_employee::select('*','m_departments.code as name_department',
											'm_sections.code as name_section','m_sections.npk as npk_1',
											'm_departments.npk as npk_2','m_divisions.npk as npk_3')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
											->where('m_employees.npk','=',$user->npk)->get();

		foreach ($check_department_user as $hasil_department) {
			$department_name 		= $hasil_department->name_department;
			$section_name 			= $hasil_department->name_section;
			$sub_section_code		= $hasil_department->sub_section;
			$npk_1 					= $hasil_department->npk_1;
			$npk_2 					= $hasil_department->npk_2;
			$npk_3 					= $hasil_department->npk_3;
		}

		$open  = "0600";
		if ($department_name == "EGB" || $department_name == "EGU" || $department_name == "ITD" ||
			$department_name == "MTE" || $department_name == "QAS" || $department_name == "QBC" || $department_name == "QEC"
			|| $department_name == "OMC") {
			$closed  = "1600";
		} else {
			$closed  = "1500";
		}

		$start_date_planning    = $input['start_date_planning'];
		$end_date_planning    	= $input['start_date_planning'];
		$start_date 			= date('Ymd',strtotime($start_date_planning));
		$month_ot 				= date('m',strtotime($start_date_planning));
		$jam_server2 			= date('Hi',strtotime($jam_server));
		$day_start_friday 		= date('D',strtotime($date));

		//hotfix-2.3.3, by Merio Aji, 20161111, fixing bug tidak bisa membuat spkl untuk masa depan jika jam pembuatan spkl sudah lewat
		if ($start_date <= $date) {
			//hotfix-1.5.5, by Merio Aji, 20160428, Add constraint Access OT Late
			if (($jam_server2 < $open || $jam_server2 > $closed) && $user->ot_par == "1" && $user->limit_mp <= 0) {
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, batas waktu pembuatan SPKL untuk hari ini (06:00-15:00) sudah habis, silakan hubungi Dept. Head untuk pembukaan akses SPKL terlambat');
		        return response()->json([
					'status' => 'Error',
                    'message' => 'batas waktu pembuatan SPKL untuk hari ini (06:00-15:00) sudah habis, silakan hubungi Dept. Head untuk pembukaan akses SPKL terlambat'
                ]);
			}
		}

		if ($user->ot_par == "1" && $user->limit_mp <= 0) {
			if ($start_date < $date) {
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, anda tidak mempunyai akses untuk membuat SPKL di masa lalu / lembur terlambat, silakan hubungi Dept. Head untuk membuka akses lembur terlambat');
		        return response()->json([
					'status' => 'Error',
                    'message' => 'anda tidak mempunyai akses untuk membuat SPKL di masa lalu / lembur terlambat, silakan hubungi Dept. Head untuk membuka akses lembur terlambat'
                ]);
			}
		}

		//mencari type spkl
		$check_type 	= m_holiday::where('date_holiday',$start_date_planning)->first();
		if ($check_type) {
			$type_spkl = "2";
		} else {
			$day    = date('N',strtotime($start_date_planning));
	   		if ($day == "6" || $day == "7") {
	   			$type_spkl = "2";
	   		} else {
	   			$type_spkl = "1";
	   		}
	   	}

	   	//dev-1.7, by Merio, 20160630, add code department to id spkl
		$queries 	= DB::select('select MAX(SUBSTRING(id_spkl, 10,4)) as maxid
            from t_spkls
            where SUBSTRING(id_spkl, 3, 2) = '.$month.' and SUBSTRING(id_spkl, 1, 2) = '.$year.'
            and SUBSTRING(id_spkl, 6, 3) = "'.$department_name.'" and kolektif="" ');
        $result 	= new Collection($queries);

        foreach ($result as $results) {
        	$id 		= $results->maxid;
        	$last_id 	= $id+1;
        	$last_id2 	= sprintf("%04s",$last_id);
        	$a 			= strlen($last_id2);
        	$id_spkl 	= "$year$month-$department_name-$last_id2";
        }

        $t_spkl 					= new t_spkl;
		$t_spkl->id_spkl			= $id_spkl;
		$t_spkl->is_print			= "0";
		$t_spkl->type 				= $type_spkl;
		$t_spkl->category 			= $input['category'];

		if ($input['category'] == '2') {
			$t_spkl->category_detail 	= $input['category_detail'];
		}

		if (($start_date <= $date && ($jam_server2 < $open || $jam_server2 > $closed)) || $start_date < $date) {
			$t_spkl->is_late = "1";
		} else {
			$t_spkl->is_late = "0";
		}

		$t_spkl->note 				= $input['note'];
		$t_spkl->npk_1              = $npk_1;
		$t_spkl->npk_2              = $npk_2;
		$t_spkl->npk_3              = $npk_3;
		$t_spkl->save();

		//hotfix-2.2.6, by Yudo&Merio, 20161018, pengecekan dan delete untuk menghindari duplikasi data
		$spkl_outstanding = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','0')
										->get();

		foreach ($spkl_outstanding as $spkl_outstanding) {
			$id_spkl_outstanding  	 		= $spkl_outstanding->id;
			$npk_outstanding  				= $spkl_outstanding->npk;
			$start_planning_outstanding 	= $spkl_outstanding->start_planning;
			$end_planning_outstanding 		= $spkl_outstanding->end_planning;

			//$t_spkl_planing_repeat = DB::select('SELECT * FROM t_spkl_details WHERE sub_section = "'.$sub_section_code.'"
			// 	AND npk="'.$npk_mp_repeat.'" AND is_closed = "1" AND is_clv = "0" and start_date="'.$start_date.'"
			// 	AND (("'.$start_planning_repeat.'" BETWEEN start_planning and end_planning) or
			// 	("'.$end_planning_repeat.'" BETWEEN start_planning and end_planning) )');

			$check_duplikasi = t_spkl_detail::where('npk','=',$npk_outstanding)
											->where('start_date','=',$start_date_planning)
											->where('start_planning','=',$start_planning_outstanding)
											->where('end_planning','=',$end_planning_outstanding)
											->where('is_closed','=','1')
											->where('is_clv','=','0')
											->get();
			if (count($check_duplikasi)>0) {
				t_spkl_detail::destroy($id_spkl_outstanding);
			}
		}

		$check_ada_data_tidak = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','0')
										->get();

		if (count($check_ada_data_tidak)>0) {


		} else {
			$hapus_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
			foreach ($hapus_spkl as $hapus_spkl) {
				$id_spkl_yang_akan_dihapus = $hapus_spkl->id;
			}
			t_spkl::destroy($id_spkl_yang_akan_dihapus);

			\Session::flash('flash_type','alert-error');
		    \Session::flash('flash_message','Error, MP yang dimasukkan sudah pernah dilemburkan');
		    return response()->json([
				'status' => 'Error',
                'message' => 'MP yang dimasukkan sudah pernah dilemburkan'
            ]);
		}

		$t_spkl_employee = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','0')
										->get();

		foreach ($t_spkl_employee as $t_spkl_employee) {

			$t_spkl_planning 				= t_spkl_detail::findOrFail($t_spkl_employee->id);
			$t_spkl_planning->is_closed 	= "1";
			$t_spkl_planning->id_spkl		= $id_spkl;
			$t_spkl_planning->start_date 	= $start_date_planning;

			// $t_spkl_planning->end_date		= $end_date_planning;
			// dev-1.6.0, Ferry, 20160512, commented and replaced with $end_date_planning + 1 for tomorrow morning
			// $t_spkl_planning->end_date		= $end_date_planning;
			$carbon_start_dt 	= Carbon::parse($start_date_planning.' '.$t_spkl_planning->start_planning);
			$carbon_end_dt 		= Carbon::parse($end_date_planning.' '.$t_spkl_planning->end_planning);
			$t_spkl_planning->end_date		= $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
											$carbon_end_dt->addDay()->toDateString();
			$end_date_planning2				= $carbon_start_dt->lt($carbon_end_dt) ? $end_date_planning :
											$carbon_end_dt->addDay()->toDateString();

			// Tambah shift makan
			$t_spkl_planning->kd_shift_makan = m_shift::generateShiftMakan($carbon_start_dt, $carbon_end_dt, $type_spkl);
			// Tambah kode transport otomatis
			$npk_trans_code = m_employee::where('npk', $t_spkl_planning->npk)->pluck('transport');
			$trans_gen_code = m_shift::generateCodeTransport($carbon_start_dt, $carbon_end_dt, $npk_trans_code, $type_spkl);
			$t_spkl_planning->kd_shift_trans = $trans_gen_code->kd_shift;
			$t_spkl_planning->kd_trans = $trans_gen_code->code;
			// end dev-1.6.0

			$date1 							= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
			$date2 							= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
			$total_ot_temp					= $date1->diffInMinutes($date2);
			//merubah format untuk mendapatkan hari break
			$start_day 						= Carbon::parse($t_spkl_planning->start_date)->format('N');
			$end_day 						= Carbon::parse($t_spkl_planning->end_date)->format('N');
	        $start_plannings 	= date('Hi',strtotime($t_spkl_planning->start_planning));
	        $end_plannings 		= date('Hi',strtotime($t_spkl_planning->end_planning));

	        //untuk menghitung durasi break
	        if ($t_spkl_planning->start_date == $t_spkl_planning->end_date) {
	        	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
	            						->where('day_break','=',$start_day)
	            						->where('start_break','>=',$start_plannings)
	            						->where('end_break','<=',$end_plannings)
	            						->where('status_break','=','1')
	            						->get();
	            foreach ($check_break as $check_break) {
	            	$jml_duration = $check_break->jml;
	            }
	        }
	        else {
	        	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
	        								->where('day_break','=',$start_day)
	        								->where('start_break','>=',$start_plannings)
	        								->where('end_break','<=','2400')
	        								->where('status_break','=','1')
	        								->get();
	        	$check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
	        								->where('day_break','=',$end_day)
	        								->where('start_break','>=','0000')
	        								->where('end_break','<=',$end_plannings)
	        								->where('status_break','=','1')
	        								->get();
	        	foreach ($check_break1 as $check_break1) {
	        		$jml_duration1 = $check_break1->jml1;
	        	}
	        	foreach ($check_break2 as $check_break2) {
	        		$jml_duration2 = $check_break2->jml2;
	        	}
	           	$jml_duration = $jml_duration1+$jml_duration2;
	        }

	        $total_ot 					= $total_ot_temp-$jml_duration;
	        $t_spkl_planning->quota_ot 	= $total_ot;
	        //pengurangan quota used di m_employee
			$npk_mp 						= $t_spkl_employee->npk;
			$check_quota_mp_spkl 			= m_employee::where('npk','=',$npk_mp)->get();

			foreach ($check_quota_mp_spkl as $check_quota_mp_spkl) {

				$id_emp 	= $check_quota_mp_spkl->id;
				if ($month_ot == "01") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_1;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month_ot == "02") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_2;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month_ot == "03") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_3;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month_ot == "04") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_4;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month_ot == "05") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_5;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month_ot == "06") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_6;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month_ot == "07") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_7;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month_ot == "08") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_8;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month_ot == "09") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_9;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month_ot == "10") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_10;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month_ot == "11") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_11;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month_ot == "12") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_12;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}

			if ($t_spkl->is_late == '1') {
				if ($npk_1 == "") {
					$t_spkl_planning->status = "5";
					$t_spkl_planning->approval_1_realisasi_date = $date_approval;
				} elseif ($npk_1 == "" && $npk_2 == "") {
					$t_spkl_planning->status = "6";
					$t_spkl_planning->approval_2_realisasi_date = $date_approval;
				} elseif ($npk_1 != "") {
					$t_spkl_planning->status = "4";
					$t_spkl_planning->approval_3_planning_date = $date_approval;
				}
				$t_spkl_planning->start_actual 		= $t_spkl_employee->start_planning;
				$t_spkl_planning->end_actual		= $t_spkl_employee->end_planning;
				//hotfix-2.0.1, by Merio, 20160828, fixing bug saat create spkl terlambat, quota ot actualnya seharusnya ikut tergenerate
				$t_spkl_planning->quota_ot_actual 	= $total_ot;
			}
			else {
				if ($t_spkl_planning->status == "2") {
					$t_spkl_planning->approval_1_planning_date = $date_approval;
				} else if ($t_spkl_planning->status == "3") {
					$t_spkl_planning->approval_2_planning_date = $date_approval;
				} else if ($t_spkl_planning->status == "4") {
					$t_spkl_planning->approval_3_planning_date = $date_approval;
				}
			}

			$npk_spkl = m_employee::where('npk',$npk_mp)->first();

				//mencari sub section
				$div_code = $npk_spkl->hasSubSection->hasSection->hasDepartment->hasDivision->code;

				//menemukan quota limit GM
				$special_limit = m_spesial_limits::where('sub_section', $div_code)->first();

				$limit_weekday = $special_limit->quota_limit_weekday;
				$limit_holiday = $special_limit->quota_limit_holiday;

				//temukan tanggal weekend dan holiday
				$cek_its_holiday = m_holiday::where('date_holiday', $t_spkl_planning->start_date)->first();

				$cek_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->first();

				//delete npk ketika sudah ditambah ke tabel approved
				$delete_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk);

				$date1 				= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
				$date2 				= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
				$hasil_selisih	    = $date1->diffInMinutes($date2);

				if ($cek_its_holiday){

					$carbon_tgl_inputan = Carbon::parse($t_spkl_planning->start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_holiday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumHoliday'))
														->whereIn('start_date', $arr)
														->where('npk', $npk_spkl->npk)
														->get();

					//mendapatkan remain quota holiday
					$remain_holiday = $limit_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

					if (($remain_holiday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_holiday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
							'status' => 'Error',
                            'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);
					}
					elseif (($remain_holiday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						$created_at = Carbon::parse($cek_approved->created_at)->format('Y-m-d');
						$cek_bulan_approve = Carbon::createFromFormat('Y-m-d', $created_at);
						$tgl_input = Carbon::createFromFormat('Y-m-d', $t_spkl_planning->start_date);

						$remain_holiday = $hrd_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

						if ($remain_holiday < 0) {

							$limit_by_jam = $hrd_holiday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

						if ($tgl_input->format('Y-m') > $cek_bulan_approve->format('Y-m')) {

							$delete_approved->delete();
						}
					}
				}
				else {

					$carbon_tgl_inputan = Carbon::parse($t_spkl_planning->start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_weekday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumWeekday'))
														->whereNotIn('start_date', $arr)
														->where('start_date', 'like', '%' . $carbon_tgl_inputan . '%')
														->where('npk', $npk_spkl->npk)
														->get();
					//mendapatkan remain quota weekday
					$remain_weekday = $limit_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

					if (($remain_weekday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_weekday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
							'status' => 'Error',
                            'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);
					}
					elseif (($remain_weekday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						$cek_bulan_approve = Carbon::parse($cek_approved->created_at)->format('Y-m');

						$remain_weekday = $hrd_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

						if ($remain_weekday < 0) {

							$limit_by_jam = $hrd_weekday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

						if ($tgl_input->format('Y-m') > $cek_bulan_approve->format('Y-m')) {

							$delete_approved->delete();
						}
					}
				}

				$pengurangan_quota_used = $total_ot+$quota_used;

				if ($pengurangan_quota_used < $quota_remain) {
					$m_employee_update 				= m_employee::findOrFail($id_emp);
					$m_employee_update->$quota_par 	= $pengurangan_quota_used;
					$m_employee_update->save();

					$t_spkl_planning->save();
				}
			}
		$check_ada_member_enggak = t_spkl_detail::where('id_spkl','=',$id_spkl)->get();

		$jml_mp_over_quota 		= DB::select('select npk, count(*) as jml from t_spkl_details
			WHERE is_closed=0 and is_clv=0 and sub_section="'.$sub_section_code.'"');
        $result_mp_over_quota 	= new Collection($jml_mp_over_quota);
		$check_over_mp = t_spkl_detail::select('npk')
										->where('is_closed','0')
										->where('is_clv','0')
										->where('sub_section',$sub_section_code)
										->get();

		//hotix-2.2.2, by Yudo, 20160929, Menampilkan MP yg over
		$menghapusSimbol = preg_replace('/[^\p{L}\p{N}\s]/u', '', $check_over_mp);
		$menghapusString = str_replace('npk', ' ,', $menghapusSimbol);
		$testku = ltrim($menghapusString, ' ,');


		foreach ($result_mp_over_quota as $result_mp_over_quota) {
			$jml_mp_over = $result_mp_over_quota->jml;
			$npk_mp_over = $result_mp_over_quota->npk;
		}

		//hotfix-2.0.1, by Merio, 20160828, check npk mana saja yang over, biar notifikasi ke mp makin jelas
		$npk_mp_over_quota 		= t_spkl_detail::select('npk')
												->where('is_closed',0)
												->where('is_clv',0)
												->where('sub_section',$sub_section_code)
												->get();

		if (count($check_ada_member_enggak) > 0) {
			if ($jml_mp_over >= 1) {
				//hotfix-2.0.1, by Merio, 20160828, check kalo ada yg over quota, spkl di hapus dan yg sukses digagalin semua
				//pertama spkl yang sudah dibentuk dihapus
				$delete_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
				foreach ($delete_spkl as $delete_spkl) {
					$id_delete = $delete_spkl->id;
				}
				t_spkl::destroy($id_delete);
				//lalu detail yang sudah tergenerate di update menjadi ungenerate
				$update_spkl_detail = t_spkl_detail::where('id_spkl',$id_spkl)->get();
				foreach ($update_spkl_detail as $update_spkl_detail) {
					$update_spkl_detail->id_spkl 		= '';
					$update_spkl_detail->is_closed 		= 0;
					//hotfix-2.2.1, by Merio, 20160920, Mengurangi quota used yang sudah terlanjur di generate spkl
					$update_spkl_detail->employee->{$quota_par} -= $update_spkl_detail->quota_ot;
					$update_spkl_detail->employee->save();
					$update_spkl_detail->save();
				}
				//3rd notif di update biar makin cakep
				\Session::flash('flash_type','alert-danger');
		        // \Session::flash('flash_message','Error, SPKL tidak dapat di generate, terdapat '.$jml_mp_over.' MP
		      	\Session::flash('flash_message','Error, SPKL tidak dapat di generate, terdapat '.$jml_mp_over.' MP yaitu npk : '.$testku.',tidak dapat dilemburkan karena quota untuk mp tersebut sudah habis, silakan hubungi Dept. Head');
                        return response()->json([
							'status' => 'Error',
                            'message' => 'SPKL tidak dapat di generate, terdapat '.$jml_mp_over.' MP yaitu npk : '.$testku.',tidak dapat dilemburkan karena quota untuk mp tersebut sudah habis, silakan hubungi Dept. Head'
                        ]);
			} else {
				//hotfix-2.0.1, by Merio, 20160828, fix bug perubahan status id_par dilakukan saat benar2 sukses membuat spkl terlambat
				if (($t_spkl->is_late == '1') && $id_spkl != '') {
					$user2 				= User::findOrFail($user->id);
					$user2->limit_mp 	= $user2->limit_mp-1;
					if ($user2->limit_mp <= 0) {
						$user2->ot_par 		= "1";
					}
					$user2->save();
				}

				\Session::flash('flash_type','alert-success');
		        \Session::flash('flash_message','Sukses, SPKL dengan id '.$id_spkl.' berhasil dibuat');
		        return response()->json([
					'status' => 'success',
                    'message' => 'Sukses, SPKL dengan id '.$id_spkl.' berhasil dibuat'
                ]);
			}
		} else {

			$delete_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
			foreach ($delete_spkl as $delete_spkl) {
				$id_delete = $delete_spkl->id;
			}
			t_spkl::destroy($id_delete);
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, Karyawan sudah tidak dapat dilemburkan, silakan hubungi Dept. Head');
            return response()->json([
				'status' => 'Error',
                'message' => 'Karyawan sudah tidak dapat dilemburkan, silakan hubungi Dept. Head'
            ]);
		}

	}

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_input_clv_save()
	{
		$input 			= request::all();
		$date_approval	= Carbon::now()->format('Y-m-d H:i:s');
		$date 			= Carbon::now()->format('Ymd');
		$jam_server 	= Carbon::now()->format('Hi');
		$month  		= Carbon::now()->format('m');
		$year 			= Carbon::now()->format('y');
		$user 			= \Auth::user();
		$npk 			= $user->npk;
		//Membedakan waktu input berdasarkan support dan tidak
		$check_department_user = m_employee::select('*','m_departments.code as name_department',
											'm_sections.code as name_section','m_sections.npk as npk_1',
											'm_departments.npk as npk_2','m_divisions.npk as npk_3')
											->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->join('m_departments','m_departments.code','=','m_sections.code_department')
											->join('m_divisions','m_divisions.code','=','m_departments.code_division')
											->where('m_employees.npk','=',$npk)->get();
		foreach ($check_department_user as $hasil_department) {
			$department_name 	= $hasil_department->name_department;
			$section_name 		= $hasil_department->name_section;
			$sub_section_code 	= $hasil_department->sub_section;
			$npk_1 				= $hasil_department->npk_1;
			$npk_2 				= $hasil_department->npk_2;
			$npk_3 				= $hasil_department->npk_3;
		}
		$open  = "0600";
		if ($department_name == "EGB" || $department_name == "EGU" || $department_name == "ITD" ||
			$department_name == "MTE" || $department_name == "QAS" || $department_name == "QBC" || $department_name == "QEC"
			|| $department_name == "OMC") {
			$closed  = "1600";
		} else {
			$closed  = "1500";
		}

		$start_date_planning   	= $input['start_date_planning'];
		$start_date 			= date('Ymd',strtotime($start_date_planning));
		$month_ot 				= date('m',strtotime($start_date_planning));
		$jam_server2 			= date('Hi',strtotime($jam_server));
		$day_start_friday 		= date('D',strtotime($date));


		if ($start_date <= $date) {
			//hotfix-1.5.5, by Merio Aji, 20160428, Add constraint Access OT Late
			if (($jam_server2 < $open || $jam_server2 > $closed) && $user->ot_par == "1" && $user->limit_mp <= 0) {
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, batas waktu pembuatan SPKL untuk hari ini (06:00-15:00) sudah habis, silakan hubungi Dept. Head untuk pembukaan akses SPKL terlambat');
                return response()->json([
					'status' => 'Error',
                    'message' => 'batas waktu pembuatan SPKL untuk hari ini (06:00-15:00) sudah habis, silakan hubungi Dept. Head untuk pembukaan akses SPKL terlambat'
                ]);
			}
		}
		if ($user->ot_par == "1" && $user->limit_mp <= 0) {
			if ($start_date < $date) {
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, anda tidak mempunyai akses untuk planning lembur di masa lalu / lembur terlambat, silakan hubungi Dept. Head untuk membuka akses lembur terlambat');
                return response()->json([
					'status' => 'Error',
                    'message' => 'anda tidak mempunyai akses untuk planning lembur di masa lalu / lembur terlambat, silakan hubungi Dept. Head untuk membuka akses lembur terlambat'
                ]);
			}
		}

		//mencari type spkl
		$check_type 	= m_holiday::where('date_holiday',$start_date_planning)->get();
		if (count($check_type) > 0) {
			foreach ($check_type as $check_type) {
				$type_spkl  = $check_type->type_holiday;
			}
		} else {
			$day    = date('N',strtotime($start_date_planning));
	   		if ($day == "6" || $day == "7") {
	   			$type_spkl = "2";
	   		} else {
	   			$type_spkl = "1";
	   		}
		}

		//dev-1.7, by Merio, 20160630, add code department to id spkl
		$queries 	= DB::select('select MAX(SUBSTRING(id_spkl, 14,4)) as maxid
            from t_spkls where SUBSTRING(id_spkl, 3, 2) = '.$month.' and SUBSTRING(id_spkl, 1, 2) = '.$year.'
            and SUBSTRING(id_spkl, 6, 3) = "'.$department_name.'" and kolektif=1 ');
        $result 	= new Collection($queries);

		foreach ($result as $results) {
			$id 		= $results->maxid;
			$last_id 	= $id+1;
			$last_id2 	= sprintf("%04s",$last_id);
			$a 			= strlen($last_id2);
			$id_spkl 	= "$year$month-$department_name-CLV-$last_id2"; //dev-1.7, by Merio, 20160630, add code department to id spkl
		}
		$t_spkl 					= new t_spkl;
		$t_spkl->id_spkl			= $id_spkl;
		$t_spkl->is_print			= "0";
		$t_spkl->type 				= $type_spkl;
		$t_spkl->category 			= $input['category'];
		$t_spkl->kolektif 			= "1";
		if ($input['category'] == '2') {
			$t_spkl->category_detail 	= $input['category_detail'];
		}

		if (($start_date < $date && ($jam_server2 < $open || $jam_server2 > $closed)) || $start_date < $date) {   			// hotfix 1.5.5 by andre menambah input ke field is_late
			$t_spkl->is_late = "1";
		} else {
			$t_spkl->is_late = "0";
		}
		$t_spkl->note 				= $input['note'];
		$t_spkl->npk_1              = $npk_1;
		$t_spkl->npk_2              = $npk_2;
		$t_spkl->npk_3              = $npk_3;
		$t_spkl->save();

		//hotfix-2.2.6, by Merio, 20161020, pengecekan dan delete untuk menghindari duplikasi data
		$spkl_outstanding = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','1')
										->get();
		foreach ($spkl_outstanding as $spkl_outstanding) {
			$id_spkl_outstanding  	 		= $spkl_outstanding->id;
			$npk_outstanding  				= $spkl_outstanding->npk;
			$start_planning_outstanding 	= $spkl_outstanding->start_planning;
			$end_planning_outstanding 		= $spkl_outstanding->end_planning;

			$check_duplikasi = t_spkl_detail::where('npk','=',$npk_outstanding)
											->where('start_date','=',$start_date_planning)
											->where('start_planning','=',$start_planning_outstanding)
											->where('end_planning','=',$end_planning_outstanding)
											->where('is_closed','=','1')
											->where('is_clv','=','1')
											->get();
			if (count($check_duplikasi)>0) {
				t_spkl_detail::destroy($id_spkl_outstanding);
			}
		}

		$check_ada_data_tidak = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
										->where('t_spkl_details.is_closed','=','0')
										->where('t_spkl_details.is_clv','=','1')
										->get();
		if (count($check_ada_data_tidak)>0) {
		} else {
			$hapus_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
			foreach ($hapus_spkl as $hapus_spkl) {
				$id_spkl_yang_akan_dihapus = $hapus_spkl->id;
			}

			t_spkl::destroy($id_spkl_yang_akan_dihapus);

			\Session::flash('flash_type','alert-error');
		    \Session::flash('flash_message','Error, MP yang dimasukkan sudah pernah dilemburkan');
		    return response()->json([
				'status' => 'Error',
                'message' => 'MP yang dimasukkan sudah pernah dilemburkan'
            ]);
		}

		$t_spkl_employee = t_spkl_detail::where('t_spkl_details.sub_section','=',$sub_section_code)
											->where('t_spkl_details.is_closed','=','0')
											->where('t_spkl_details.is_clv','=','1')
											->get();
		foreach ($t_spkl_employee as $t_spkl_employee) {
			$t_spkl_planning 				= t_spkl_detail::findOrFail($t_spkl_employee->id);
			$t_spkl_planning->is_closed 	= "1";
			$t_spkl_planning->id_spkl		= $id_spkl;
			$t_spkl_planning->start_date 	= $start_date_planning;
			// dev-1.6.0, Ferry, 20160512, commented and replaced with $end_date_planning + 1 for tomorrow morning
			$carbon_start_dt	= Carbon::parse($start_date_planning.' '.$t_spkl_planning->start_planning);
			$carbon_end_dt 		= Carbon::parse($start_date_planning.' '.$t_spkl_planning->end_planning);
			$t_spkl_planning->end_date	= $carbon_start_dt->lt($carbon_end_dt) ? $start_date_planning :
										$carbon_end_dt->addDay()->toDateString();

			// Tambah shift makan
			$t_spkl_planning->kd_shift_makan = m_shift::generateShiftMakan($carbon_start_dt, $carbon_end_dt, $type_spkl);

			// Tambah kode transport otomatis
			$npk_trans_code = m_employee::where('npk', $t_spkl_planning->npk)->pluck('transport');
			$trans_gen_code = m_shift::generateCodeTransport($carbon_start_dt, $carbon_end_dt, $npk_trans_code, $type_spkl);
			$t_spkl_planning->kd_shift_trans = $trans_gen_code->kd_shift;
			$t_spkl_planning->kd_trans = $trans_gen_code->code;
			// end dev-1.6.0
			// $t_spkl_planning->end_date		= $end_date_planning;
			$date1	 						= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
			$date2 							= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
			$total_ot_temp					= $date1 ->diffInMinutes($date2);

			//merubah format untuk mendapatkan hari break
			$start_day 						= Carbon::parse($t_spkl_planning->start_date)->format('N');
			$end_day 						= Carbon::parse($t_spkl_planning->end_date)->format('N');

            $start_plannings 	= date('Hi',strtotime($t_spkl_planning->start_planning));
            $end_plannings 		= date('Hi',strtotime($t_spkl_planning->end_planning));

            //untuk menghitung durasi break
            if ($start_day == $end_day) {
            	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break as $check_break) {
            		$jml_duration = $check_break->jml;
            	}
            } else {
            	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=','2400')
            								->where('status_break','=','1')
            								->get();
            	$check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            								->where('day_break','=',$end_day)
            								->where('start_break','>=','0000')
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break1 as $check_break1) {
            		$jml_duration1 = $check_break1->jml1;
            	}
            	foreach ($check_break2 as $check_break2) {
            		$jml_duration2 = $check_break2->jml2;
            	}
            	$jml_duration = $jml_duration1+$jml_duration2;
            }
            $total_ot 					= $total_ot_temp-$jml_duration;
            $t_spkl_planning->quota_ot 	= $total_ot;

        	//pengurangan quota used di m_employee
			$npk_mp 						= $t_spkl_employee->npk;
			$check_quota_mp_spkl 			= m_employee::where('npk','=',$npk_mp)->get();
			foreach ($check_quota_mp_spkl as $check_quota_mp_spkl) {
				$id_emp 	= $check_quota_mp_spkl->id;
				if ($month_ot == "01") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_1;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month_ot == "02") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_2;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month_ot == "03") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_3;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_3; //hotfixes-3.0.3, Yudo, 20170301, tadinya quota quota_remain_ot -> quota_remain
					$quota_par 		= "quota_used_3";
				} else if ($month_ot == "04") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_4;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month_ot == "05") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_5;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month_ot == "06") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_6;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month_ot == "07") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_7;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month_ot == "08") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_8;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month_ot == "09") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_9;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month_ot == "10") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_10;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month_ot == "11") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_11;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month_ot == "12") {
					$quota_used 	= $check_quota_mp_spkl->quota_used_12;
					$quota_remain 	= $check_quota_mp_spkl->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}

			if ($start_date <= $date && ($jam_server2 < $open || $jam_server2 > $closed)) {   			// hotfix 1.5.5 by andre menambah input ke field is_late
				if ($npk_1 == "") {
					$t_spkl_planning->status 					= "5";
					$t_spkl_planning->approval_1_realisasi_date = $date_approval;
				} elseif ($npk_2 == "" && $npk_1 == "") {
					$t_spkl_planning->status 					= "6";
					$t_spkl_planning->approval_2_realisasi_date = $date_approval;
				} elseif ($npk_1 != "") {
					$t_spkl_planning->status 					= "4";
					$t_spkl_planning->approval_3_planning_date 	= $date_approval;
				}
				$t_spkl_planning->start_actual 		= $t_spkl_employee->start_planning;
				$t_spkl_planning->end_actual		= $t_spkl_employee->end_planning;
				//hotfix-2.0.1, by Merio, 20160828, fixing bug saat create spkl terlambat, quota ot actualnya seharusnya ikut tergenerate
				$t_spkl_planning->quota_ot_actual 	= $total_ot;
			} else {
				if ($t_spkl_planning->status == "2") {
					$t_spkl_planning->approval_1_planning_date = $date_approval;
				} else if ($t_spkl_planning->status == "3") {
					$t_spkl_planning->approval_2_planning_date = $date_approval;
				} else if ($t_spkl_planning->status == "4") {
					$t_spkl_planning->approval_3_planning_date = $date_approval;
				}
			}

			$npk_spkl = m_employee::where('npk',$npk_mp)->first();

				//mencari sub section
				$div_code = $npk_spkl->hasSubSection->hasSection->hasDepartment->hasDivision->code;

				//menemukan quota limit GM
				$special_limit = m_spesial_limits::where('sub_section', $div_code)->first();

				$limit_weekday = $special_limit->quota_limit_weekday;
				$limit_holiday = $special_limit->quota_limit_holiday;

				//temukan tanggal weekend dan holiday
				$cek_its_holiday = m_holiday::where('date_holiday', $t_spkl_planning->start_date)->first();

				$cek_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->first();

				//delete npk ketika sudah ditambah ke tabel approved
				// $delete_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->delete();

				$date1 				= Carbon::parse($t_spkl_planning->start_date.' '.$t_spkl_planning->start_planning);
				$date2 				= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
				$hasil_selisih	    = $date1->diffInMinutes($date2);

				if ($cek_its_holiday) {

					$carbon_tgl_inputan = Carbon::parse($t_spkl_planning->start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_holiday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumHoliday'))
														->whereIn('start_date', $arr)
														->where('npk', $npk_spkl->npk)
														->get();

					//mendapatkan remain quota holiday
					$remain_holiday = $limit_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

					if (($remain_holiday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_holiday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
							'status' => 'Error',
                            'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);
					}
					elseif (($remain_holiday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						$created_at = Carbon::parse($cek_approved->created_at)->format('Y-m-d');
						$cek_bulan_approve = Carbon::createFromFormat('Y-m-d', $created_at);
						$tgl_input = Carbon::createFromFormat('Y-m-d', $t_spkl_planning->start_date);


						$remain_holiday = $hrd_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

						if ($remain_holiday < 0) {

							$limit_by_jam = $hrd_holiday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

						if ($tgl_input->format('Y-m') > $cek_bulan_approve->format('Y-m')) {

							$delete_approved;
						}
					}

				}else {

					$carbon_tgl_inputan = Carbon::parse($t_spkl_planning->start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_weekday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumWeekday'))
														->whereNotIn('start_date', $arr)
														->where('start_date', 'like', '%' . $carbon_tgl_inputan . '%')
														->where('npk', $npk_spkl->npk)
														->get();
					//mendapatkan remain quota weekday
					$remain_weekday = $limit_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

					if (($remain_weekday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_weekday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
                            'status' => 'Error',
                            'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);
					}
					elseif (($remain_weekday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						$cek_bulan_approve = Carbon::parse($cek_approved->created_at)->format('Y-m');

						$remain_weekday = $hrd_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

						if ($remain_weekday < 0) {

							$limit_by_jam = $hrd_weekday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

						if ($tgl_input->format('Y-m') > $cek_bulan_approve->format('Y-m')) {

							$delete_approved;
						}

					}

				}
			$pengurangan_quota_used = $total_ot+$quota_used;
			if ($pengurangan_quota_used < $quota_remain) {
				$m_employee_update 				= m_employee::findOrFail($id_emp);
				$m_employee_update->$quota_par 	= $pengurangan_quota_used;
				$m_employee_update->save();

				$t_spkl_planning->save();
			}
		}
		$check_ada_member_enggak = t_spkl_detail::where('id_spkl','=',$id_spkl)->get();

		$jml_mp_over_quota 		= DB::select('select npk,count(*) as jml from t_spkl_details
			WHERE is_closed=0 and is_clv=1 and sub_section="'.$sub_section_code.'"');
        $result_mp_over_quota 	= new Collection($jml_mp_over_quota);
		foreach ($result_mp_over_quota as $result_mp_over_quota) {
			$jml_mp_over = $result_mp_over_quota->jml;
		}
		//hotfix-2.0.1, by Merio, 20160828, check npk mana saja yang over, biar notifikasi ke mp makin jelas
		$npk_mp_over_quota 		= t_spkl_detail::select('npk')
												->where('is_closed',0)
												->where('is_clv',1)
												->where('sub_section',$sub_section_code)
												->get();

		//hotix-2.2.2, by Yudo, 20160929, Menampilkan MP yg over
		$menghapusSimbol = preg_replace('/[^\p{L}\p{N}\s]/u', '', $npk_mp_over_quota);
		$menghapusString = str_replace('npk', ' ,', $menghapusSimbol);
		$testku = ltrim($menghapusString, ' ,');

		if (count($check_ada_member_enggak) > 0) {
			if ($jml_mp_over >= 1) {
				//pertama spkl yang sudah dibentuk dihapus
				$delete_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
				foreach ($delete_spkl as $delete_spkl) {
					$id_delete = $delete_spkl->id;
				}
				t_spkl::destroy($id_delete);

				//hotfix-2.2.1, by Merio, 20160920, Mengurangi quota used yang sudah terlanjur di generate spkl
				$update_spkl_detail = t_spkl_detail::where('id_spkl',$id_spkl)->get();
				foreach ($update_spkl_detail as $update_spkl_detail) {
					$update_spkl_detail->id_spkl 		= '';
					$update_spkl_detail->is_closed 	= 0;
					$update_spkl_detail->employee->{$quota_par} -= $update_spkl_detail->quota_ot;
					$update_spkl_detail->employee->save();
					$update_spkl_detail->save();
				}

				//3rd notif di update biar makin cakep
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, SPKL tidak dapat di generate, terdapat '.$jml_mp_over.' MP yaitu : '.$testku.' , tidak dapat dilemburkan karena quota untuk mp tersebut sudah habis, silakan hubungi Dept. Head');
                    return response()->json([
						'status' => 'Error',
                        'message' => 'SPKL tidak dapat di generate, terdapat '.$jml_mp_over.' MP yaitu : '.$testku.' , tidak dapat dilemburkan karena quota untuk mp tersebut sudah habis, silakan hubungi Dept. Head'
                    ]);
			} else {
				//hotfix-2.0.1, by Merio, 20160828, fix bug perubahan status id_par dilakukan saat benar2 sukses membuat spkl terlambat
				if (($t_spkl->is_late == '1') && $id_spkl != '') {
					$user2 				= User::findOrFail($user->id);
					$user2->limit_mp 	= $user2->limit_mp-1;
					if ($user2->limit_mp <= 0) {
						$user2->ot_par 		= "1";
					}
					$user2->save();
				}
				\Session::flash('flash_type','alert-success');
		        \Session::flash('flash_message','Sukses, SPKL dengan id '.$id_spkl.' berhasil dibuat');
                return response()->json([
					'status' => 'success',
                    'message' => 'SPKL dengan id '.$id_spkl.' berhasil dibuat'
                ]);
			}
		} else {
			$delete_spkl = t_spkl::where('id_spkl','=',$id_spkl)->get();
			foreach ($delete_spkl as $delete_spkl) {
				$id_delete = $delete_spkl->id;
			}
			t_spkl::destroy($id_delete);
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, Karyawan yang anda masukan sudah tidak dapat dilemburkan, silakan hubungi Supervisor atau HR Personal Admin');
            return response()->json([
				'status' => 'Error',
                'message' => 'Karyawan yang anda masukan sudah tidak dapat dilemburkan, silakan hubungi Supervisor atau HR Personal Admin'
            ]);
	    }
	}

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_update($id,$id2)
	{
		$user 				= \Auth::user();
		$sub_section 		= m_employee::where('npk',$id)->get();
		foreach ($sub_section as $sub_section) {
			$code_sub_section = $sub_section->sub_section;
		}
		$m_category = m_category::all();
		$t_spkl_employee = t_spkl_detail::leftjoin('m_employees','m_employees.npk','=','t_spkl_details.npk')
											->where('t_spkl_details.sub_section',$code_sub_section)
											->where('t_spkl_details.is_closed',0)
											->where('t_spkl_details.is_clv',0)
											->get();
		$t_spkl_employee_update = t_spkl_detail::select('*','m_employees.nama as nama_employee')
												->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
												->where('t_spkl_details.sub_section',$id2)
												->where('t_spkl_details.is_closed',0)
												->where('t_spkl_details.is_clv',0)
												->where('t_spkl_details.npk',$id)
												->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			sub_section = "'.$code_sub_section.'" and is_closed = "0" and is_clv = "0" ');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            'sub_section' => $code_sub_section,
            'm_category' => $m_category,
            't_spkl_employee' => $t_spkl_employee,
            't_spkl_employee_update' => $t_spkl_employee_update,
            'check_employee' => $check_employee,
            'check_employee2' => $check_employee2,
            'jml' => $jml
        ];

        return response()->json($data);
	}

	public function spkl_planning_update_clv($id,$id2)
	{
		$user 		= \Auth::user();
		$m_category = m_category::all();
		$t_spkl_employee = t_spkl_detail::select('*','t_spkl_details.sub_section as sub_sections')
											->leftjoin('m_employees','m_employees.npk','=','t_spkl_details.npk')
											->where('t_spkl_details.sub_section','=',$id2)
											->where('t_spkl_details.is_closed','=',"0")
											->where('t_spkl_details.is_clv','=',"1")
											->get();
		$t_spkl_employee_update = t_spkl_detail::select('*','m_employees.nama as nama_employee')
												->leftjoin('m_employees','m_employees.npk','=','t_spkl_details.npk')
												->where('t_spkl_details.sub_section','=',$id2)
												->where('t_spkl_details.is_closed','=',"0")
												->where('t_spkl_details.is_clv','=',"1")
												->where('t_spkl_details.npk','=',$id)
												->where('t_spkl_details.status','=',"2")
												->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where sub_section = "'.$id2.'"
			and is_closed = "0" and is_clv = "1"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            'm_category' => $m_category,
            't_spkl_employee' => $t_spkl_employee,
            't_spkl_employee_update' => $t_spkl_employee_update,
            'check_employee' => $check_employee,
            'check_employee2' => $check_employee2,
            'jml' => $jml
        ];

		return response()->json($data);
    }
	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_2_update($id,$id2,$id3)
	{
		$t_spkl_employee_update = t_spkl_detail::select('*','m_employees.nama as nama_employee')
												->leftjoin('m_employees','m_employees.npk','=','t_spkl_details.npk')
												->where('t_spkl_details.sub_section',$id2)
												->where('t_spkl_details.id_spkl',$id3)
												->where('t_spkl_details.npk',$id)
												->get();
                                                return response()->json([
                                                    't_spkl_employee_update' => $t_spkl_employee_update
                                                ]);
	}
	public function spkl_planning_detail_update($id)
	{
		$t_spkl 	= t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl',$id)
							->groupBy('t_spkl_details.id_spkl')
							->get();
		$m_category = m_category::all();

        $data = [
            't_spkl' => $t_spkl,
            'm_category' => $m_category
        ];

        return response()->json($data);
	}

	public function spkl_planning_add_employee($id)
	{
		$user 		  = \Auth::user();
		$check_clv 	  = t_spkl::where('id_spkl','=',$id)->get();
		foreach ($check_clv as $check_clv) {
		 	$kolektif = $check_clv->kolektif;
		}
		$sub_section  = m_employee::where('npk',$user->npk)->get();
		foreach ($sub_section as $sub_section) {
			$sub_sections = $sub_section->sub_section;
		}
		if ($kolektif == '1') {
			$check_department = m_sub_section::join('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->join('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_sub_sections.code','=',$sub_sections)->get();
			foreach ($check_department as $check_department) {
				$code_department = $check_department->code_department;
			}
			$m_employee = m_employee::select('*','m_employees.npk as npk_employee','m_employees.nama as nama_employee')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									//hotfix-2.2.9, by Merio, 20161027, menambahkan where untuk filter mp yang sudah dimasukan ke dalam spkl
									->whereNotIn('m_employees.npk', function($q) use ($id) {
										$q->select('t_spkl_details.npk')
											->from('t_spkl_details')
											->where('t_spkl_details.is_closed','=','1')
											->where('t_spkl_details.is_clv','=','1')
											->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('m_employees.status_emp','=',1)
									->where('m_departments.code','=',$code_department)->get();
		} else {
			$m_employee = m_employee::select('*','npk as npk_employee','m_employees.nama as nama_employee')
									//hotfix-2.2.9, by Merio, 20161027, menambahkan where untuk filter mp yang sudah dimasukan ke dalam spkl
									->whereNotIn('m_employees.npk', function($q) use ($id) {
										$q->select('t_spkl_details.npk')
											->from('t_spkl_details')
											->where('t_spkl_details.is_closed','=','1')
											->where('t_spkl_details.is_clv','=','0')
											->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('status_emp','=',1)
									->where('sub_section',$sub_sections)->get();
		}
		return response()->json($m_employee);
	}
	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_update_save()
	{
		$user   = \Auth::user();
		$input 	= request::all();
		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as spkl_id')
								->where('npk',$input['npk'])
								->where('is_closed','=','0')
								->where('is_clv','=','0')
								->first();

		$id_spkl = $t_spkl->spkl_id;

		// hotfix-3.7.4, 20200916, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimeActual = $today . ' ' . $input['start_planning'];
		$endTimeActual = $today . ' ' . $input['end_planning'];

		if (strtotime($input['start_planning']) > strtotime($input['end_planning'])) {
			$endTimeActual = date('Y-m-d H:i', strtotime($endTimeActual . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimeActual) - strtotime($startTimeActual)) / 60,2);

		if ($inMinute < 30) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}

		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_planning'] == $input['end_planning']) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}

		$t_spkl_details					= t_spkl_detail::findOrFail($id_spkl);
		$t_spkl_details->start_planning = $input['start_planning'];
		$t_spkl_details->end_planning 	= $input['end_planning'];
		$t_spkl_details->notes 			= $input['notes'];
		$t_spkl_details->ref_code 		= $input['ref_no'];
		$t_spkl_details->npk_leader 	= $user->npk;
		$t_spkl_details->save();
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','Sukses, perubahan SPKL berhasil disimpan');
		return response()->json([
			'status' => 'success',
            'message' => 'Sukses, perubahan SPKL berhasil disimpan'
        ]);
	}

	//v1.0 by Merio, 20160113, method for input spkl
	public function spkl_planning_update_clv_save()
	{
		$user   = \Auth::user();
		$input 	= request::all();
		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as spkl_id')
								->where('npk',$input['npk'])
								->where('is_closed','=','0')
								->where('is_clv','=','1')
								->first();
		$id_spkl = $t_spkl->spkl_id;

		$id_spkl = $t_spkl->spkl_id;

		// hotfix-3.7.4, 20200916, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimeActual = $today . ' ' . $input['start_planning'];
		$endTimeActual = $today . ' ' . $input['end_planning'];

		if (strtotime($input['start_planning']) > strtotime($input['end_planning'])) {
			$endTimeActual = date('Y-m-d H:i', strtotime($endTimeActual . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimeActual) - strtotime($startTimeActual)) / 60,2);

		if ($inMinute < 30) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}

		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_planning'] == $input['end_planning']) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
			return response()->json([
				'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}
		$t_spkl_details					= t_spkl_detail::findOrFail($id_spkl);
		$t_spkl_details->start_planning = $input['start_planning'];
		$t_spkl_details->end_planning 	= $input['end_planning'];
		$t_spkl_details->notes 			= $input['notes'];
		$t_spkl_details->ref_code 		= $input['ref_no'];
		$t_spkl_details->npk_leader 	= $user->npk;
		$t_spkl_details->save();
	    Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, perubahan SPKL berhasil disimpan');
		return response()->json([
			'status' => 'success',
			'message' => 'perubahan SPKL berhasil disimpan',
		]);
	}

	public function spkl_planning_detail_update_save()
	{
		$input 			= request::all();
		$user 			= Auth::user();
		$id_spkl 		= $input['id_spkl'];
		$date 			= Carbon::now()->format('Ymd');
		$jam_server 	= Carbon::now()->format('Hi');
		$npk 			= $user->npk;
		$tanggal_awal 	= date('Ymd',strtotime($input['start_date_planning']));
		$month          = Carbon::parse($input['start_date_planning'])->format('n');
		if ($tanggal_awal == $date) {
			//Membedakan waktu input berdasarkan support dan tidak
			$check_department_user = m_employee::select('*','m_departments.code as name_department','m_sections.code as name_section')
												->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
												->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
												->join('m_departments','m_departments.code','=','m_sections.code_department')
												->where('m_employees.npk','=',$npk)->get();
			foreach ($check_department_user as $hasil_department) {
				$department_name 	= $hasil_department->name_department;
				$section_name 		= $hasil_department->name_section;
			}
			$open  = "0600";
			if ($department_name == "EGB" || $department_name == "EGU" || $department_name == "ITD" ||
				$department_name == "MTE" || $department_name == "QAS" || $department_name == "QBC" || $department_name == "QEC"
				|| $department_name == "OMC") {
				$closed  = "1600";
			} else {
				$closed  = "1500";
			}
			if ($jam_server < $open || $jam_server > $closed && $user->ot_par == "1" && $user->limit_mp <= 0) {
				\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, anda tidak dapat mengubah tanggal di SPKL untuk masa lampau, silakan ulangi proses');
                return response()->json([
                    'status' => 'Error',
                    'message' => 'anda tidak dapat mengubah tanggal di SPKL untuk masa lampau, silakan ulangi proses'
                ]);
			}
		} else if ($tanggal_awal < $date) {
			\Session::flash('flash_type','alert-danger');
		    \Session::flash('flash_message','Error, anda tidak dapat mengubah tanggal di SPKL untuk masa lampau, silakan ulangi proses');
            return response()->json([
                'status' => 'Error',
                'message' => 'anda tidak dapat mengubah tanggal di SPKL untuk masa lampau, silakan ulangi proses'
            ]);
		}
		//mencari type spkl
		$check_type 	= m_holiday::where('date_holiday',$input['start_date_planning'])->get();
		if (count($check_type) > 0) {
			foreach ($check_type as $check_type) {
				$type_spkl  = $check_type->type_holiday;
			}
		} else {
			$day    = date('N',strtotime($input['start_date_planning']));
	   		if ($day == "6" || $day == "7") {
	   			$type_spkl = "2";
	   		} else {
	   			$type_spkl = "1";
	   		}
		}

		$t_spkl 	= t_spkl::where('id_spkl',$id_spkl)->get();
		foreach ($t_spkl as $t_spkl) {
			$id_spkls = $t_spkl->id;
		}

		$t_spkls					= t_spkl::findOrFail($id_spkls);
		$t_spkls->type 				= $type_spkl;
		$t_spkls->category 			= $input['category'];
		$t_spkls->category_detail 	= $input['category_detail'];
		$t_spkls->note 				= $input['note'];
		$t_spkls->save();

		$t_spkl_details = t_spkl_detail::where('t_spkl_details.id_spkl',$id_spkl)->get();
		foreach ($t_spkl_details as $t_spkl_details) {
			$t_spkl_planning 				= t_spkl_detail::findOrFail($t_spkl_details->id);
			$t_spkl_planning->start_date 	= $input['start_date_planning'];

			// dev-1.6.0, Ferry, 20170518, Update harus dihitung ulang efeknya transport dan makan
			$carbon_start_dt 	= Carbon::parse($input['start_date_planning'].' '.$t_spkl_planning->start_planning);
			$carbon_end_dt 		= Carbon::parse($input['start_date_planning'].' '.$t_spkl_planning->end_planning);
			$t_spkl_planning->end_date		= $carbon_start_dt->lt($carbon_end_dt) ? $input['start_date_planning'] :
												$carbon_end_dt->addDay()->toDateString();
			$end_date_planning  			= $carbon_start_dt->lt($carbon_end_dt) ? $input['start_date_planning'] :
												$carbon_end_dt->addDay()->toDateString();
			// Tambah shift makan
			$t_spkl_planning->kd_shift_makan = m_shift::generateShiftMakan($carbon_start_dt, $carbon_end_dt, $type_spkl);

			// Tambah kode transport otomatis
			$npk_trans_code = m_employee::where('npk', $t_spkl_planning->npk)->pluck('transport');
			$trans_gen_code = m_shift::generateCodeTransport($carbon_start_dt, $carbon_end_dt, $npk_trans_code, $type_spkl);
			$t_spkl_planning->kd_shift_trans = $trans_gen_code->kd_shift;
			$t_spkl_planning->kd_trans = $trans_gen_code->code;
			// end dev-1.6.0
			//dev-1.6, by Merio Aji, 20160530, menambahkan perhitungan break dan quota overtime yang digunakan
			$date1	 		= Carbon::parse($input['start_date_planning'].' '.$t_spkl_planning->start_planning);
			$date2 			= Carbon::parse($t_spkl_planning->end_date.' '.$t_spkl_planning->end_planning);
			$total_ot_temp	= $date1->diffInMinutes($date2);

			//merubah format untuk mendapatkan hari break
			$start_day 						= Carbon::parse($input['start_date_planning'])->format('N');
			$end_day 						= Carbon::parse($end_date_planning)->format('N');

            $start_plannings 	= date('Hi',strtotime($t_spkl_planning->start_planning));
            $end_plannings 		= date('Hi',strtotime($t_spkl_planning->end_planning));
            //untuk menghitung durasi break
            if ($t_spkl_planning->start_date == $t_spkl_planning->end_date) {
            	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break as $check_break) {
            		$jml_duration = $check_break->jml;
            	}
            } else {
            	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=','2400')
            								->where('status_break','=','1')
            								->get();
            	$check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            								->where('day_break','=',$end_day)
            								->where('start_break','>=','0000')
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break1 as $check_break1) {
            		$jml_duration1 = $check_break1->jml1;
            	}
            	foreach ($check_break2 as $check_break2) {
            		$jml_duration2 = $check_break2->jml2;
            	}
            	$jml_duration = $jml_duration1+$jml_duration2;
            }

            $total_ot 					= $total_ot_temp-$jml_duration;

            //dev-2.0, 20160825, by Merio, pengembalian quota ke semula sebelum quota baru di update
			$employee_quota = m_employee::where('npk',$t_spkl_details->npk)->first();
			if ($month == 1) {
				$quota_used 	= $employee_quota->quota_used_1;
				$quota_remain 	= $employee_quota->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month == 2) {
				$quota_used 	= $employee_quota->quota_used_2;
				$quota_remain 	= $employee_quota->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month == 3) {
				$quota_used 	= $employee_quota->quota_used_3;
				$quota_remain 	= $employee_quota->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month == 4) {
				$quota_used 	= $employee_quota->quota_used_4;
				$quota_remain 	= $employee_quota->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month == 5) {
				$quota_used 	= $employee_quota->quota_used_5;
				$quota_remain 	= $employee_quota->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month == 6) {
				$quota_used 	= $employee_quota->quota_used_6;
				$quota_remain 	= $employee_quota->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month == 7) {
				$quota_used 	= $employee_quota->quota_used_7;
				$quota_remain 	= $employee_quota->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month == 8) {
				$quota_used 	= $employee_quota->quota_used_8;
				$quota_remain 	= $employee_quota->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month == 9) {
				$quota_used 	= $employee_quota->quota_used_9;
				$quota_remain 	= $employee_quota->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month == 10) {
				$quota_used 	= $employee_quota->quota_used_10;
				$quota_remain 	= $employee_quota->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month == 11) {
				$quota_used 	= $employee_quota->quota_used_11;
				$quota_remain 	= $employee_quota->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month == 12) {
				$quota_used 	= $employee_quota->quota_used_12;
				$quota_remain 	= $employee_quota->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
			$perkiraan_over_quota = ($quota_used-$t_spkl_planning->quota_ot)+$total_ot;
			if ($perkiraan_over_quota <= $quota_remain) {
				$t_spkl_planning->quota_ot 	= $total_ot;
				$t_spkl_planning->save();

				//dev-2.0, 20160825, by Merio, mengembalikan quota used
				$update_quota_emp = m_employee::findOrFail($employee_quota->id);
				$update_quota_emp->$quota_par = $perkiraan_over_quota;
				$update_quota_emp->save();
			}

		}
		// dev-1.6.0, Ferry
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL List employee was successfully updated');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL List employee was successfully updated',
		]);
	}

	public function spkl_planning_2_update_save()
	{
		$input   	= request::all();
		$npk    	= $input['npk'];
		$id_spkls 	= $input['id_spkl'];
		$t_spkl 	= t_spkl_detail::where('npk','=',$npk)
									->where('id_spkl','=',$id_spkls)->get();

		$today = date('Y-m-d');
		$startTimeActual = $today . ' ' . $input['start_planning'];
		$endTimeActual = $today . ' ' . $input['end_planning'];

		if (strtotime($input['start_planning']) > strtotime($input['end_planning'])) {
			$endTimeActual = date('Y-m-d H:i', strtotime($endTimeActual . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimeActual) - strtotime($startTimeActual)) / 60,2);

		if ($inMinute < 30) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu kerja harus lebih dari 30 menit'
            ]);
		}
		foreach ($t_spkl as $t_spkl) {
			$id_spkl 		= $t_spkl->id;
			$start_date 	= $t_spkl->start_date;
			$end_date 		= $t_spkl->end_date;
			$quota_ot_awal 	= $t_spkl->quota_ot;
		}
		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_planning'] == $input['end_planning']) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
	        return response()->json([
				'status' => 'Error',
                'message' => 'waktu awal dan akhir overtime tidak boleh sama'
            ]);
		}

		$t_spkl_details					= t_spkl_detail::findOrFail($id_spkl);
		$t_spkl_details->start_planning = $input['start_planning'];
		$t_spkl_details->end_planning 	= $input['end_planning'];
		$t_spkl_details->ref_code 		= $input['ref_no'];
		$t_spkl_details->notes 			= $input['notes'];
		//hotfix-2.0.4, by Merio, 20160830, mengubah tanggal jika terjadi abnormal
		$carbon_start_dt	= Carbon::parse($start_date.' '.$input['start_planning']);
		$carbon_end_dt 		= Carbon::parse($start_date.' '.$input['end_planning']);
		$end_date2			= $carbon_start_dt->lt($carbon_end_dt) ? $start_date :
										$carbon_end_dt->addDay()->toDateString();

		//hotfix-2.2.9, by Merio, 20161026, add script to update report makan saat update detail spkl
		$check_type = m_holiday::where('date_holiday',$start_date)->get();
		if (count($check_type) > 0) {
			foreach ($check_type as $check_type) {
				$type_spkl  = $check_type->type_holiday;
			}
		} else {
			$day = date('N',strtotime($start_date));
	   		if ($day == "6" || $day == "7") {
	   			$type_spkl = "2";
	   		} else {
	   			$type_spkl = "1";
	   		}
		}
		$t_spkl_details->kd_shift_makan = m_shift::generateShiftMakan($carbon_start_dt, $carbon_end_dt, $type_spkl);
		$npk_trans_code 				= m_employee::where('npk', $t_spkl_details->npk)->pluck('transport');
		$trans_gen_code 				= m_shift::generateCodeTransport($carbon_start_dt, $carbon_end_dt, $npk_trans_code, $type_spkl);
		$t_spkl_details->kd_shift_trans = $trans_gen_code->kd_shift;
		$t_spkl_details->kd_trans 		= $trans_gen_code->code;

		//perhitungan quota
		$date1	 						= Carbon::parse($start_date.' '.$input['start_planning']);
		$date2 							= Carbon::parse($end_date2.' '.$input['end_planning']);
		$total_ot_temp					= $date1 ->diffInMinutes($date2);

		//merubah format untuk mendapatkan hari break
		$start_day 						= Carbon::parse($start_date)->format('N');
		$end_day 						= Carbon::parse($end_date2)->format('N');
		$month_ot 						= Carbon::parse($start_date)->format('n');
		$employee_quota = m_employee::where('npk',$npk)->first();
		if ($month_ot == 1) {
			$quota_used 	= $employee_quota->quota_used_1;
			$quota_remain 	= $employee_quota->quota_remain_1;
			$quota_par 		= "quota_used_1";
		} else if ($month_ot == 2) {
			$quota_used 	= $employee_quota->quota_used_2;
			$quota_remain 	= $employee_quota->quota_remain_2;
			$quota_par 		= "quota_used_2";
		} else if ($month_ot == 3) {
			$quota_used 	= $employee_quota->quota_used_3;
			$quota_remain 	= $employee_quota->quota_remain_3;
			$quota_par 		= "quota_used_3";
		} else if ($month_ot == 4) {
			$quota_used 	= $employee_quota->quota_used_4;
			$quota_remain 	= $employee_quota->quota_remain_4;
			$quota_par 		= "quota_used_4";
		} else if ($month_ot == 5) {
			$quota_used 	= $employee_quota->quota_used_5;
			$quota_remain 	= $employee_quota->quota_remain_5;
			$quota_par 		= "quota_used_5";
		} else if ($month_ot == 6) {
			$quota_used 	= $employee_quota->quota_used_6;
			$quota_remain 	= $employee_quota->quota_remain_6;
			$quota_par 		= "quota_used_6";
		} else if ($month_ot == 7) {
			$quota_used 	= $employee_quota->quota_used_7;
			$quota_remain 	= $employee_quota->quota_remain_7;
			$quota_par 		= "quota_used_7";
		} else if ($month_ot == 8) {
			$quota_used 	= $employee_quota->quota_used_8;
			$quota_remain 	= $employee_quota->quota_remain_8;
			$quota_par 		= "quota_used_8";
		} else if ($month_ot == 9) {
			$quota_used 	= $employee_quota->quota_used_9;
			$quota_remain 	= $employee_quota->quota_remain_9;
			$quota_par 		= "quota_used_9";
		} else if ($month_ot == 10) {
			$quota_used 	= $employee_quota->quota_used_10;
			$quota_remain 	= $employee_quota->quota_remain_10;
			$quota_par 		= "quota_used_10";
		} else if ($month_ot == 11) {
			$quota_used 	= $employee_quota->quota_used_11;
			$quota_remain 	= $employee_quota->quota_remain_11;
			$quota_par 		= "quota_used_11";
		} else if ($month_ot == 12) {
			$quota_used 	= $employee_quota->quota_used_12;
			$quota_remain 	= $employee_quota->quota_remain_12;
			$quota_par 		= "quota_used_12";
		}

        $start_plannings 	= date('Hi',strtotime($input['start_planning']));
        $end_plannings 		= date('Hi',strtotime($input['end_planning']));
        //untuk menghitung durasi break
        if ($start_day == $end_day) {
        	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            							->where('day_break','=',$start_day)
            							->where('start_break','>=',$start_plannings)
            							->where('end_break','<=',$end_plannings)
            							->where('status_break','=','1')
            							->get();
            foreach ($check_break as $check_break) {
            	$jml_duration = $check_break->jml;
            }
       	} else {
        	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            							->where('day_break','=',$start_day)
            							->where('start_break','>=',$start_plannings)
            							->where('end_break','<=','2400')
            							->where('status_break','=','1')
            							->get();
            $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            							->where('day_break','=',$end_day)
            							->where('start_break','>=','0000')
            							->where('end_break','<=',$end_plannings)
            							->where('status_break','=','1')
            							->get();
            foreach ($check_break1 as $check_break1) {
            	$jml_duration1 = $check_break1->jml1;
            }
            foreach ($check_break2 as $check_break2) {
            	$jml_duration2 = $check_break2->jml2;
            }
            $jml_duration = $jml_duration1+$jml_duration2;
        }
        $total_ot 					= $total_ot_temp-$jml_duration;
        $pengecekan_quota 			= ($quota_used-$quota_ot_awal)+$total_ot;

        $npk_spkl = m_employee::where('npk',$npk)->first();

				//mencari sub section
				$div_code = $npk_spkl->hasSubSection->hasSection->hasDepartment->hasDivision->code;

				//menemukan quota limit GM
				$special_limit = m_spesial_limits::where('sub_section', $div_code)->first();

				$limit_weekday = $special_limit->quota_limit_weekday;
				$limit_holiday = $special_limit->quota_limit_holiday;

				//temukan tanggal weekend dan holiday
				$cek_its_holiday = m_holiday::where('date_holiday', $start_date)->first();

				$cek_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk)->first();

				//delete npk ketika sudah ditambah ke tabel approved
				$delete_approved = t_approved_limit_spesial::where('npk',$npk_spkl->npk);

				$date1	 						= Carbon::parse($start_date.' '.$input['start_planning']);
				$date2 							= Carbon::parse($end_date2.' '.$input['end_planning']);
				$hasil_selisih					= $date1->diffInMinutes($date2);

				if ($cek_its_holiday) {

					$carbon_tgl_inputan = Carbon::parse($start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_holiday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumHoliday'))
														->whereIn('start_date', $arr)
														->where('npk', $npk_spkl->npk)
														->get();

					//mendapatkan remain quota holiday
					$remain_holiday = $limit_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

					if (($remain_holiday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_holiday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
                            'message' => 'Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);

					}
					elseif (($remain_holiday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						// $created_at = Carbon::parse($cek_approved->created_at)->format('Y-m-d');
						// $cek_bulan_approve = Carbon::createFromFormat('Y-m-d', $created_at);
						// $tgl_input = Carbon::createFromFormat('Y-m-d', $t_spkl_planning->start_date);

						$remain_holiday = $hrd_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

						if ($remain_holiday < 0) {

							$limit_by_jam = $hrd_holiday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Holiday  HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

					}

				}
				else {

					$carbon_tgl_inputan = Carbon::parse($start_date)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_weekday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumWeekday'))
														->whereNotIn('start_date', $arr)
														->where('start_date', 'like', '%' . $carbon_tgl_inputan . '%')
														->where('npk', $npk_spkl->npk)
														->get();
					//mendapatkan remain quota weekday
					$remain_weekday = $limit_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

					if (($remain_weekday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_weekday/60;
						\Session::flash('flash_type','alert-danger');
			    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL');
			    		return response()->json([
							'status' => 'Error',
                            'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday, silakan hubungi GM untuk membuka akses membuat SPKL'
                        ]);
					}
					elseif (($remain_weekday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						// $cek_bulan_approve = Carbon::parse($cek_approved->created_at)->format('Y-m');

						$remain_weekday = $hrd_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

						if ($remain_weekday < 0) {

							$limit_by_jam = $hrd_weekday/60;
							\Session::flash('flash_type','alert-danger');
				    		\Session::flash('flash_message','Error, Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL');
				    		return response()->json([
								'status' => 'Error',
                                'message' => 'Quota anda sudah melebihi '.$limit_by_jam.' jam parameter Weekday HRD, Anda Sudah tidak dapat membuat SPKL'
                            ]);
						}

					}
				}

        if ($pengecekan_quota > $quota_remain) {
        	\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, kuota karyawan yang anda update sudah habis, silakan hubungi Dept Head');
			return response()->json([
				'status' => 'Error',
                'message' => 'kuota karyawan yang anda update sudah habis, silakan hubungi Dept Head'
            ]);
        } else if ($pengecekan_quota <= $quota_remain) {
	        $t_spkl_details->start_date = $start_date;
			$t_spkl_details->end_date 	= $end_date2;
	        $t_spkl_details->quota_ot 	= $total_ot;
			$t_spkl_details->save();

			$update_quota_mp 				= m_employee::findOrFail($employee_quota->id);
			$update_quota_mp->$quota_par 	= $pengecekan_quota;
			$update_quota_mp->save();

			\Session::flash('flash_type','alert-success');
	        \Session::flash('flash_message','SPKL List employee was successfully updated');
			return response()->json([
        'status' => 'success',
        'message' => 'SPKL List employee was successfully updated',
    ]);
		}
	}
	//v1.0 by Merio, 20160113, method for view skpl
	public function spkl_planning_approval()
	{
		$par 	= "1";
		$t_spkl_all = t_spkl::select('*','t_spkls.id as spkl_id')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkl_details.status','=','1')
							->groupBy('t_spkls.id_spkl')
							->get();

                            $data = [
                                't_spkl_all' => $t_spkl_all,
                                'par' => $par,
                            ];

                            return response()->json($data);
	}
	//v1.0 by Merio, 20160113, method for view skpl detail (show list member)
	public function spkl_planning_approval_list($id)
	{
		$par 	= "2";
		$t_spkl = t_spkl_employee::join('m_employees','m_employees.npk','=','t_spkl_employees.npk')
									->where('t_spkl_employees.id_spkl','=',$id)
									->where('t_spkl_employees.status','=','1')
									->get();
		$t_spkl_all = t_spkl::select('*','t_spkls.id as spkl_id')
								->join('t_spkl_employees','t_spkl_employees.id_spkl','=','t_spkls.id_spkl')
								->where('t_spkl_employees.status','=','1')
								->groupBy('t_spkls.id_spkl')
								->get();
								return response()->json([
									'status' => 'success',
									'data' => [
										't_spkl' => $t_spkl,
										't_spkl_all' => $t_spkl_all
									]
									]);

	}

	//v1.0 by Merio, 20160113, method for approve skpl (all / id skpl)
	public function spkl_planning_approve_1($id)
	{

		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');

		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_departments;
			$check_emp 			= m_employee::where('npk','=',$npk)->get();

			foreach ($check_emp as $check_emp) {
				$occupation 		= $check_emp->occupation;
				$employment_status	= $check_emp->employment_status;
			}
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 		= 0; // dev-1.7, by Merio, Memberi nilai awal agar menghindari error
			// $quota_approve 		= 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= "2";
				$t_spkl_employees->approval_1_planning_date = "$date";
				$t_spkl_employees->save();
			// }
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved'
		]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl (all / id skpl)
	public function spkl_planning_reject($id)
	{

		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','2')
		                    		->orWhere('t_spkl_details.status','1');
		                		})
								->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;
			// hotfix 1.8.2 20160714 by andre, pengembalian kuota apabila reject supervisor
			$npk 							= $t_spkls->npk;
			$status_terakhir				= $t_spkls->status;
			$quota_used_mp					= $t_spkls->quota_ot;
			$date_ot 						= $t_spkls->start_date;
			$month 							= Carbon::parse($date_ot)->format('m');
			if ($status_terakhir=='2' || $status_terakhir=='1'){
			$check_emp 						= m_employee::where('npk','=',$npk)->get();
			foreach ($check_emp as $check_emp) {
				$occupation 				= $check_emp->occupation;
				$id_employee 				= $check_emp->id;
				$employment_status			= $check_emp->employment_status;
				if ($month == "01") {
					$quota_used 	= $check_emp->quota_used_1;
					$quota_remain 	= $check_emp->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month == "02") {
					$quota_used 	= $check_emp->quota_used_2;
					$quota_remain 	= $check_emp->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month == "03") {
					$quota_used 	= $check_emp->quota_used_3;
					$quota_remain 	= $check_emp->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month == "04") {
					$quota_used 	= $check_emp->quota_used_4;
					$quota_remain 	= $check_emp->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month == "05") {
					$quota_used 	= $check_emp->quota_used_5;
					$quota_remain 	= $check_emp->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month == "06") {
					$quota_used 	= $check_emp->quota_used_6;
					$quota_remain 	= $check_emp->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month == "07") {
					$quota_used 	= $check_emp->quota_used_7;
					$quota_remain 	= $check_emp->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month == "08") {
					$quota_used 	= $check_emp->quota_used_8;
					$quota_remain 	= $check_emp->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month == "09") {
					$quota_used 	= $check_emp->quota_used_9;
					$quota_remain 	= $check_emp->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month == "10") {
					$quota_used 	= $check_emp->quota_used_10;
					$quota_remain 	= $check_emp->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month == "11") {
					$quota_used 	= $check_emp->quota_used_11;
					$quota_remain 	= $check_emp->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month == "12") {
					$quota_used 	= $check_emp->quota_used_12;
					$quota_remain 	= $check_emp->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			$pengembalian_quota  			= $quota_used-$quota_used_mp;
			$check_emp 						= m_employee::findOrFail($id_employee);
			$check_emp->$quota_par 			= $pengembalian_quota;
			$check_emp->save();
			}}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-1";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl (all / id skpl)
	public function spkl_planning_reject_2($id)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
			                		$q->where('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','3')
			                    		->orWhere('t_spkl_details.status','2');
			                	})->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id;
			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 				= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '2' || $status_terakhir == '3'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used-$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-2";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl (all / id skpl)
	public function spkl_planning_reject_3($id)
	{
		$date = Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','3');
		                		})
								->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;
			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '3' || $status_terakhir == '4') {
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used-$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-3";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_planning_reject_member($id, $id2)
	{
		$date = Carbon::now()->format('Y-m-d H:i:s');

		$t_spkl = t_spkl_employee::where('id_spkl',$id)
									->where('npk',$id2)
									->get();
		foreach ($t_spkl as $t_spkls) {
			$id 							= $t_spkls->id;
			$t_spkl_employees 				= t_spkl_employee::findOrFail($id);
			$t_spkl_employees->status 		= "-1";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}

	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_planning_approval_1()
	{
		$t_spkl 		 	= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";

        $data = [
            'status' => 'success',
			't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_planning_approval_2()
	{
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";

        $data = [
            'status' => 'success',
			't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_planning_approval_3()
	{
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";

        $data = [
            'status' => 'success',
			't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2
        ];

		return response()->json($data);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_approval_search_result($id)
	{
		$user    = Auth::user();
		$input 	 = request::all();
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->where('t_spkls.id_spkl',$id)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2');
	                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
								->where('t_spkls.id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','1')
		                    		->orWhere('t_spkl_details.status','2');
	                			})
								->groupBy('m_employees.npk')->get();
		$queries2 	= DB::select('select count(npk) as count from t_spkl_details where
			(status="1" or status="-1" or status="2") and id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);

		$queries_quota 	= DB::select('select sum(quota_ot) as quota from t_spkl_details where
			(status="2" or status="1") and id_spkl="'.$id.'"');
        $result_quota 	= new Collection($queries_quota);

        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');

        $year 	= Carbon::now()->format('Y');
        $check_department = m_employee::select('*','m_departments.code as code_department')
        									->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
	        								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	        								->where('m_employees.npk','=',$user->npk)
	        								->get();
	    foreach ($check_department as $check_department) {
	    	$code_department = $check_department->code_department;
	    }
	    $quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_original_2 	= new Collection($quota_original_1);

        $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_used_2 	= new Collection($quota_used_1);

        $data = [
            'status' => 'success',
			't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'queries2' => $queries2,
			'result2' => $result2,
			'queries_quota' => $queries_quota,
			'result_quota' => $result_quota,
			'check_month' => $check_month,
			'month' => $month,
			'year' => $year,
			'check_department' => $check_department,
			'code_department' => $code_department,
			'quota_original_1' => $quota_original_1,
			'quota_original_2' => $quota_original_2,
			'quota_used_1' => $quota_used_1,
			'quota_used_2' => $quota_used_2,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_approval_search_result_2($id)
	{
		$user    = Auth::user();
		$input 	 = request::all();
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->where('t_spkls.id_spkl',$id)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3');
	                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
								->where('t_spkls.id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-2')
		                    		->orWhere('t_spkl_details.status','2')
		                    		->orWhere('t_spkl_details.status','3');
	                			})
								->groupBy('m_employees.npk')->get();
		$queries2 	= DB::select('select count(npk) as count from t_spkl_details where
			(status="2" or status="-2" or status="3") and id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);

		$queries_quota 	= DB::select('select sum(quota_ot) as quota from t_spkl_details where
			(status="2" or status="3") and id_spkl="'.$id.'"');
        $result_quota 	= new Collection($queries_quota);

        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');

        $year 	= Carbon::now()->format('Y');
        $check_department = m_employee::select('*','m_departments.code as code_department')
        									->leftjoin('m_departments','m_departments.code','=','m_employees.sub_section')
	        								->where('m_employees.npk','=',$user->npk)
	        								->get();
	    foreach ($check_department as $check_department) {
	    	$code_department = $check_department->code_department;
	    }
	    $quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_original_2 	= new Collection($quota_original_1);

        $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_used_2 	= new Collection($quota_used_1);

        $data = [
            'status' => 'success',
			't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'queries2' => $queries2,
			'result2' => $result2,
			'queries_quota' => $queries_quota,
			'result_quota' => $result_quota,
			'check_month' => $check_month,
			'month' => $month,
			'year' => $year,
			'check_department' => $check_department,
			'code_department' => $code_department,
			'quota_original_1' => $quota_original_1,
			'quota_original_2' => $quota_original_2,
			'quota_used_1' => $quota_used_1,
			'quota_used_2' => $quota_used_2,
        ];

		return response()->json($data);

	}
	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_approval_search_result_3($id)
	{
		$input 	 = request::all();
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->where('t_spkls.id_spkl',$id)
							->where ( function ($q) {
	                			$q->where('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4');
	                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
								->where('t_spkls.id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','3')
		                    		->orWhere('t_spkl_details.status','4');
	                			})
								->groupBy('m_employees.npk')->get();
		$queries2 	= DB::select('select count(npk) as count from t_spkl_details where
			(status="3" or status="-3" or status="4") and id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);

		$queries_quota 	= DB::select('select sum(quota_ot) as quota from t_spkl_details where
			(status="3" or status="4") and id_spkl="'.$id.'"');
        $result_quota 	= new Collection($queries_quota);

        $check_npk = t_spkl_detail::where('id_spkl','=',$id)->get();
        foreach ($check_npk as $check_npk) {
        	$npk_spkl = $check_npk->npk;
        }

        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');

        $year 	= Carbon::now()->format('Y');
        $check_department = m_employee::select('*','m_departments.code as code_department')
        									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
        									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
	        								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	        								->where('m_employees.npk','=',$npk_spkl)
	        								->get();
	    foreach ($check_department as $check_department) {
	    	$code_department = $check_department->code_department;
	    }
	    $quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_original_2 	= new Collection($quota_original_1);

        $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
        $quota_used_2 	= new Collection($quota_used_1);

        $data = [
            'status' => 'success',
        't_spkl' => $t_spkl,
        't_spkl_employee' => $t_spkl_employee,
        'queries2' => $queries2,
        'result2' => $result2,
        'queries_quota' => $queries_quota,
        'result_quota' => $result_quota,
        'check_npk' => $check_npk,
        'check_month' => $check_month,
        'month' => $month,
        'year' => $year,
        'check_department' => $check_department,
        'code_department' => $code_department,
        'quota_original_1' => $quota_original_1,
        'quota_original_2' => $quota_original_2,
        'quota_used_1' => $quota_used_1,
        'quota_used_2' => $quota_used_2,
        ];

    return response()->json($data);
	}

	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_planning_approve_member_1($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		// $year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)
								->where('t_spkl_details.npk','=',$id2)->get();

		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_department;
			$date_ot 			= $t_spkls->start_date;
			$month 				= Carbon::parse($date_ot)->format('m');
			if ($status_terakhir=='-1'){
			$check_emp 			= m_employee::where('npk','=',$id2)->get();
			foreach ($check_emp as $check_emp) {
				$occupation 		= $check_emp->occupation;
				$id_employee 		= $check_emp->id;
				$employment_status	= $check_emp->employment_status;
				if ($month == "01") {
					$quota_used 	= $check_emp->quota_used_1;
					$quota_remain 	= $check_emp->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month == "02") {
					$quota_used 	= $check_emp->quota_used_2;
					$quota_remain 	= $check_emp->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month == "03") {
					$quota_used 	= $check_emp->quota_used_3;
					$quota_remain 	= $check_emp->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month == "04") {
					$quota_used 	= $check_emp->quota_used_4;
					$quota_remain 	= $check_emp->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month == "05") {
					$quota_used 	= $check_emp->quota_used_5;
					$quota_remain 	= $check_emp->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month == "06") {
					$quota_used 	= $check_emp->quota_used_6;
					$quota_remain 	= $check_emp->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month == "07") {
					$quota_used 	= $check_emp->quota_used_7;
					$quota_remain 	= $check_emp->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month == "08") {
					$quota_used 	= $check_emp->quota_used_8;
					$quota_remain 	= $check_emp->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month == "09") {
					$quota_used 	= $check_emp->quota_used_9;
					$quota_remain 	= $check_emp->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month == "10") {
					$quota_used 	= $check_emp->quota_used_10;
					$quota_remain 	= $check_emp->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month == "11") {
					$quota_used 	= $check_emp->quota_used_11;
					$quota_remain 	= $check_emp->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month == "12") {
					$quota_used 	= $check_emp->quota_used_12;
					$quota_remain 	= $check_emp->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}
			$penambahan_quota  			    = $quota_used+$quota_used_mp;
			$check_emp 						= m_employee::findOrFail($id_employee);
			$check_emp->$quota_par 			= $penambahan_quota;
			$check_emp->save();
			}}
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 		= 0; // dev-1.7, by Merio, Memberi nilai awal agar menghindari error
			// $quota_approve 		= 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
			$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 					= "2";
			$t_spkl_employees->approval_1_planning_date = "$date";
			$t_spkl_employees->save();
			// }

		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved',
		]);
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_planning_approve_member_2($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');

		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)
								->where('t_spkl_details.npk','=',$id2)->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_department;
			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');

			if ($status_terakhir == '-2'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used+$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			// $check_emp 			= m_employee::where('npk','=',$id2)->get();

			// foreach ($check_emp as $check_emp) {
			// 	$occupation 		= $check_emp->occupation;
			// 	$employment_status	= $check_emp->employment_status;
			// }
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 		= 0; // dev-1.7, by Merio, Memberi nilai awal agar menghindari error
			// $quota_approve 		= 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
				$sub_section = t_spkl_detail::where('id_spkl','=',$id)
										->groupBy('id_spkl')
										->get();
				foreach ($sub_section as $sub_section) {
					$code_sub_section = $sub_section->sub_section;
				}
				$check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
				foreach ($check_code_section as $check_code_section) {
					$code_section = $check_code_section->code_section;
				}
				$check_section = m_section::where('code','=',$code_section)->get();
				foreach ($check_section as $check_section) {
						$code_department = $check_section->code_department;
				}
				$check_department = m_department::where('code','=',$code_department)->get();
				foreach ($check_department as $check_department) {
					$code_division 	= $check_department->code_division;
				}
				$check_division = m_division::where('code','=',$code_division)->get();
				foreach ($check_division as $check_division) {
					$npk_gm = $check_division->npk;
				}
				$status = "3";
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}

				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= $status;
				if ($status == 4) {
					$t_spkl_employees->approval_3_planning_date = "$date";
				}
				$t_spkl_employees->approval_2_planning_date = "$date";
				$t_spkl_employees->save();
			// }
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved',
		]);
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_planning_approve_member_3($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');

		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)
								->where('t_spkl_details.npk','=',$id2)->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_department;

			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '-3'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used+$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			// $check_emp 			= m_employee::where('npk','=',$id2)->get();

			// foreach ($check_emp as $check_emp) {
			// 	$occupation 		= $check_emp->occupation;
			// 	$employment_status	= $check_emp->employment_status;
			// }
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 		= 0; // dev-1.7, by Merio, Memberi nilai awal agar menghindari error
			// $quota_approve 		= 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= "4";
				$t_spkl_employees->approval_3_planning_date = "$date";
				$t_spkl_employees->save();
			// }
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved',
		]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_planning_reject_member_1($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();
		// hotfix 1.8.2 20160714 by andre, pengembalian kuota apabila reject supervisor
		foreach ($t_spkl as $t_spkl) {
			$npk 							= $t_spkl->npk;
			$quota_used_mp					= $t_spkl->quota_ot;
			$status_terakhir 				= $t_spkl->status;
			$date_ot 						= $t_spkl->start_date;
			$month 							= Carbon::parse($date_ot)->format('m');
			if ($status_terakhir=='2' || $status_terakhir=='10'){
			$check_emp 						= m_employee::where('npk','=',$npk)->get();
			foreach ($check_emp as $check_emp) {
				$occupation 				= $check_emp->occupation;
				$id_employee 				= $check_emp->id;
				$employment_status			= $check_emp->employment_status;
				if ($month == "01") {
					$quota_used 	= $check_emp->quota_used_1;
					$quota_remain 	= $check_emp->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month == "02") {
					$quota_used 	= $check_emp->quota_used_2;
					$quota_remain 	= $check_emp->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month == "03") {
					$quota_used 	= $check_emp->quota_used_3;
					$quota_remain 	= $check_emp->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month == "04") {
					$quota_used 	= $check_emp->quota_used_4;
					$quota_remain 	= $check_emp->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month == "05") {
					$quota_used 	= $check_emp->quota_used_5;
					$quota_remain 	= $check_emp->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month == "06") {
					$quota_used 	= $check_emp->quota_used_6;
					$quota_remain 	= $check_emp->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month == "07") {
					$quota_used 	= $check_emp->quota_used_7;
					$quota_remain 	= $check_emp->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month == "08") {
					$quota_used 	= $check_emp->quota_used_8;
					$quota_remain 	= $check_emp->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month == "09") {
					$quota_used 	= $check_emp->quota_used_9;
					$quota_remain 	= $check_emp->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month == "10") {
					$quota_used 	= $check_emp->quota_used_10;
					$quota_remain 	= $check_emp->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month == "11") {
					$quota_used 	= $check_emp->quota_used_11;
					$quota_remain 	= $check_emp->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month == "12") {
					$quota_used 	= $check_emp->quota_used_12;
					$quota_remain 	= $check_emp->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			$pengembalian_quota  			= $quota_used-$quota_used_mp;
			$check_emp 						= m_employee::findOrFail($id_employee);
			$check_emp->$quota_par 			= $pengembalian_quota;
			$check_emp->save();
			}}}
		foreach ($t_spkl as $t_spkls) {
			$ids 								= $t_spkl->id;
			$t_spkl_employees 					= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 			= "-1";
			$t_spkl_employees->reject_date 		= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_planning_reject_member_2($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;
			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '2' || $status_terakhir == '3'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used-$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-2";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_planning_reject_member_3($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;
			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject gm
			$npk 				= $t_spkls->npk;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '3' || $status_terakhir == '4'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used-$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-3";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}

	// ************* SPKL Actual Here **************** //
	//v1.0 by Merio, 20160120, method for input spkl actual
	public function spkl_actual_input()
	{
		$user 			= \Auth::user();
		$npk 			= $user->npk;
		$sub_section 	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_section as $sub_section) {
			$sub_sections = $sub_section->sub_section;
		}
		$id_spkl = t_spkl_detail::where('sub_section','=',$sub_sections)
								->where('status','=','4')
								->where('is_closed','=','1')
		                		->groupBy('id_spkl')
								->get();
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";
		$a 					= "1";
		$jml 				= "0";
		return response()->json('spkl.input_actual', compact('jml','t_spkl','t_spkl_employee','id','id2','id_spkl','a'));
	}
	//v1.0 by Merio, 20160120, method untuk hasil search spkl actual
	public function spkl_actual_result()
	{
		$input 	 	= request::all();
		$id_spkl 	= $input['id_spkl'];
		$id 		= "1";
		$id2 		= "1";
		$t_spkl  	= t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
								->where('t_spkls.id_spkl','=',$id_spkl)
								->where ( function ($q) {
				                	$q->where('t_spkl_details.status','-1')
				                    	->orWhere('t_spkl_details.status','-2')
				                    	->orWhere('t_spkl_details.status','-3')
				                    	->orWhere('t_spkl_details.status','4')
				                    	->orWhere('t_spkl_details.status','5')
				                    	->orWhere('t_spkl_details.status','6');
				                	})
								->groupBy('t_spkls.id_spkl')
								->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkls.id_spkl',$id_spkl)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','-1')
			                    		->orWhere('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','-3')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                		})
									->groupBy('m_employees.npk')
									->get();
		$a = '2';
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
		(status = -1 or status = -2 or status = -3 or status = 4 or status = 5 or status = 6) and
		id_spkl = "'.$id_spkl.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'jml' => $jml,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160120, method untuk hasil search spkl actual
	public function spkl_actual_result2($id)
	{
		$input 	 	= request::all();
		$id_spkl 	= $id;
		$id 		= "1";
		$id2 		= "1";
		$t_spkl  	= t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
								->where('t_spkls.id_spkl','=',$id_spkl)
								->where ( function ($q) {
			                		$q->where('t_spkl_details.status','-1')
			                    		->orWhere('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','-3')
			                    		->orWhere('t_spkl_details.status','-7')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                		})
								->groupBy('t_spkls.id_spkl')
								->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkls.id_spkl',$id_spkl)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','-1')
			                    		->orWhere('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','-3')
			                    		->orWhere('t_spkl_details.status','-7')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                		})
									->groupBy('m_employees.npk')
									->get();
		$a = '2';
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
		(status = -1 or status = -2 or status = -3 or status = -7 or status = 4 or status = 5 or status = 6) and
		id_spkl = "'.$id_spkl.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'jml' => $jml,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160121, method untuk add actual date for SPKL Realisasi
	public function spkl_actual_create($id,$id2)
	{
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->where('t_spkls.id_spkl',$id)
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employees = t_spkl::select('*','t_spkl_details.id as id_spkl_detail')
									->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkls.id_spkl',$id)
									->where('t_spkl_details.npk','=',$id2)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','-1')
			                    		->orWhere('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','-3')
			                    		->orWhere('t_spkl_details.status','-7')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                		})
									->groupBy('t_spkls.id_spkl')
									->get();
		$id_spkl = $id;
		foreach ($t_spkl_employees as $t_spkl_employees) {
			$start_planning = $t_spkl_employees->start_planning;
			$end_planning   = $t_spkl_employees->end_planning;
			$start_actual 	= $t_spkl_employees->start_actual;
			$end_actual 	= $t_spkl_employees->end_actual;
			//dev-2.1, 20160902, by Merio, get system_in & system_out
			$id_spkl_detail = $t_spkl_employees->id_spkl_detail;
			$system_in 		= $t_spkl_employees->system_in;
			$system_out 	= $t_spkl_employees->system_out;
			$npk_edited 	= $t_spkl_employees->npk_edited;

			if ($start_actual != "00:00:00" || $end_actual != "00:00:00") {
				$timin = date('H:i',strtotime($start_actual));
				$timot = date('H:i',strtotime($end_actual));
			} else {
				$start_date  = $t_spkl_employees->start_date;
				$start_date2 = date('Ymd',strtotime($start_date));
				try {
					$actual = DB::connection('sqlsrv')->select('select * from attdly1 where empno='.$id2.'
					and schdt='.$start_date2.'');
					//dev-2.1, 20160901, by Merio, jika connect sql server tapi tidak menemukan data
					if ($actual) {
						foreach ($actual as $actual) {
							//dev-2.1, 20160902, by Merio, update t_spkl_details system_in & system_out kalau pertama create actual
							$planning_ot 	= Carbon::parse($end_planning)->format('Hi');
							$prick_out 		= Carbon::parse($actual->timot)->format('Hi');
							//hotfix-2.1.1, 20160907, by Merio, jika timot ada maka system in dan out disimpan
							if ($actual->timot) {
								if ($planning_ot < $prick_out) {
									$final_end_time = Carbon::parse($planning_ot)->format('H:i:s');
								} else {
									$final_end_time = Carbon::parse($prick_out)->format('H:i:s');
								}
								$update_in_out_system 				= t_spkl_detail::findOrFail($id_spkl_detail);
								$update_in_out_system->system_in 	= $start_planning;
								$update_in_out_system->system_out 	= $final_end_time;
								$update_in_out_system->save();
							}

							if ($actual->stmot == "9999") {
								$timin = Carbon::parse($start_planning)->format('H:i');
								$timot = Carbon::parse($final_end_time)->format('H:i');
								// if (trim($actual->timin) == "") {
								// 	$timin = "Anda belum prick masuk";
								// }
								if (trim($actual->timot) == "") {
									$timot = "Anda belum prick keluar";
								}
							} else {
								$timin = Carbon::parse($start_planning)->format('H:i');
								$timot = Carbon::parse($final_end_time)->format('H:i');
								if (trim($actual->timot) == "") {
									$timot = "Anda belum prick keluar";
								}
							}
						}
					//dev-2.1, 20160901, by Merio, else nya
					} else {
						$timin = Carbon::parse($start_planning)->format('H:i');
						$timot = 'HRD belum input absensi';
					}
				} catch (\Exception $e) {
					$timin = Carbon::parse($start_planning)->format('H:i');
					$timot = Carbon::parse($end_planning)->format('H:i');
				}
			}
		}
		//hotfix-1.5.18, by Merio Aji, 20161505, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			(status = 4 or status = 5 or status = 6 or status = 7 or status = -1 or status = -2 or status = -3
				or status = -4 or status = -5 or status = -6 or status = -7) and
			id_spkl = "'.$id.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkls.id_spkl',$id)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','-1')
			                    		->orWhere('t_spkl_details.status','-2')
			                    		->orWhere('t_spkl_details.status','-3')
			                    		->orWhere('t_spkl_details.status','-7')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6');
			                		})->get();
		$a 		= '2';
		$nama 	= $t_spkl_employees->nama;
		$npk  	= $t_spkl_employees->npk;
		//dev-2.1, 20160905, by Merio, memberi nilai awal placeholder
		$ngambil_data_in_out = t_spkl_detail::where('id',$id_spkl_detail)->get();
		foreach ($ngambil_data_in_out as $ngambil_data_in_out) {
			$system_in2  = $ngambil_data_in_out->system_in;
			$system_out2 = $ngambil_data_in_out->system_out;
		}
		$start_par 	= Carbon::parse($system_in2)->format('H:i');
		$end_par 	= Carbon::parse($system_out2)->format('H:i');

        $data = [
            't_spkl' => $t_spkl,
            't_spkl_employees' => $t_spkl_employees,
            'jml' => $jml,
            't_spkl_employee' => $t_spkl_employee,
            'a' => $a,
            'nama' => $nama,
            'npk' => $npk,
            'start_par' => $start_par,
            'end_par' => $end_par,
            'timin' => $timin,
            'timot' => $timot,
        ];

		 return response()->json($data);
}

	public function spkl_actual_sinkron($id,$id2)
	{
		$tanggal_server 	= date('Y-m-d H:i:s');
		$kolektif 			= t_spkl::where('id_spkl','=',$id)->get();
		foreach ($kolektif as $kolektif) {
			$is_kolektif 	= $kolektif->kolektif;
		}
		$check_sub_section = t_spkl_detail::where('npk','=',$id2)
											->where('id_spkl','=',$id)
											->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section = $check_sub_section->sub_section;
		}
		$check_code_section = m_sub_section::where('code','=',$sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section 	= $check_code_section->code_section;
		}
		$check_section = m_section::where('code','=',$code_section)->get();
		foreach ($check_section as $check_section) {
			$code_department = $check_section->code_department;
			$npk_spv         = $check_section->npk;
		}
		//hotfix-2.1.1, by Merio, 20160906, bug di pengecekan isi kolektif
		if ($is_kolektif) {
			$status = "5";
			$check_department = m_department::where('code','=',$code_department)->get();
			foreach ($check_department as $check_department) {
				$code_division = $check_department->code_division;
				$npk_kadep     = $check_department->npk;
			}
			if ($npk_kadep == "") {
				$status 		= $status+1;
				$check_division = m_division::where('code','=',$code_division)->get();
				foreach ($check_division as $check_division) {
					$npk_gm = $check_division->npk;
				}
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		} else {
			$status = "4";
			if ($npk_spv == "") {
				$status = $status+1;
				$check_department = m_department::where('code','=',$code_department)->get();
				foreach ($check_department as $check_department) {
					$code_division = $check_department->code_division;
					$npk_kadep     = $check_department->npk;
				}
				if ($npk_kadep == "") {
					$status 		= $status+1;
					$check_division = m_division::where('code','=',$code_division)->get();
					foreach ($check_division as $check_division) {
						$npk_gm = $check_division->npk;
					}
					if ($npk_gm == "") {
						$status = $status+1;
					} else {
						$status = $status;
					}
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		}
		$check = t_spkl_detail::where('id_spkl','=',$id)
								->where('npk','=',$id2)
								->where ( function ($q) {
		                			$q->Where('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5');
		                		})->get();
		foreach ($check as $check) {
			$id_par 		= $check->id;
			$start_planning = $check->start_planning;
			$end_planning   = $check->end_planning;
			$start_date 	= $check->start_date;
			$end_date   	= $check->end_date;
			$npk_emp 		= $check->npk;					// hotfix 1.8.2 20160715 by andre, update kuota use di m_employee
			if ($check->quota_ot_actual > 0) {
				$quota_plan = $check->quota_ot_actual;
			} else {
				$quota_plan = $check->quota_ot;
			}
			$month 	= Carbon::parse($start_date)->format('m');
		}

		$t_spkl 				= t_spkl_detail::findOrFail($id_par);
		// $t_spkl->start_actual 	= $start_planning;
		// $t_spkl->end_actual   	= $end_planning;
		$date1	 				= Carbon::parse($start_date.' '.$start_planning);
		$date2     				= Carbon::parse($end_date.' '.$end_planning);
		$quota_use				= $date1->diffInMinutes($date2);
		//merubah format untuk mendapatkan hari break
		$start_day 			= Carbon::parse($start_date)->format('N');
		$end_day 			= Carbon::parse($end_date)->format('N');
        $start_plannings 	= date('Hi',strtotime($start_planning));
        $end_plannings      = date('Hi',strtotime($end_planning));
		//untuk menghitung durasi break
        if ($start_day == $end_day) {
            $check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
	                                    ->where('day_break','=',$start_day)
	                                    ->where('start_break','>',$start_plannings)
	                                    ->where('end_break','<',$end_plannings)
	                                    ->where('status_break','=','1')
	                                    ->get();
            foreach ($check_break as $check_break) {
                $jml_duration = $check_break->jml;
            }
        } else {
            $check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
	                                    ->where('day_break','=',$start_day)
	                                    ->where('start_break','>',$start_plannings)
	                                    ->where('end_break','<','2400')
	                                    ->where('status_break','=','1')
	                                    ->get();
            $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
	                                    ->where('day_break','=',$end_day)
	                                    ->where('start_break','>','0000')
	                                    ->where('end_break','<',$end_plannings)
	                                    ->where('status_break','=','1')
	                                    ->get();
            foreach ($check_break1 as $check_break1) {
                $jml_duration1 = $check_break1->jml1;
            }
            foreach ($check_break2 as $check_break2) {
                $jml_duration2 = $check_break2->jml2;
            }
            $jml_duration = $jml_duration1+$jml_duration2;
        }
        $total_ot          			= $quota_use-$jml_duration;
        $t_spkl->quota_ot_actual 	= $total_ot;
		$t_spkl->status = $status;
		if ($status == '5') {
			$t_spkl->approval_1_realisasi_date = $tanggal_server;
		} else if ($status == '6') {
			$t_spkl->approval_2_realisasi_date = $tanggal_server;
		} else if ($status == '7') {
			$t_spkl->approval_3_realisasi_date = $tanggal_server;
		}

		$waktu_ot_emp 	= m_employee::where('npk','=',$npk_emp)->get();
        foreach ($waktu_ot_emp as $waktu_ot_emp) {
        	$id_emp = $waktu_ot_emp->id;
        	if ($month == '01') {
        		$quota_used 	= $waktu_ot_emp->quota_used_1;
        		$quota_remain 	= $waktu_ot_emp->quota_remain_1;
            	$quota_update   = "quota_used_1";
            } else if ($month == '02') {
            	$quota_used 	= $waktu_ot_emp->quota_used_2;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_2;
            	$quota_update   = "quota_used_2";
            } else if ($month == '03') {
            	$quota_used 	= $waktu_ot_emp->quota_used_3;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_3;
            	$quota_update   = "quota_used_3";
            } else if ($month == '04') {
            	$quota_used 	= $waktu_ot_emp->quota_used_4;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_4;
            	$quota_update   = "quota_used_4";
            } else if ($month == '05') {
            	$quota_used 	= $waktu_ot_emp->quota_used_5;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_5;
            	$quota_update   = "quota_used_5";
            } else if ($month == '06') {
            	$quota_used 	= $waktu_ot_emp->quota_used_6;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_6;
            	$quota_update   = "quota_used_6";
            } else if ($month == '07') {
            	$quota_used 	= $waktu_ot_emp->quota_used_7;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_7;
            	$quota_update   = "quota_used_7";
            } else if ($month == '08') {
            	$quota_used 	= $waktu_ot_emp->quota_used_8;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_8;
            	$quota_update   = "quota_used_8";
            } else if ($month == '09') {
            	$quota_used 	= $waktu_ot_emp->quota_used_9;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_9;
            	$quota_update   = "quota_used_9";
            } else if ($month == '10') {
            	$quota_used 	= $waktu_ot_emp->quota_used_10;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_10;
            	$quota_update   = "quota_used_10";
            } else if ($month == '11') {
            	$quota_used 	= $waktu_ot_emp->quota_used_11;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_11;
            	$quota_update   = "quota_used_11";
            } else if ($month == '12') {
            	$quota_used 	= $waktu_ot_emp->quota_used_12;
            	$quota_remain 	= $waktu_ot_emp->quota_remain_12;
            	$quota_update   = "quota_used_12";
            }
        }
        //hotfix-2.2.4, by Yudo Maryanto, realisasi harus h+1 setelah spkl planing
        if ($date2->addDay(1) < $tanggal_server){

        } else {
        	\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing');
	        return response()->json([
				'status' => 'Error',
				'message' => 'waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing',
			]);
        }

	    //hotfix-2.2.8, by Merio, 20161025, memperbaiki bug saat sinkron per member
	    $update_kuota_employee 	= ($quota_used-$quota_plan)+$total_ot;
	    if ($update_kuota_employee > $quota_remain) {

	    } else {
	        $update_quota 					= m_employee::findOrFail($id_emp);
	        $update_quota->$quota_update 	= $update_kuota_employee;
	        $update_quota->save();
	        //hotfix-2.3.5, ubah posisi setting jam actual di sinkron per member
	        $t_spkl->start_actual 	= $start_planning;
			$t_spkl->end_actual   	= $end_planning;
	        $t_spkl->save();
		}
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','Sukses, sikronisasi jam realisasi dengan jam planning untuk karyawan dengan NPK '.$id2.' di SPKL dengan ID SPKL '.$id.' berhasil');
		return response()->json([
			'status' => 'success',
			'message' => 'sikronisasi jam realisasi dengan jam planning untuk karyawan dengan NPK '.$id2.' di SPKL dengan ID SPKL '.$id.' berhasil',
		]);
	}

	public function spkl_actual_sinkron_all($id)
	{
		$tanggal_server 	= Carbon::now()->format('Y-m-d H:i:s');
		$kolektif 			= t_spkl::where('id_spkl','=',$id)->get();
		foreach ($kolektif as $kolektif) {
			$is_kolektif = $kolektif->kolektif;
		}

		$check_sub_section = t_spkl_detail::where('id_spkl','=',$id)->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section = $check_sub_section->sub_section;
		}

		$check_code_section = m_sub_section::where('code','=',$sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section = $check_code_section->code_section;
		}

		$check_section = m_section::where('code','=',$code_section)->get();
		foreach ($check_section as $check_section) {
			$code_department = $check_section->code_department;
			$npk_spv         = $check_section->npk;
		}

		//hotfix-2.1.1, by Merio, 20160906, bug di pengecekan isi kolektif
		if ($is_kolektif) {
			$status = "5";
			$check_department = m_department::where('code','=',$code_department)->get();
			foreach ($check_department as $check_department) {
				$code_division = $check_department->code_division;
				$npk_kadep     = $check_department->npk;
			}
			if ($npk_kadep == "") {
				$status 		= $status+1;
				$check_division = m_division::where('code','=',$code_division)->get();
				foreach ($check_division as $check_division) {
					$npk_gm = $check_division->npk;
				}
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		} else {
			$status = "4";
			if ($npk_spv == "") {
				$status 			= $status+1;
				$check_department 	= m_department::where('code','=',$code_department)->get();
				foreach ($check_department as $check_department) {
					$code_division = $check_department->code_division;
					$npk_kadep     = $check_department->npk;
				}
				if ($npk_kadep == "") {
					$status 		= $status+1;
					$check_division = m_division::where('code','=',$code_division)->get();
					foreach ($check_division as $check_division) {
						$npk_gm = $check_division->npk;
					}
					if ($npk_gm == "") {
						$status = $status+1;
					} else {
						$status = $status;
					}
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		}

		$check 		= t_spkl_detail::where('id_spkl','=',$id)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5');
			                		})->get();
		foreach ($check as $check) {
			$id_par 		= $check->id;
			$npk_emp 		= $check->npk;
			$start_date 	= $check->start_date;
			$end_date 		= $check->end_date;
			$month_ot 		= Carbon::parse($start_date)->format('n');
			$start_planning = $check->start_planning;
			$end_planning   = $check->end_planning;

			//hotfix-2.2.8, by Merio, 20161025, mengubah lokasi error kalau h+1 realisasi
			//hotfix-2.2.4, by Yudo Maryanto, realisasi harus h+1 setelah spkl planing
			$get_endDate_endPlaning = Carbon::parse($end_date.' '.$end_planning);
			if ($get_endDate_endPlaning->addDay(1) < $tanggal_server) {

			} else {
				\Session::flash('flash_type','alert-danger');
			    \Session::flash('flash_message','Error, waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing');
			    return response()->json([
					'status' => 'Error',
					'message' => 'waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing',
				]);
			}

			if ($check->quota_ot_actual > 0) {
				$pengurang = $check->quota_ot_actual;
			} else {
				$pengurang = $check->quota_ot;
			}
			$t_spkl 					= t_spkl_detail::findOrFail($id_par);
			$t_spkl->start_actual 		= $start_planning;
			$t_spkl->end_actual   		= $end_planning;

			//sinkron all quota used realisasi with quota used planning
			$t_spkl->quota_ot_actual   	= $check->quota_ot;
			$t_spkl->status = $status;
			if ($status == '5') {
				$t_spkl->approval_1_realisasi_date = $tanggal_server;
			} else if ($status == '6') {
				$t_spkl->approval_2_realisasi_date = $tanggal_server;
			} else if ($status == '7') {
				$t_spkl->approval_3_realisasi_date = $tanggal_server;
			}

			//dev-2.0, 20160825, by Merio, pengembalian quota
			$m_employee_update = m_employee::where('npk',$npk_emp)->first();
			if ($month_ot == 1) {
				$quota_used 	= $m_employee_update->quota_used_1;
				$quota_remain 	= $m_employee_update->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month_ot == 2) {
				$quota_used 	= $m_employee_update->quota_used_2;
				$quota_remain 	= $m_employee_update->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month_ot == 3) {
				$quota_used 	= $m_employee_update->quota_used_3;
				$quota_remain 	= $m_employee_update->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month_ot == 4) {
				$quota_used 	= $m_employee_update->quota_used_4;
				$quota_remain 	= $m_employee_update->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month_ot == 5) {
				$quota_used 	= $m_employee_update->quota_used_5;
				$quota_remain 	= $m_employee_update->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month_ot == 6) {
				$quota_used 	= $m_employee_update->quota_used_6;
				$quota_remain 	= $m_employee_update->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month_ot == 7) {
				$quota_used 	= $m_employee_update->quota_used_7;
				$quota_remain 	= $m_employee_update->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month_ot == 8) {
				$quota_used 	= $m_employee_update->quota_used_8;
				$quota_remain 	= $m_employee_update->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month_ot == 9) {
				$quota_used 	= $m_employee_update->quota_used_9;
				$quota_remain 	= $m_employee_update->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month_ot == 10) {
				$quota_used 	= $m_employee_update->quota_used_10;
				$quota_remain 	= $m_employee_update->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month_ot == 11) {
				$quota_used 	= $m_employee_update->quota_used_11;
				$quota_remain 	= $m_employee_update->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month_ot == 12) {
				$quota_used 	= $m_employee_update->quota_used_12;
				$quota_remain 	= $m_employee_update->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}

			$check_pengembalian = ($quota_used-$pengurang)+$check->quota_ot;
			if ($check_pengembalian > $quota_remain) {

			} else {
				$change_quota = m_employee::findOrFail($m_employee_update->id);
				$change_quota->$quota_par = $check_pengembalian;
				$change_quota->save();

				$t_spkl->save();
			}
		}
		\Session::flash('flash_type','alert-success');
		\Session::flash('flash_message','Sukses, sikronisasi jam realisasi dengan jam planning untuk semua karyawan di SPKL dengan ID SPKL '.$id.' berhasil');
		return response()->json([
			'status' => 'success',
			'message' => 'sikronisasi jam realisasi dengan jam planning untuk semua karyawan di SPKL dengan ID SPKL '.$id.' berhasil',
		]);
	}

	//v1.0 by Merio, 20160121, method untuk save start date and end date spkl realisasi
	public function spkl_actual_save()
	{
		$input 	 	 = request::all();
		$user    	 = Auth::user();
		$npk_user 	 = $user->npk;
		$date 		 = Carbon::now()->format('Y-m-d H:i:s');
		$date_server = Carbon::now()->format('Y-m-d');
		$year  		 = Carbon::now()->format('Y');
		$npk 		 = $input['npk'];
		$id_spkl 	 = $input['id_spkl'];

		$kolektif = t_spkl::where('id_spkl','=',$id_spkl)->get();
		foreach ($kolektif as $kolektif) {
			$is_kolektif = $kolektif->kolektif;
		}

		// hotfix-3.5.6, 20190905, validasi hanya boleh jika > 30 menit
		$today = date('Y-m-d');
		$startTimeActual = $today . ' ' . $input['start_time_actual'];
		$endTimeActual = $today . ' ' . $input['end_time_actual'];

		if (strtotime($input['start_time_actual']) > strtotime($input['end_time_actual'])) {
			$endTimeActual = date('Y-m-d H:i', strtotime($endTimeActual . "+1 days"));
		}

		$inMinute = round(abs(strtotime($endTimeActual) - strtotime($startTimeActual)) / 60,2);

		if ($inMinute < 30) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu kerja harus lebih dari 30 menit');
			return response()->json([
				'status' => 'Error',
				'message' => 'waktu kerja harus lebih dari 30 menit',
			]);
		}

		//hotfix-2.1.2, 20160907, by Merio, menambahkan fungsi jika start time dan end time sama akan digagalkan
		if ($input['start_time_actual'] == $input['end_time_actual']) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, waktu awal dan akhir overtime tidak boleh sama');
			return response()->json([
				'status' => 'Error',
				'message' => 'waktu awal dan akhir overtime tidak boleh sama',
			]);
		}

		$start2 = $input['start_time_actual'];
		$end2 	= $input['end_time_actual'];

		$start_check 	= substr($start2,3,2);
		$end_check 		= substr($end2,3,2);

		$start_check22 	= substr($start2,0,2);
		$end_check22 	= substr($end2,0,2);

		if ($start_check > 59 || $end_check > 59) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, rentang waktu yang diinputkan tidak sesuai, silakan ulangi proses');
			return response()->json([
				'status' => 'Error',
				'message' => 'rentang waktu yang diinputkan tidak sesuai, silakan ulangi proses',
			]);
		}

		if ($start_check22 > 23 || $end_check22 > 23) {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, rentang waktu yang diinputkan tidak sesuai, silakan ulangi proses');
			return response()->json([
				'status' => 'Error',
				'message' => 'rentang waktu yang diinputkan tidak sesuai, silakan ulangi proses',
			]);
		}

		$start_time_planning_2 	= substr($start2, -3, 1);
		$end_time_planning_2 	= substr($end2, -3, 1);

		if ($start_time_planning_2 != ":" || $end_time_planning_2 != ":") {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Error, format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses');
	        return response()->json([
				'status' => 'Error',
				'message' => 'Format waktu overtime salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses',
			]);
		}

		$jml   	= strlen($start2);
		$jml2 	= strlen($end2);
		if ($jml > "5" || $jml2 > "5") {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Format masukkan waktu lembur salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses');
			return response()->json([
				'status' => 'Error',
				'message' => 'Format masukkan waktu lembur salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses',
			]);
		}

		if ($jml < "5" || $jml2 < "5") {
			\Session::flash('flash_type','alert-danger');
	        \Session::flash('flash_message','Format masukkan waktu lembur salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses');
			return response()->json([
				'status' => 'Error',
				'message' => 'Format masukkan waktu lembur salah, format Jam:Menit, contoh = 16:20, silakan ulangi proses',
			]);
		}

		$check_sub_section = t_spkl_detail::where('npk','=',$npk)
											->where('id_spkl','=',$id_spkl)
											->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section 	= $check_sub_section->sub_section;
			$start_date_1 	= $check_sub_section->start_date;
		}

		$check_code_section = m_sub_section::where('code','=',$sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section = $check_code_section->code_section;
		}
		$check_section = m_section::where('code','=',$code_section)->get();
		foreach ($check_section as $check_section) {
			$code_department = $check_section->code_department;
			$npk_spv         = $check_section->npk;
		}
		//hotfix-2.1.1, by Merio, 20160906, bug di pengecekan isi kolektif
		if ($is_kolektif) {
			$status = "5";
			$check_department = m_department::where('code','=',$code_department)->get();
			foreach ($check_department as $check_department) {
				$code_division = $check_department->code_division;
				$npk_kadep     = $check_department->npk;
			}
			if ($npk_kadep == "") {
				$status = $status+1;
				$check_division = m_division::where('code','=',$code_division)->get();
				foreach ($check_division as $check_division) {
					$npk_gm = $check_division->npk;
				}
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		} else {
			$status = "4";
			if ($npk_spv == "") {
				$status = $status+1;
				$check_department = m_department::where('code','=',$code_department)->get();
				foreach ($check_department as $check_department) {
					$code_division = $check_department->code_division;
					$npk_kadep     = $check_department->npk;
				}
				if ($npk_kadep == "") {
					$status = $status+1;
					$check_division = m_division::where('code','=',$code_division)->get();
					foreach ($check_division as $check_division) {
						$npk_gm = $check_division->npk;
					}
					if ($npk_gm == "") {
						$status = $status+1;
					} else {
						$status = $status;
					}
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
		}

		$t_spkl_detail = t_spkl_detail::where('npk',$npk)->where('id_spkl',$id_spkl)->get();
		foreach ($t_spkl_detail as $t_spkl_detail) {
			$id = $t_spkl_detail->id;
			if ($t_spkl_detail->quota_ot_actual > 0) {
				$quota_plan = $t_spkl_detail->quota_ot_actual;			//hotfix 1.8.2, 20160715 by andre, update waktu realisasi ot
			} else {
				$quota_plan = $t_spkl_detail->quota_ot;
			}
			$t_spkl_realisasi 			= t_spkl_detail::findOrFail($id);
			$month 						= Carbon::parse($t_spkl_detail->start_date)->format('n');
			$t_spkl_realisasi->npk 		= $input['npk'];
			$t_spkl_realisasi->id_spkl	= $input['id_spkl'];
			$t_spkl_realisasi->status   = $status;

			if ($status == "5") {
				$t_spkl_realisasi->approval_1_realisasi_date 	= $date;
			} else if ($status == "6") {
				$t_spkl_realisasi->approval_1_realisasi_date 	= $date;
				$t_spkl_realisasi->approval_2_realisasi_date 	= $date;
			} else if ($status == "7" || $status == "8") {
				$t_spkl_realisasi->approval_1_realisasi_date 	= $date;
				$t_spkl_realisasi->approval_2_realisasi_date 	= $date;
				$t_spkl_realisasi->approval_3_realisasi_date 	= $date;
			}
			$check_system_in_out = t_spkl_detail::where('id_spkl',$id_spkl)->where('npk',$npk)->get();
			//dev-2.1, 20160905, by Merio, check system in dan system out compare dengan inputan actual user
			foreach ($check_system_in_out as $check_system_in_out) {
				$id_spkl_update = $check_system_in_out->id;
				$system_in 		= Carbon::parse($check_system_in_out->system_in)->format('Hi');
				$system_out 	= Carbon::parse($check_system_in_out->system_out)->format('Hi');
			}
			$inputan_actual_user = Carbon::parse($input['end_time_actual'])->format('Hi');
			if ($inputan_actual_user > $system_out ) {
				$update_detail_spkl 				= t_spkl_detail::findOrFail($id_spkl_update);
				$update_detail_spkl->npk_edited 	= $npk_user;
				$update_detail_spkl->date_edited 	= $date;
				$update_detail_spkl->save();
			}
			$t_spkl_realisasi->start_actual 	= $input['start_time_actual'];
			$t_spkl_realisasi->end_actual		= $input['end_time_actual'];

			//mengitung quota used realisasi
			$date1	 						= Carbon::parse($t_spkl_realisasi->start_date.' '.$input['start_time_actual']);
			$date2 							= Carbon::parse($t_spkl_realisasi->start_date.' '.$input['end_time_actual']);
			if ($date2->lt($date1)) {
				$date2->addDay();
			}

			$total_ot_temp					= $date1->diffInMinutes($date2);
			//merubah format untuk mendapatkan hari break
			$start_day 						= Carbon::parse($t_spkl_realisasi->start_date)->format('N');
			$end_day 						= Carbon::parse($date2)->format('N');

            $start_plannings 	= Carbon::parse($input['start_time_actual'])->format('Hi');
            $end_plannings 		= Carbon::parse($input['end_time_actual'])->format('Hi');
            //hotfix-2.2.8, by Merio, 20161025, memperbaiki bug saat input realisasi tetapi berbeda tanggal
            $end_date_final 	= Carbon::parse($date2)->format('Y-m-d');

            $t_spkl_realisasi->start_date 	= $t_spkl_realisasi->start_date;
            $t_spkl_realisasi->end_date 	= $end_date_final;

            //untuk menghitung durasi break
            if ($start_day == $end_day) {
            	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break as $check_break) {
            		$jml_duration = $check_break->jml;
            	}
            } else {
            	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            								->where('day_break','=',$start_day)
            								->where('start_break','>=',$start_plannings)
            								->where('end_break','<=','2400')
            								->where('status_break','=','1')
            								->get();
            	$check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            								->where('day_break','=',$end_day)
            								->where('start_break','>=','0000')
            								->where('end_break','<=',$end_plannings)
            								->where('status_break','=','1')
            								->get();
            	foreach ($check_break1 as $check_break1) {
            		$jml_duration1 = $check_break1->jml1;
            	}
            	foreach ($check_break2 as $check_break2) {
            		$jml_duration2 = $check_break2->jml2;
            	}
            	$jml_duration = $jml_duration1+$jml_duration2;
            }

            $total_ot 							= $total_ot_temp-$jml_duration;
            $t_spkl_realisasi->quota_ot_actual 	= $total_ot;
            //check and update waktu lembur per mp
            $waktu_ot_emp 	= m_employee::select('*','m_employees.id as id_emp','m_departments.code as code_department')
            							->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
            							->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
            							->join('m_departments','m_departments.code','=','m_sections.code_department')
            							->where('m_employees.npk','=',$input['npk'])->get();
            foreach ($waktu_ot_emp as $waktu_ot_emp) {
            	$id_emp = $waktu_ot_emp->id_emp;
            	if ($month == '1') {
            		$quota_used 	= $waktu_ot_emp->quota_used_1;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_1;
            		$quota_update   = "quota_used_1";
            	} else if ($month == '2') {
            		$quota_used 	= $waktu_ot_emp->quota_used_2;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_2;
            		$quota_update   = "quota_used_2";
            	} else if ($month == '3') {
            		$quota_used 	= $waktu_ot_emp->quota_used_3;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_3;
            		$quota_update   = "quota_used_3";
            	} else if ($month == '4') {
            		$quota_used 	= $waktu_ot_emp->quota_used_4;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_4;
            		$quota_update   = "quota_used_4";
            	} else if ($month == '5') {
            		$quota_used 	= $waktu_ot_emp->quota_used_5;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_5;
            		$quota_update   = "quota_used_5";
            	} else if ($month == '6') {
            		$quota_used 	= $waktu_ot_emp->quota_used_6;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_6;
            		$quota_update   = "quota_used_6";
            	} else if ($month == '7') {
            		$quota_used 	= $waktu_ot_emp->quota_used_7;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_7;
            		$quota_update   = "quota_used_7";
            	} else if ($month == '8') {
            		$quota_used 	= $waktu_ot_emp->quota_used_8;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_8;
            		$quota_update   = "quota_used_8";
            	} else if ($month == '9') {
            		$quota_used 	= $waktu_ot_emp->quota_used_9;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_9;
            		$quota_update   = "quota_used_9";
            	} else if ($month == '10') {
            		$quota_used 	= $waktu_ot_emp->quota_used_10;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_10;
            		$quota_update   = "quota_used_10";
            	} else if ($month == '11') {
            		$quota_used 	= $waktu_ot_emp->quota_used_11;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_11;
            		$quota_update   = "quota_used_11";
            	} else if ($month == '12') {
            		$quota_used 	= $waktu_ot_emp->quota_used_12;
            		$quota_remain 	= $waktu_ot_emp->quota_remain_12;
            		$quota_update   = "quota_used_12";
            	}
            }
            $pengurangan_kuota = ($quota_used-$quota_plan)+$total_ot;

            //hotfix-2.2.4, by Yudo Maryanto, realisasi harus h+1 setelah spkl planing
            if($date2->addDay(1) < $date) {

            } else {
            	\Session::flash('flash_type','alert-danger');
			    \Session::flash('flash_message','Error, waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing');
			    return response()->json([
					'status' => 'Error',
					'message' => 'waktu realisasi SPKL harus h + 1 setelah pembuatan SPKL planing',
				]);
            }

            // $pengurangan_kuota adalah jumlah quota yang sudah digunakann dan sudah di tambah
            $division = $t_spkl_realisasi->hasSubSection->hasSection->hasDepartment->hasDivision;
            $specialLimit = $division->specialLimit; // in minute

      //       if ($specialLimit) {
      //       	$cek_approved = t_approved_limit_spesial::where('npk',$t_spkl_detail->npk)->first();

      //       	if (!$cek_approved) {
	     //        	if ($pengurangan_kuota > $specialLimit->quota_limit) {
						// \Session::flash('flash_type','alert-danger');
				  //       \Session::flash('flash_message','Error, Quota anda sudah melebihi '. round((int)$specialLimit->quota_limit / 60) .' jam parameter, silakan hubungi GM untuk membuka akses merealisasi SPKL');
				  //       return response()->json('t_spkl/actual/create/'.$id_spkl.'/'.$npk.'');
	     //        	}
	     //        }

            	//delete npk ketika sudah ditambah ke tabel approved
				// $delete_approved = t_approved_limit_spesial::where('npk',$t_spkl_detail->npk)->delete();
            // }

            if ($pengurangan_kuota > $quota_remain) {
            	\Session::flash('flash_type','alert-danger');
		        \Session::flash('flash_message','Error, waktu realisasi yang anda masukkan melebihi batas quota overtime');
				return response()->json([
					'status' => 'Error',
					'message' => 'waktu realisasi yang anda masukkan melebihi batas quota overtime',
				]);
	            $update_quota 					= m_employee::findOrFail($id_emp);
	            $update_quota->$quota_update 	= $pengurangan_kuota;
	            $update_quota->save();

				$t_spkl_realisasi->save();
				\Session::flash('flash_type','alert-success');
				\Session::flash('flash_message','Sukses, waktu realisasi berhasil dimasukkan ke dalam sistem');
				return response()->json([
					'status' => 'Error',
					'message' => 'waktu realisasi berhasil dimasukkan ke dalam sistem',
				]);
			}
		}
	}

	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_actual_approval()
	{
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";
		$data = [
            't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_actual_approval_2()
	{
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";

        $data = [
            't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for approval skpl planning
	public function spkl_actual_approval_3()
	{
		$t_spkl  			= "1";
		$t_spkl_employee  	= "1";
		$id 				= "1";
		$id2 				= "1";

        $data = [
            't_spkl' => $t_spkl,
			't_spkl_employee' => $t_spkl_employee,
			'id' => $id,
			'id2' => $id2,
        ];

		return response()->json($data);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_search_result($id)
	{
		$user    = \Auth::user();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();
		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-6','-5', '-4', '-3', '-2', '-1', '4', '5'])
							->where('t_spkls.id_spkl', $id)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->first();
		if (! $t_spkl) {

            $data = [
                't_spkl' => $t_spkl,
				'user' => $user,
            ];

			return response()->json($data);
		}

		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-6','-5', '-4', '-3', '-2', '-1', '4', '5'])
							->where('t_spkls.id_spkl', $id)
							->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5"
		// 		or status="5" or status="6") and id_spkl="'.$id.'"');
  //       $result2 = new Collection($queries2);

        // hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

   //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
			// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);
   //      $par 	= "1";
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['4', '5'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);
        $par 	= "1";
        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (sdh di query sekalian diatas)
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->where('t_spkl_details.id_spkl',$id)
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);
	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (parameter di belakang di delete)
		// return response()->json('spkl.approval_actual_2', compact('month','quota_original_2','quota_used_2','par','t_spkl','t_spkl_employee','result2','result_quota','check_department','quota_original','quota_used'));

        $data = [
            't_spkl' => $t_spkl,
			't_spkl_employee_count' => $result2,
			'quota_actual' => $result_quota,
			'quota_original' => $quota_original_2,
			'quota_used' => $quota_used_2,
			'user' => $user,
        ];

		return response()->json($data);
		}

	public function spkl_actual_member_edit_save()
	{
		$input 	 = request::all();
		$npk     = $input['npk'];
		$id_spkl = $input['id_spkl'];
		$start_actual = $input['start_actual'];
		$end_actual   = $input['end_actual'];
		$check_id = t_spkl_detail::where('id_spkl','=',$id_spkl)
								->where('npk','=',$npk)
								->get();
		foreach ($check_id as $check_id) {
			$id 				= $check_id->id;
			$quota_ot_planning 	= $check_id->quota_ot;
			$quota_ot_actual 	= $check_id->quota_ot_actual;
		}
		$t_spkl 				= t_spkl_detail::findOrFail($id);
		$t_spkl->start_actual 	= $start_actual;
		$t_spkl->end_actual   	= $end_actual;

		//perhitungan quota jika jam realisasi di edit oleh spv
		$date1	 						= Carbon::parse($t_spkl->start_date.' '.$start_actual);
		$date2 							= Carbon::parse($t_spkl->end_date.' '.$end_actual);
		$total_ot_temp					= $date1 ->diffInMinutes($date2);
		//merubah format untuk mendapatkan hari break
		$start_day 						= Carbon::parse($t_spkl->start_date)->format('N');
		$end_day 						= Carbon::parse($t_spkl->end_date)->format('N');
		$month_ot 						= Carbon::parse($t_spkl->start_date)->format('m');

        $start_plannings 	= date('Hi',strtotime($start_actual));
        $end_plannings 		= date('Hi',strtotime($end_actual));
        //untuk menghitung durasi break
        if ($start_day == $end_day) {
        	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
			            				->where('day_break','=',$start_day)
			            				->where('start_break','>',$start_plannings)
			            				->where('end_break','<',$end_plannings)
			            				->where('status_break','=','1')
			            				->get();
	        foreach ($check_break as $check_break) {
	        	$jml_duration = $check_break->jml;
	        }
        } else {
        	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
			            				->where('day_break','=',$start_day)
			            				->where('start_break','>',$start_plannings)
			            				->where('end_break','<','2400')
			            				->where('status_break','=','1')
			            				->get();
	        $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
			            				->where('day_break','=',$end_day)
			            				->where('start_break','>','0000')
			            				->where('end_break','<',$end_plannings)
			            				->where('status_break','=','1')
			            				->get();
        	foreach ($check_break1 as $check_break1) {
          		$jml_duration1 = $check_break1->jml1;
         	}
         	foreach ($check_break2 as $check_break2) {
          		$jml_duration2 = $check_break2->jml2;
         	}
         	$jml_duration = $jml_duration1+$jml_duration2;
        }

        //hotfix-1.8.2, by Merio Aji, 20160715, update quota employee
		$employee 	= m_employee::where('npk',$npk)->get();
		foreach ($employee as $employee) {
			$employee_id = $employee->id;
			if ($month_ot == "01") {
				$quota_used 	= $employee->quota_used_1;
				$quota_remain 	= $employee->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month_ot == "02") {
				$quota_used 	= $employee->quota_used_2;
				$quota_remain 	= $employee->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month_ot == "03") {
				$quota_used 	= $employee->quota_used_3;
				$quota_remain 	= $employee->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month_ot == "04") {
				$quota_used 	= $employee->quota_used_4;
				$quota_remain 	= $employee->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month_ot == "05") {
				$quota_used 	= $employee->quota_used_5;
				$quota_remain 	= $employee->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month_ot == "06") {
				$quota_used 	= $employee->quota_used_6;
				$quota_remain 	= $employee->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month_ot == "07") {
				$quota_used 	= $employee->quota_used_7;
				$quota_remain 	= $employee->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month_ot == "08") {
				$quota_used 	= $employee->quota_used_8;
				$quota_remain 	= $employee->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month_ot == "09") {
				$quota_used 	= $employee->quota_used_9;
				$quota_remain 	= $employee->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month_ot == "10") {
				$quota_used 	= $employee->quota_used_10;
				$quota_remain 	= $employee->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month_ot == "11") {
				$quota_used 	= $employee->quota_used_11;
				$quota_remain 	= $employee->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month_ot == "12") {
				$quota_used 	= $employee->quota_used_12;
				$quota_remain 	= $employee->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
		}
       	$total_ot 					= $total_ot_temp-$jml_duration;

        $quota_yg_dikembalikan 		= $quota_used-$quota_ot_actual;
        $quota_yg_disave 			= $quota_yg_dikembalikan+$total_ot;

        $t_spkl->quota_ot_actual 	= $total_ot;
		$t_spkl->save();

		$update_quota_employee 					= m_employee::findOrFail($employee_id);
		$update_quota_employee->$quota_par 		= $quota_yg_disave;
		$update_quota_employee->save();

		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL Realization was successfully updated');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL Realization successfully updated',
		]);	}

	public function spkl_actual_member_2_edit_save()
	{
		$input 	 = request::all();
		$npk     = $input['npk'];
		$id_spkl = $input['id_spkl'];
		$start_actual = $input['start_actual'];
		$end_actual   = $input['end_actual'];

		$check_id = t_spkl_detail::where('id_spkl','=',$id_spkl)
								->where('npk','=',$npk)
								->get();

		foreach ($check_id as $check_id) {
			$id 				= $check_id->id;
			$quota_ot_planning 	= $check_id->quota_ot;
			$quota_ot_actual 	= $check_id->quota_ot_actual;
		}
		$t_spkl 				= t_spkl_detail::findOrFail($id);
		$t_spkl->start_actual 	= $start_actual;
		$t_spkl->end_actual   	= $end_actual;
		$date1	 				= Carbon::parse($t_spkl->start_date.' '.$input['start_actual']);
		$date2 					= Carbon::parse($t_spkl->end_date.' '.$input['end_actual']);
		$total_ot_temp			= $date1 ->diffInMinutes($date2);

		//merubah format untuk mendapatkan hari break
		$start_day 						= Carbon::parse($t_spkl->start_date)->format('N');
		$end_day 						= Carbon::parse($t_spkl->end_date)->format('N');
		$month_ot 						= Carbon::parse($t_spkl->start_date)->format('m');

        $start_plannings 	= date('Hi',strtotime($input['start_actual']));
        $end_plannings 		= date('Hi',strtotime($input['end_actual']));

        //untuk menghitung durasi break
        if ($start_day == $end_day) {
        	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            						->where('day_break','=',$start_day)
            						->where('start_break','>',$start_plannings)
            						->where('end_break','<',$end_plannings)
            						->where('status_break','=','1')
            						->get();
            foreach ($check_break as $check_break) {
            	$jml_duration = $check_break->jml;
            }
        } else {
        	$check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            							->where('day_break','=',$start_day)
            							->where('start_break','>',$start_plannings)
            							->where('end_break','<','2400')
            							->where('status_break','=','1')
            							->get();
            $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            							->where('day_break','=',$end_day)
            							->where('start_break','>','0000')
            							->where('end_break','<',$end_plannings)
            							->where('status_break','=','1')
            							->get();
            foreach ($check_break1 as $check_break1) {
            	$jml_duration1 = $check_break1->jml1;
            }
            foreach ($check_break2 as $check_break2) {
            	$jml_duration2 = $check_break2->jml2;
            }
            $jml_duration = $jml_duration1+$jml_duration2;
        }

        //hotfix-1.8.2, by Merio Aji, 20160715, update quota employee
		$employee 	= m_employee::where('npk',$npk)->get();
		foreach ($employee as $employee) {
			$employee_id = $employee->id;
			if ($month_ot == "01") {
				$quota_used 	= $employee->quota_used_1;
				$quota_remain 	= $employee->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month_ot == "02") {
				$quota_used 	= $employee->quota_used_2;
				$quota_remain 	= $employee->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month_ot == "03") {
				$quota_used 	= $employee->quota_used_3;
				$quota_remain 	= $employee->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month_ot == "04") {
				$quota_used 	= $employee->quota_used_4;
				$quota_remain 	= $employee->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month_ot == "05") {
				$quota_used 	= $employee->quota_used_5;
				$quota_remain 	= $employee->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month_ot == "06") {
				$quota_used 	= $employee->quota_used_6;
				$quota_remain 	= $employee->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month_ot == "07") {
				$quota_used 	= $employee->quota_used_7;
				$quota_remain 	= $employee->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month_ot == "08") {
				$quota_used 	= $employee->quota_used_8;
				$quota_remain 	= $employee->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month_ot == "09") {
				$quota_used 	= $employee->quota_used_9;
				$quota_remain 	= $employee->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month_ot == "10") {
				$quota_used 	= $employee->quota_used_10;
				$quota_remain 	= $employee->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month_ot == "11") {
				$quota_used 	= $employee->quota_used_11;
				$quota_remain 	= $employee->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month_ot == "12") {
				$quota_used 	= $employee->quota_used_12;
				$quota_remain 	= $employee->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
		}
       	$total_ot 					= $total_ot_temp-$jml_duration;

        $quota_yg_dikembalikan 		= $quota_used-$quota_ot_actual;
        $quota_yg_disave 			= $quota_yg_dikembalikan+$total_ot;

        $t_spkl->quota_ot_actual 	= $total_ot;
		$t_spkl->save();

		$update_quota_employee 					= m_employee::findOrFail($employee_id);
		$update_quota_employee->$quota_par 		= $quota_yg_disave;
		$update_quota_employee->save();

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','SPKL Realization was successfully updated');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL Realization was successfully updated',
		]);
	}

	public function spkl_actual_member_3_edit_save()
	{
		$input 	 		= request::all();
		$npk     		= $input['npk'];
		$id_spkl 		= $input['id_spkl'];
		$start_actual 	= $input['start_actual'];
		$end_actual   	= $input['end_actual'];
		$check_id 		= t_spkl_detail::where('id_spkl','=',$id_spkl)
										->where('npk','=',$npk)
										->get();
		foreach ($check_id as $check_id) {
			$id 				= $check_id->id;
			$quota_ot_planning 	= $check_id->quota_ot;
			$quota_ot_actual 	= $check_id->quota_ot_actual;
		}

		$t_spkl 				= t_spkl_detail::findOrFail($id);
		$t_spkl->start_actual 	= $start_actual;
		$t_spkl->end_actual   	= $end_actual;
		$date1	 				= Carbon::parse($t_spkl->start_date.' '.$start_actual);
		$date2 					= Carbon::parse($t_spkl->end_date.' '.$end_actual);
		$total_ot_temp			= $date1 ->diffInMinutes($date2);
		//merubah format untuk mendapatkan hari break
		$start_day 						= Carbon::parse($t_spkl->start_date)->format('N');
		$end_day 						= Carbon::parse($t_spkl->end_date)->format('N');
		$month_ot 						= Carbon::parse($t_spkl->start_date)->format('m');

        $start_plannings 	= date('Hi',strtotime($start_actual));
        $end_plannings 		= date('Hi',strtotime($end_actual));

        //untuk menghitung durasi break
        if ($start_day == $end_day) {
        	$check_break = m_break_ot::select(DB::raw('sum(duration_break) as jml'))
            							->where('day_break','=',$start_day)
            							->where('start_break','>',$start_plannings)
            							->where('end_break','<',$end_plannings)
            							->where('status_break','=','1')
            							->get();
            foreach ($check_break as $check_break) {
            	$jml_duration = $check_break->jml;
            }
        } else {
            $check_break1 = m_break_ot::select(DB::raw('sum(duration_break) as jml1'))
            							->where('day_break','=',$start_day)
            							->where('start_break','>',$start_plannings)
            							->where('end_break','<','2400')
            							->where('status_break','=','1')
            							->get();
            $check_break2 = m_break_ot::select(DB::raw('sum(duration_break) as jml2'))
            							->where('day_break','=',$end_day)
            							->where('start_break','>','0000')
            							->where('end_break','<',$end_plannings)
            							->where('status_break','=','1')
            							->get();
            foreach ($check_break1 as $check_break1) {
            	$jml_duration1 = $check_break1->jml1;
            }
            foreach ($check_break2 as $check_break2) {
            	$jml_duration2 = $check_break2->jml2;
            }
            $jml_duration = $jml_duration1+$jml_duration2;
        }

        //hotfix-1.8.2, by Merio Aji, 20160715, update quota employee
		$employee 	= m_employee::where('npk',$npk)->get();
		foreach ($employee as $employee) {
			$employee_id = $employee->id;
			if ($month_ot == "01") {
				$quota_used 	= $employee->quota_used_1;
				$quota_remain 	= $employee->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month_ot == "02") {
				$quota_used 	= $employee->quota_used_2;
				$quota_remain 	= $employee->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month_ot == "03") {
				$quota_used 	= $employee->quota_used_3;
				$quota_remain 	= $employee->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month_ot == "04") {
				$quota_used 	= $employee->quota_used_4;
				$quota_remain 	= $employee->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month_ot == "05") {
				$quota_used 	= $employee->quota_used_5;
				$quota_remain 	= $employee->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month_ot == "06") {
				$quota_used 	= $employee->quota_used_6;
				$quota_remain 	= $employee->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month_ot == "07") {
				$quota_used 	= $employee->quota_used_7;
				$quota_remain 	= $employee->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month_ot == "08") {
				$quota_used 	= $employee->quota_used_8;
				$quota_remain 	= $employee->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month_ot == "09") {
				$quota_used 	= $employee->quota_used_9;
				$quota_remain 	= $employee->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month_ot == "10") {
				$quota_used 	= $employee->quota_used_10;
				$quota_remain 	= $employee->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month_ot == "11") {
				$quota_used 	= $employee->quota_used_11;
				$quota_remain 	= $employee->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month_ot == "12") {
				$quota_used 	= $employee->quota_used_12;
				$quota_remain 	= $employee->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
		}
       	$total_ot 					= $total_ot_temp-$jml_duration;

        $quota_yg_dikembalikan 		= $quota_used-$quota_ot_actual;
        $quota_yg_disave 			= $quota_yg_dikembalikan+$total_ot;

        $t_spkl->quota_ot_actual 	= $total_ot;
		$t_spkl->save();

		$update_quota_employee 					= m_employee::findOrFail($employee_id);
		$update_quota_employee->$quota_par 		= $quota_yg_disave;
		$update_quota_employee->save();

		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL Realization was successfully updated');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL Realization was successfully updated',
		]);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_edit_member($id,$id2)
	{
		$input 	 = request::all();
		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-4', '-3', '-2', '-1', '4', '5'])
							->where('t_spkls.id_spkl', $id)
							->where('t_spkl_details.npk', $id2)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->firstOrFail();

		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-4', '-3', '-2', '-1', '4', '5'])
							->where('t_spkls.id_spkl', $id)
							->get();
		// return $t_spkl;

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $t_spkl_employee_member = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where('t_spkl_details.npk',$id2)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5" or status="5" or status="6") and id_spkl="'.$id.'"');
  //       $result2 = new Collection($queries2);

		// hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
   //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
			// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['4', '5'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);

        $par = "2";
        $month 	= Carbon::now()->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$t_spkl->code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);

	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, t_spkl_employee_member deleted from view
		// return response()->json('spkl.approval_actual_2', compact('result_quota','par','t_spkl','t_spkl_employee',
		// 	't_spkl_employee_member','result2','quota_original_2','quota_used_2','month'));

        $data = [
            't_spkl' => $t_spkl,
			'result2' => $result2,
			'result_quota' => $result_quota,
			'quota_original_2' => $quota_original_2,
			'quota_used_2' => $quota_used_2,
			'par' => $par,
        ];

		return response()->json($data);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_edit_member_2($id, $id2)
	{
		$input 	 = request::all();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-5', '-4', '-3', '-2', '-1', '5', '6'])
							->where('t_spkls.id_spkl', $id)
							->where('t_spkl_details.npk', $id2)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->firstOrFail();

		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-5', '-4', '-3', '-2', '-1', '5', '6'])
							->where('t_spkls.id_spkl', $id)
							->get();
		// return $t_spkl;

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $t_spkl_employee_member = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where('t_spkl_details.npk',$id2)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5" or status="5" or status="6") and id_spkl="'.$id.'"');
  //       $result2 = new Collection($queries2);

		// hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
   //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
			// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['5', '6'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);

        $par = "2";
        $month 	= Carbon::now()->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$t_spkl->code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);

	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, t_spkl_employee_member deleted from view
		// return response()->json('spkl.approval_actual_2', compact('result_quota','par','t_spkl','t_spkl_employee',
		// 	't_spkl_employee_member','result2','quota_original_2','quota_used_2','month'));

        $data = [
            't_spkl' => $t_spkl,
			'result2' => $result2,
			'result_quota' => $result_quota,
			'quota_original_2' => $quota_original_2,
			'quota_used_2' => $quota_used_2,
			'par' => $par,
        ];

		return response()->json($data);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_edit_member_3($id,$id2)
	{
		$input 	 = request::all();
		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-6','-5','-4', '-3', '-2', '-1', '6', '7'])
							->where('t_spkls.id_spkl', $id)
							->where('t_spkl_details.npk', $id2)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->firstOrFail();

		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-6','-5','-4', '-3', '-2', '-1', '6', '7'])
							->where('t_spkls.id_spkl', $id)
							->get();
		// return $t_spkl;

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing

		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $t_spkl_employee_member = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where('t_spkl_details.npk',$id2)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5" or status="5" or status="6") and id_spkl="'.$id.'"');
  //       $result2 = new Collection($queries2);

		// hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
   //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
			// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['6', '7'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);

        $par = "2";
        $month 	= Carbon::now()->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$t_spkl->code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);

	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, t_spkl_employee_member deleted from view
		// return response()->json('spkl.approval_actual_2', compact('result_quota','par','t_spkl','t_spkl_employee',
		// 	't_spkl_employee_member','result2','quota_original_2','quota_used_2','month'));

        $data = [
            't_spkl' => $t_spkl,
			'result2' => $result2,
			'result_quota' => $result_quota,
			'quota_original_2' => $quota_original_2,
			'quota_used_2' => $quota_used_2,
			'par' => $par,
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_search_result_2($id)
	{
		$user    = \Auth::user();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();
		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-5', '-4', '-3', '-2', '-1', '5', '6'])
							->where('t_spkls.id_spkl', $id)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->first();
							if (!$t_spkl) {
								return response()->json([
									'message' => 'SPKL not found with the given ID.',
								], 404); // Return 404 status code indicating resource not found
							}

							// Return JSON response with the found $t_spkl data
							return response()->json($t_spkl);



		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-5', '-4', '-3', '-2', '-1', '5', '6'])
							->where('t_spkls.id_spkl', $id)
							->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5"
		// 		or status="5" or status="6") and id_spkl="'.$id.'"');
         //       $result2 = new Collection($queries2);

        // hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

         //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
		// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);
   //      $par 	= "1";
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['5', '6'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);
        $par 	= "1";
        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (sdh di query sekalian diatas)
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->where('t_spkl_details.id_spkl',$id)
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);
	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (parameter di belakang di delete)
		// return response()->json('spkl.approval_actual_2', compact('month','quota_original_2','quota_used_2','par','t_spkl','t_spkl_employee','result2','result_quota','check_department','quota_original','quota_used'));

        $data = [
            't_spkl' => $t_spkl,
			'employee_count' => $result2,
			'quota_actual_sum' => $result_quota,
			'quota_original' => $quota_original_2,
			'quota_used' => $quota_used_2
        ];

		return response()->json($data);

    }


	//v1.0 by Merio, 20160126, method for search result approval spkl planning

	public function spkl_actual_search_result_3($id)
	{
		$user    = \Auth::user();

		// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing
		// $t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 					->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
		// 					->where('t_spkls.id_spkl',$id)
		// 					->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 					->groupBy('t_spkls.id_spkl')
		// 					->get();
		// $t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
		// 						->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
		// 						->where('t_spkls.id_spkl',$id)
		// 						->where ( function ($q) {
		//                 			$q->where('t_spkl_details.status','-1')
		//                     		->orWhere('t_spkl_details.status','-2')
		//                     		->orWhere('t_spkl_details.status','-3')
		//                     		->orWhere('t_spkl_details.status','-4')
		//                     		->orWhere('t_spkl_details.status','-5')
		//                     		->orWhere('t_spkl_details.status','5')
		//                     		->orWhere('t_spkl_details.status','6');
		//                 		})
		// 						->groupBy('m_employees.npk')
		// 						->get();

		// hotfix-1.8.4 by Ferry, 20160725, Get Object yang akan diedit
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_sub_sections', 'm_sub_sections.code', '=', 't_spkl_details.sub_section' )
                            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
							->whereIn('t_spkl_details.status', ['-6','-5', '-4', '-3', '-2', '-1', '6', '7'])
							->where('t_spkls.id_spkl', $id)
							->select('t_spkls.*', 't_spkl_details.*', 'm_categories.*', 'nama', 'code_department')
							->first();
							if (!$t_spkl) {
								return response()->json([
									'message' => 'SPKL not found with the given ID.',
								], 404); // Return 404 status code indicating resource not found
							}

							// Return JSON response with the retrieved SPKL details
							return response()->json($t_spkl);


		// hotfix-1.8.4 by Ferry, 20160725, Get List seluruh employee dalam SPKL tersebut
		$t_spkl_employee  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->whereIn('t_spkl_details.status', ['-6','-5', '-4', '-3', '-2', '-1', '6', '7'])
							->where('t_spkls.id_spkl', $id)
							->get();

		// $queries2 = DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="-1" or status="-2" or status="-3" or status="-4" or status="-5"
		// 		or status="5" or status="6") and id_spkl="'.$id.'"');
  //       $result2 = new Collection($queries2);

        // hotfix-1.8.4 by Ferry, 20160725, Hitung rows dari member SPKL yg sudah di query
		$result2 = $t_spkl_employee->count();

   //      $queries_quota 	= DB::select('select sum(quota_ot_actual) as jml from t_spkl_details where
			// (status="5" or status="6") and id_spkl="'.$id.'"');
   //      $result_quota 	= new Collection($queries_quota);
   //      $par 	= "1";
   //      $result_quota 	= new Collection($queries_quota);

        $result_quota 	= round(t_spkl_detail::whereIn('status', ['6', '7'])
        									->where('id_spkl', $id)->sum('quota_ot_actual') / 60, 2);
        $par 	= "1";
        //dev-2.0, 20160825, by Merio, Bulan untuk check quota tertinggi mengacu pada bulan SPKL
        $check_month = t_spkl_detail::where('id_spkl',$id)->groupBy('id_spkl')->get();
        foreach ($check_month as $check_month) {
        	$bulan_spkl = $check_month->start_date;
        }
        $month 	= Carbon::parse($bulan_spkl)->format('m');
        $year 	= Carbon::now()->format('Y');

        // hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (sdh di query sekalian diatas)
     //    $check_department = t_spkl_detail::select('*','m_departments.code as code_department')
     //    									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
	    //     								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
	    //     								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
	    //     								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
	    //     								->where('t_spkl_details.id_spkl',$id)
	    //     								->get();
	    // foreach ($check_department as $check_department) {
	    // 	$code_department = $check_department->code_department;
	    // }

	 //   	$quota_original_1 	= DB::select('select sum(quota_approve) as quota_plan from m_quota_reals where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_original_2 	= new Collection($quota_original_1);
	   	$quota_original_2 	= round(m_quota_real::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

  //       $quota_used_1 	= DB::select('select sum(quota_approve) as quota_remain from m_quota_useds where
		// department="'.$code_department.'" and month="'.$month.'" and fyear="'.$year.'"');
  //       $quota_used_2 	= new Collection($quota_used_1);

	   	$quota_used_2 	= round(m_quota_used::where('department', $t_spkl->code_department)
		   										->where('month', $month)
		   										->where('fyear', $year)->sum('quota_approve') / 60, 2);

	   	// hotfix-1.8.4 by Ferry, 20160725, Commented for knowledge and optimizing (parameter di belakang di delete)
		// return response()->json('spkl.approval_actual_2', compact('month','quota_original_2','quota_used_2','par','t_spkl','t_spkl_employee','result2','result_quota','check_department','quota_original','quota_used'));

        $data = [
            'result2' => $result2,
			'result_quota' => $result_quota,
			'par' => $par,
			'month' => $month,
			'year' => $year,
			'quota_original_2' => $quota_original_2,
			'quota_used_2' => $quota_used_2,
        ];

        return response()->json($data);
    }


	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_actual_approve_member($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl','=',$id)
								->where('npk','=',$id2)
								->get();

		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id;
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
												'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}
			// $check_quota_mp = m_quota_used::where('employment_status','=',$employment_status)
			// 								->where('occupation','=',$occupation)
			// 								->where('department','=',$department)
			// 								->where('month','=',$month)
			// 								->where('fyear','=',$year)
			// 								->get();
			// $quota_remain = 0; //dev-1.8, by Merio, 20160701, add inisialisasi variable quota remain
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used_mp;
			// if ($sisa < 0) {
			// 	$status = $status_terakhir;
			// } else {
			$sub_section = t_spkl_detail::where('id_spkl','=',$id)->get();
            foreach ($sub_section as $sub_section) {
                $code_sub_section = $sub_section->sub_section;
            }
            $check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
            foreach ($check_code_section as $check_code_section) {
                $code_section = $check_code_section->code_section;
            }
            $check_section = m_section::where('code','=',$code_section)->get();
            foreach ($check_section as $check_section) {
                $code_department = $check_section->code_department;
            }
            $constraint1 = m_department::where('code','=',$code_department)->get();
            foreach ($constraint1 as $constraint1) {
                $npk_kadep                       = $constraint1->npk;
                $code_division = $constraint1->code_division;
            }
			$status = "5";
			if ($npk_kadep == "") {
				$status = $status+1;
				$constraint2 = m_division::where('code','=',$code_division)->get();
				foreach ($constraint2 as $constraint2) {
					$npk_gm = $constraint2->npk;
				}
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
			$t_spkl_employees 			= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 	= $status;
			if ($status == "6") {
				$t_spkl_employees->approval_2_realisasi_date = "$date";
			} else if ($status == "7" || $status == "8") {
				$t_spkl_employees->approval_2_realisasi_date = "$date";
				$t_spkl_employees->approval_3_realisasi_date = "$date";
			}
			$t_spkl_employees->approval_1_realisasi_date = "$date";
			$t_spkl_employees->save();
			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "-4") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_penambahan 				= $quota_used+$quota_ot_actual;
				$perubahan_quota  				= m_employee::findOrFail($employee_id);
				$perubahan_quota->$quota_par	= $quota_penambahan;
				$perubahan_quota->save();
			}
			// }
		}
		if ($status == "5") {
			\Session::flash('flash_type','alert-success');
		    \Session::flash('flash_message','SPKL was successfully approve');
			return response()->json([
				'status' => 'success',
				'message' => 'SPKL Realization successfully approved',
			]);
		} else if ($status == "-4" || $status == "4") {
			\Session::flash('flash_type','alert-danger');
		    \Session::flash('flash_message','Quota department anda untuk bulan ini sudah habis, silakan hubungi Ka Dept atau HR Personal Admin');
			return response()->json([
				'status' => 'Error',
				'message' => 'Quota department anda untuk bulan ini sudah habis, silakan hubungi Ka Dept atau HR Personal Admin',
			]);
		}else {
			\Session::flash('flash_type','alert-success');
		    \Session::flash('flash_message','SPKL was successfully approve');
			return response()->json([
				'status' => 'success',
				'message' => 'SPKL was successfully approved',
			]);
		}
	}

	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_actual_approve_member_2($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl','=',$id)
								->where('npk','=',$id2)
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id;
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}
			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// $quota_remain = 0; //dev-1.8, by Merio, 20160701, add inisialisasi variable quota remain
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used_mp;
			// if ($sisa < 0) {
			// 	$status = $status_terakhir;
			// } else {
			$sub_section = t_spkl_detail::where('id_spkl','=',$id)
										->groupBy('id_spkl')
										->get();
			foreach ($sub_section as $sub_section) {
				$code_sub_section = $sub_section->sub_section;
			}
			$check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
			foreach ($check_code_section as $check_code_section) {
				$code_section = $check_code_section->code_section;
			}
			$check_section = m_section::where('code','=',$code_section)->get();
			foreach ($check_section as $check_section) {
					$code_department = $check_section->code_department;
			}
			$check_department = m_department::where('code','=',$code_department)->get();
			foreach ($check_department as $check_department) {
				$code_division 	= $check_department->code_division;
			}
			$check_division = m_division::where('code','=',$code_division)->get();
			foreach ($check_division as $check_division) {
				$npk_gm = $check_division->npk;
			}
			$status = "6";
			if ($npk_gm == "") {
				$status++;
			}

			$t_spkl_employees 								= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 						= $status;
			if ($status == 7) {
				$t_spkl_employees->approval_3_realisasi_date 	= "$date";
			}
			$t_spkl_employees->approval_2_realisasi_date 	= "$date";
			$t_spkl_employees->save();

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "-5") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_penambahan 				= $quota_used+$quota_ot_actual;
				$perubahan_quota  				= m_employee::findOrFail($employee_id);
				$perubahan_quota->$quota_par	= $quota_penambahan;
				$perubahan_quota->save();
			}
			// }
		}
		// if ($status_terakhir == "6") {
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved',
		]);
		// } else if ($status_terakhir == "5" || $status_terakhir == "-5") {
		// 	\Session::flash('flash_type','alert-danger');
	 //        \Session::flash('flash_message','Quota department anda untuk bulan ini sudah habis, silakan hubungi General Manager atau HR Personal Admin');
		// 	return response()->json('spkl/actual/search_result/2/'.$id.'');
		// }
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_actual_approve_member_3($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-6')
		                    		->orWhere('t_spkl_details.status','6');
		                		})
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;

			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}

			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// $quota_remain = 0; //dev-1.8, by Merio, 20160701, add inisialisasi variable quota remain
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used_mp;
			// if ($sisa < 0) {
			// 	$status = "6";
			// 	\Session::flash('flash_type','alert-danger');
			// 	\Session::flash('flash_message','kuota lembur member sudah habis');
			// } else {
				// $update_quota 								= m_quota_used::findOrFail($id_quota);
				// $update_quota->quota_approve 				= $sisa;
				// $update_quota->save();
				// $status 									= "7";
			// }
			$ids 											= $t_spkls->id;
			$t_spkl_employees 								= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 						= 7;
			$t_spkl_employees->approval_3_realisasi_date 	= "$date";
			$t_spkl_employees->save();

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "-6") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_penambahan 				= $quota_used+$quota_ot_actual;
				$perubahan_quota  				= m_employee::findOrFail($employee_id);
				$perubahan_quota->$quota_par	= $quota_penambahan;
				$perubahan_quota->save();
			}
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved',
		]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject_member($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;
			$status_terakhir 				= $t_spkls->status;
			$npk 							= $t_spkls->npk;

			//hotfix-1.8.2
			$quota_ot_planning 				= $t_spkls->quota_ot;
			$quota_ot_actual 				= $t_spkls->quota_ot_actual;
			if ($status_terakhir == "4" || $status_terakhir == "5") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_pengurangan 				= $quota_used-$quota_ot_actual;
				$pengurangan_quota  			= m_employee::findOrFail($employee_id);
				$pengurangan_quota->$quota_par	= $quota_pengurangan;
				$pengurangan_quota->save();
			}
			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-4";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject_member_2($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();

		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id;
			$status_terakhir 	= $t_spkls->status;
			$npk 				= $t_spkls->npk;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "5" || $status_terakhir == "6") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
				$employee_id = $employee->id;
				if ($month_ot == "01") {
					$quota_used 	= $employee->quota_used_1;
					$quota_remain 	= $employee->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month_ot == "02") {
					$quota_used 	= $employee->quota_used_2;
					$quota_remain 	= $employee->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month_ot == "03") {
					$quota_used 	= $employee->quota_used_3;
					$quota_remain 	= $employee->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month_ot == "04") {
					$quota_used 	= $employee->quota_used_4;
					$quota_remain 	= $employee->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month_ot == "05") {
					$quota_used 	= $employee->quota_used_5;
					$quota_remain 	= $employee->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month_ot == "06") {
					$quota_used 	= $employee->quota_used_6;
					$quota_remain 	= $employee->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month_ot == "07") {
					$quota_used 	= $employee->quota_used_7;
					$quota_remain 	= $employee->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month_ot == "08") {
					$quota_used 	= $employee->quota_used_8;
					$quota_remain 	= $employee->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month_ot == "09") {
					$quota_used 	= $employee->quota_used_9;
					$quota_remain 	= $employee->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month_ot == "10") {
					$quota_used 	= $employee->quota_used_10;
					$quota_remain 	= $employee->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month_ot == "11") {
					$quota_used 	= $employee->quota_used_11;
					$quota_remain 	= $employee->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month_ot == "12") {
					$quota_used 	= $employee->quota_used_12;
					$quota_remain 	= $employee->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}
			$quota_pengurangan 				= $quota_used-$quota_ot_actual;
			$pengurangan_quota  				= m_employee::findOrFail($employee_id);
			$pengurangan_quota->$quota_par	= $quota_pengurangan;
			$pengurangan_quota->save();
		}
		$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
		$t_spkl_employees->status 		= "-5";
		$t_spkl_employees->reject_date 	= "$date";
		$t_spkl_employees->save();
	}
	\Session::flash('flash_type','alert-success');
    \Session::flash('flash_message','SPKL was successfully reject');
	return response()->json([
		'status' => 'success',
		'message' => 'SPKL was successfully rejected',
	]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject_member_3($id, $id2)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where('npk',$id2)
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}

			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "6" || $status_terakhir == "7") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_pengurangan 				= $quota_used-$quota_ot_actual;
				$pengurangan_quota  			= m_employee::findOrFail($employee_id);
				$pengurangan_quota->$quota_par	= $quota_pengurangan;
				$pengurangan_quota->save();
			}

			if ($status_terakhir == "6"){
				$status_reject									= "-6";
				$id2 											= $t_spkls->id;
				$t_spkl_employees 								= t_spkl_detail::findOrFail($id2);
				$t_spkl_employees->status 						= $status_reject;
				$t_spkl_employees->save();
				\Session::flash('flash_type','alert-success');
        		\Session::flash('flash_message','SPKL was successfully reject');
				return response()->json('spkl/actual/search_result/3/'.$id.'');
			}else if ($status_terakhir =="7" ){
				// $pengembalian = $quota_remain+$quota_used_mp;
				// $update_quota 									= m_quota_used::findOrFail($id_quota);
				// $update_quota->quota_approve 					= $pengembalian;
				// $update_quota->save();
				$status_reject 									= "-6";
				$ids 											= $t_spkls->id;
				$t_spkl_employees 								= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 						= $status_reject;
				$t_spkl_employees->approval_3_realisasi_date 	= "$date";
				$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected',
		]);
        }
    }
	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_actual_approve($id)
	{
		$status = 999;
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-4')
		                    		->orWhere('t_spkl_details.status','4');
		                		})->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

	 	foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}
			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// $quota_remain = 0;
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used_mp;
			// if ($sisa < 0) {
			// 	$status = $status_terakhir;
			// } else {
			$sub_section = t_spkl_detail::where('id_spkl','=',$id)->get();
            foreach ($sub_section as $sub_section) {
                $code_sub_section = $sub_section->sub_section;
            }
            $check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
            foreach ($check_code_section as $check_code_section) {
                $code_section = $check_code_section->code_section;
            }
            $check_section = m_section::where('code','=',$code_section)->get();
            foreach ($check_section as $check_section) {
                $code_department = $check_section->code_department;
            }
            $constraint1 = m_department::where('code','=',$code_department)->get();
            foreach ($constraint1 as $constraint1) {
                $npk_kadep                       = $constraint1->npk;
                $code_division = $constraint1->code_division;
            }
			$status = "5";
			if ($npk_kadep == "") {
				$status = $status+1;
				$constraint2 = m_division::where('code','=',$code_division)->get();
				foreach ($constraint2 as $constraint2) {
					$npk_gm = $constraint2->npk;
				}
				if ($npk_gm == "") {
					$status = $status+1;
				} else {
					$status = $status;
				}
			} else {
				$status = $status;
			}
			foreach ($t_spkl as $t_spkls) {
				$ids 						= $t_spkls->id;
				$t_spkl_employees 			= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 	= $status;
				if ($status == "6") {
					$t_spkl_employees->approval_2_realisasi_date = "$date";
				} else if ($status == "7" || $status == "8") {
					$t_spkl_employees->approval_2_realisasi_date = "$date";
					$t_spkl_employees->approval_3_realisasi_date = "$date";
				}
				$t_spkl_employees->approval_1_realisasi_date = "$date";
				$t_spkl_employees->save();

				// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
				if ($status_terakhir == "-4") {
					$employee 	= m_employee::where('npk',$npk)->get();
					foreach ($employee as $employee) {
						$employee_id = $employee->id;
						if ($month_ot == "01") {
							$quota_used 	= $employee->quota_used_1;
							$quota_remain 	= $employee->quota_remain_1;
							$quota_par 		= "quota_used_1";
						} else if ($month_ot == "02") {
							$quota_used 	= $employee->quota_used_2;
							$quota_remain 	= $employee->quota_remain_2;
							$quota_par 		= "quota_used_2";
						} else if ($month_ot == "03") {
							$quota_used 	= $employee->quota_used_3;
							$quota_remain 	= $employee->quota_remain_3;
							$quota_par 		= "quota_used_3";
						} else if ($month_ot == "04") {
							$quota_used 	= $employee->quota_used_4;
							$quota_remain 	= $employee->quota_remain_4;
							$quota_par 		= "quota_used_4";
						} else if ($month_ot == "05") {
							$quota_used 	= $employee->quota_used_5;
							$quota_remain 	= $employee->quota_remain_5;
							$quota_par 		= "quota_used_5";
						} else if ($month_ot == "06") {
							$quota_used 	= $employee->quota_used_6;
							$quota_remain 	= $employee->quota_remain_6;
							$quota_par 		= "quota_used_6";
						} else if ($month_ot == "07") {
							$quota_used 	= $employee->quota_used_7;
							$quota_remain 	= $employee->quota_remain_7;
							$quota_par 		= "quota_used_7";
						} else if ($month_ot == "08") {
							$quota_used 	= $employee->quota_used_8;
							$quota_remain 	= $employee->quota_remain_8;
							$quota_par 		= "quota_used_8";
						} else if ($month_ot == "09") {
							$quota_used 	= $employee->quota_used_9;
							$quota_remain 	= $employee->quota_remain_9;
							$quota_par 		= "quota_used_9";
						} else if ($month_ot == "10") {
							$quota_used 	= $employee->quota_used_10;
							$quota_remain 	= $employee->quota_remain_10;
							$quota_par 		= "quota_used_10";
						} else if ($month_ot == "11") {
							$quota_used 	= $employee->quota_used_11;
							$quota_remain 	= $employee->quota_remain_11;
							$quota_par 		= "quota_used_11";
						} else if ($month_ot == "12") {
							$quota_used 	= $employee->quota_used_12;
							$quota_remain 	= $employee->quota_remain_12;
							$quota_par 		= "quota_used_12";
						}
					}
					$quota_penambahan 				= $quota_used+$quota_ot_actual;
					$perubahan_quota  				= m_employee::findOrFail($employee_id);
					$perubahan_quota->$quota_par	= $quota_penambahan;
					$perubahan_quota->save();
				}
			}
			// }
		}

		if ($status == "5") {
			\Session::flash('flash_type','alert-success');
		    \Session::flash('flash_message','SPKL was successfully approve');
			return response()->json([
				'status' => 'success',
				'message' => 'SPKL was successfully approved',
			]);;
		} else if ($status == "-4" || $status == "4") {
			\Session::flash('flash_type','alert-danger');
		    \Session::flash('flash_message','Quota department anda untuk bulan ini sudah habis, silakan hubungi Ka Dept atau HR Personal Admin');
			return response()->json([
				'status' => 'Error',
				'message' => 'Quota department anda untuk bulan ini sudah habis, silakan hubungi Ka Dept atau HR Personal Admin',
			]);
		}else {
			\Session::flash('flash_type','alert-success');
		    \Session::flash('flash_message','SPKL was successfully approve');
			return response()->json([
				'status' => 'success',
				'message' => 'SPKL was successfully approved',
			]);
		}
	}

	//v1.0 by Merio, 20160128, method for approve skpl /member
	// dev-1.7.0, Ferry, 20160619, Tambah $redirect supaya simpel coding approval di header
	public function spkl_actual_approve_2($id, $redirect = 'default')
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-5')
		                    		->orWhere('t_spkl_details.status','5');
		                		})
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();
			foreach ($check_status_mp as $check_status_mp) {
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
			}
			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// $quota_remain = 0;
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used_mp;
			// if ($sisa < 0) {
			// 	$status = $status_terakhir;
			// } else {
			$sub_section = t_spkl_detail::where('id_spkl','=',$id)
										->groupBy('id_spkl')
										->get();
			foreach ($sub_section as $sub_section) {
				$code_sub_section = $sub_section->sub_section;
			}
			$check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
			foreach ($check_code_section as $check_code_section) {
				$code_section = $check_code_section->code_section;
			}
			$check_section = m_section::where('code','=',$code_section)->get();
			foreach ($check_section as $check_section) {
					$code_department = $check_section->code_department;
			}
			$check_department = m_department::where('code','=',$code_department)->get();
			foreach ($check_department as $check_department) {
				$code_division 	= $check_department->code_division;
			}
			$check_division = m_division::where('code','=',$code_division)->get();
			foreach ($check_division as $check_division) {
				$npk_gm = $check_division->npk;
			}
			$status = "6";
			if ($npk_gm == "") {
				$status = $status+1;
			} else {
				$status = $status;
			}
			foreach ($t_spkl as $t_spkls) {
				$ids 						= $t_spkls->id;
				$t_spkl_employees 			= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 	= $status;
				if ($status == "7" || $status == "8") {
					$t_spkl_employees->approval_3_realisasi_date = "$date";
				}
				$t_spkl_employees->approval_2_realisasi_date = "$date";
				$t_spkl_employees->save();

				// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
				if ($status_terakhir == "-5") {
					$employee 	= m_employee::where('npk',$npk)->get();
					foreach ($employee as $employee) {
						$employee_id = $employee->id;
						if ($month_ot == "01") {
							$quota_used 	= $employee->quota_used_1;
							$quota_remain 	= $employee->quota_remain_1;
							$quota_par 		= "quota_used_1";
						} else if ($month_ot == "02") {
							$quota_used 	= $employee->quota_used_2;
							$quota_remain 	= $employee->quota_remain_2;
							$quota_par 		= "quota_used_2";
						} else if ($month_ot == "03") {
							$quota_used 	= $employee->quota_used_3;
							$quota_remain 	= $employee->quota_remain_3;
							$quota_par 		= "quota_used_3";
						} else if ($month_ot == "04") {
							$quota_used 	= $employee->quota_used_4;
							$quota_remain 	= $employee->quota_remain_4;
							$quota_par 		= "quota_used_4";
						} else if ($month_ot == "05") {
							$quota_used 	= $employee->quota_used_5;
							$quota_remain 	= $employee->quota_remain_5;
							$quota_par 		= "quota_used_5";
						} else if ($month_ot == "06") {
							$quota_used 	= $employee->quota_used_6;
							$quota_remain 	= $employee->quota_remain_6;
							$quota_par 		= "quota_used_6";
						} else if ($month_ot == "07") {
							$quota_used 	= $employee->quota_used_7;
							$quota_remain 	= $employee->quota_remain_7;
							$quota_par 		= "quota_used_7";
						} else if ($month_ot == "08") {
							$quota_used 	= $employee->quota_used_8;
							$quota_remain 	= $employee->quota_remain_8;
							$quota_par 		= "quota_used_8";
						} else if ($month_ot == "09") {
							$quota_used 	= $employee->quota_used_9;
							$quota_remain 	= $employee->quota_remain_9;
							$quota_par 		= "quota_used_9";
						} else if ($month_ot == "10") {
							$quota_used 	= $employee->quota_used_10;
							$quota_remain 	= $employee->quota_remain_10;
							$quota_par 		= "quota_used_10";
						} else if ($month_ot == "11") {
							$quota_used 	= $employee->quota_used_11;
							$quota_remain 	= $employee->quota_remain_11;
							$quota_par 		= "quota_used_11";
						} else if ($month_ot == "12") {
							$quota_used 	= $employee->quota_used_12;
							$quota_remain 	= $employee->quota_remain_12;
							$quota_par 		= "quota_used_12";
						}
					}
					$quota_penambahan 				= $quota_used+$quota_ot_actual;
					$perubahan_quota  				= m_employee::findOrFail($employee_id);
					$perubahan_quota->$quota_par	= $quota_penambahan;
					$perubahan_quota->save();
				}
			}
			// }
		}
		// if ($status == "6") {
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','SPKL '.$id.' is successfully approved');		// dev-1.7.0, Ferry, 20160619, sisipkan $id

	    // dev-1.7.0, Ferry, 20160619, Tambah $redirect supaya simpel coding approval di header
	    if ($redirect == 'default') {
	    	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
	    } else {
	    	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
	    }
		// } else if ($status == "-5" || $status == "5") {
		// 	\Session::flash('flash_type','alert-danger');
		//     \Session::flash('flash_message','Quota department anda untuk bulan ini sudah habis, silakan hubungi General Manager atau HR Personal Admin');
		// 	return response()->json('spkl/actual/search_result/2/'.$id.'');
		// } else {
			// \Session::flash('flash_type','alert-success');
	  //       \Session::flash('flash_message','SPKL '.$id.' is successfully approved');		// dev-1.7.0, Ferry, 20160619, sisipkan $id
			// return response()->json('spkl/actual/approval/2/view');
		// }
	}

	//v1.0 by Merio, 20160128, method for approve skpl /member
	// dev-1.7.0, Ferry, 20160619, Tambah $redirect supaya simpel coding approval di header
	public function spkl_actual_approve_3($id, $redirect = 'default')
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-6')
		                    		->orWhere('t_spkl_details.status','6');
		                		})
								->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir 	= $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			$check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
													'm_sections.code as code_section','m_employees.id as id_employee')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk','=',$npk)->get();

			foreach ($check_status_mp as $check_status_mp) {
				$month  = Carbon::now()->format('m');
				$year  	= Carbon::now()->format('Y');
				$id_employee=$check_status_mp->id_employee;
				// if($month=='01'){
				// 	$quota_remain=$check_status_mp->quota_remain_1;
				// 	$quota_used  =$check_status_mp->quota_used_1;
				// 	$quota_save  ="quota_used_1";
				// } else if ($month=='02'){
				// 	$quota_remain=$check_status_mp->quota_remain_2;
				// 	$quota_used  =$check_status_mp->quota_used_2;
				// 	$quota_save  ="quota_used_2";
				// } else if ($month=='03'){
				// 	$quota_remain=$check_status_mp->quota_remain_3;
				// 	$quota_used  =$check_status_mp->quota_used_3;
				// 	$quota_save  ="quota_used_3";
				// } else if ($month=='04'){
				// 	$quota_remain=$check_status_mp->quota_remain_4;
				// 	$quota_used  =$check_status_mp->quota_used_4;
				// 	$quota_save  ="quota_used_4";
				// } else if ($month=='05'){
				// 	$quota_remain=$check_status_mp->quota_remain_5;
				// 	$quota_used  =$check_status_mp->quota_used_5;
				// 	$quota_save  ="quota_used_5";
				// } else if ($month=='06'){
				// 	$quota_remain=$check_status_mp->quota_remain_6;
				// 	$quota_used  =$check_status_mp->quota_used_6;
				// 	$quota_save  ="quota_used_6";
				// } else if ($month=='07'){
				// 	$quota_remain=$check_status_mp->quota_remain_7;
				// 	$quota_used  =$check_status_mp->quota_used_7;
				// 	$quota_save  ="quota_used_7";
				// } else if ($month=='08'){
				// 	$quota_remain=$check_status_mp->quota_remain_8;
				// 	$quota_used  =$check_status_mp->quota_used_8;
				// 	$quota_save  ="quota_used_8";
				// } else if ($month=='09'){
				// 	$quota_remain=$check_status_mp->quota_remain_9;
				// 	$quota_used  =$check_status_mp->quota_used_9;
				// 	$quota_save  ="quota_used_9";
				// } else if ($month=='10'){
				// 	$quota_remain=$check_status_mp->quota_remain_10;
				// 	$quota_used  =$check_status_mp->quota_used_10;
				// 	$quota_save  ="quota_used_10";
				// } else if ($month=='11'){
				// 	$quota_remain=$check_status_mp->quota_remain_11;
				// 	$quota_used  =$check_status_mp->quota_used_11;
				// 	$quota_save  ="quota_used_11";
				// } else if ($month=='12'){
				// 	$quota_remain=$check_status_mp->quota_remain_12;
				// 	$quota_used  =$check_status_mp->quota_used_12;
				// 	$quota_save  ="quota_used_12";
				// }
				// $pengurangan=$quota_used_mp-$quota_used;
				// if($pengurangan > 0){
				// 	$pengurangan_kuota=m_employee::findOrFail($id_employee);
				// 	$pengurangan_kuota->$quota_save=$pengurangan;
				// 	$pengurangan_kuota->save();
				// }
				$employment_status 		= $check_status_mp->employment_status;
				$occupation 			= $check_status_mp->occupation;
				$department 			= $check_status_mp->code_department;
				$section 				= $check_status_mp->code_section;
				$sub_section 			= $check_status_mp->code_sub_section;
				$quota_remain_perbulan	= $check_status_mp->quota_remain;
			}
			// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
			// 							->where('occupation','=',$occupation)
			// 							->where('department','=',$department)
			// 							->where('month','=',$month)
			// 							->where('fyear','=',$year)
			// 							->get();
			// $quota_remain = 0;
			// foreach ($check_quota_mp as $check_quota_mp ) {
			// 	$id_quota 				= $check_quota_mp->id;
			// 	$quota_remain			= $check_quota_mp->quota_approve;
			// }
			// $sisa = $quota_remain-$quota_used;
			// $update_quota 								= m_quota_used::findOrFail($id_quota);
			// $update_quota->quota_approve 				= $sisa;
			// $update_quota->save();
			$status 										= "7";
			$ids 											= $t_spkls->id;
			$t_spkl_employees 								= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 						= $status;
			$t_spkl_employees->approval_3_realisasi_date 	= "$date";
			$t_spkl_employees->save();

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "-6") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_penambahan 				= $quota_used+$quota_ot_actual;
				$perubahan_quota  				= m_employee::findOrFail($employee_id);
				$perubahan_quota->$quota_par	= $quota_penambahan;
				$perubahan_quota->save();
			}
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL '.$id.' is successfully approved');		// dev-1.7.0, Ferry, 20160619, sisipkan $id

        // dev-1.7.0, Ferry, 20160619, Tambah $redirect supaya simpel coding approval di header
        if ($redirect == 'default') {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
        else {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject($id)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
									->where ( function ($q) {
			                			$q->where('t_spkl_details.status','-4')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5');
		                			})->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;
			$npk 				= $t_spkls->npk;
			$status_terakhir 	= $t_spkls->status;

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "4" || $status_terakhir == "5") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_pengurangan 				= $quota_used-$quota_ot_actual;
				$pengurangan_quota  			= m_employee::findOrFail($employee_id);
				$pengurangan_quota->$quota_par	= $quota_pengurangan;
				$pengurangan_quota->save();
			}

			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-4";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected'
		]);
	}

	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject_2($id)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
										->where ( function ($q) {
				                			$q->where('t_spkl_details.status','-5')
				                    		->orWhere('t_spkl_details.status','5')
				                    		->orWhere('t_spkl_details.status','6');
		                				})
										->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$ids 							= $t_spkls->id;

			//hotfix-1.8.2
			$npk 				= $t_spkls->npk;
			$status_terakhir 	= $t_spkls->status;
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "5" || $status_terakhir == "6") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_pengurangan 				= $quota_used-$quota_ot_actual;
				$pengurangan_quota  			= m_employee::findOrFail($employee_id);
				$pengurangan_quota->$quota_par	= $quota_pengurangan;
				$pengurangan_quota->save();
			}

			$t_spkl_employees 				= t_spkl_detail::findOrFail($ids);
			$t_spkl_employees->status 		= "-5";
			$t_spkl_employees->reject_date 	= "$date";
			$t_spkl_employees->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully reject');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully rejected'
		]);
	}
	//v1.0 by Merio, 20160113, method for reject skpl /member
	public function spkl_actual_reject_3($id)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$t_spkl = t_spkl_detail::where('id_spkl',$id)
										->where ( function ($q) {
				                			$q->where('t_spkl_details.status','-6')
				                    		->orWhere('t_spkl_details.status','6')
				                    		->orWhere('t_spkl_details.status','7')
				                    		->orWhere('t_spkl_details.status','8');
		                				})
										->get();
		//hotfix-1.8.2
		$cari_bulan_ot = t_spkl_detail::where('id_spkl',$id)->get();
		foreach ($cari_bulan_ot as $cari_bulan_ot) {
			$month_ot = Carbon::parse($cari_bulan_ot->start_date)->format('m');
		}

		foreach ($t_spkl as $t_spkls) {
			$npk 				= $t_spkls->npk;
			$quota_used_mp 		= $t_spkls->quota_ot_actual;
			$status_terakhir    = $t_spkls->status;

			//hotfix-1.8.2
			$quota_ot_planning 	= $t_spkls->quota_ot;
			$quota_ot_actual 	= $t_spkls->quota_ot_actual;

			// hotfix-1.8.2, by Merio Aji, 20160719, update quota_used_mp
			if ($status_terakhir == "6" || $status_terakhir == "7") {
				$employee 	= m_employee::where('npk',$npk)->get();
				foreach ($employee as $employee) {
					$employee_id = $employee->id;
					if ($month_ot == "01") {
						$quota_used 	= $employee->quota_used_1;
						$quota_remain 	= $employee->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month_ot == "02") {
						$quota_used 	= $employee->quota_used_2;
						$quota_remain 	= $employee->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month_ot == "03") {
						$quota_used 	= $employee->quota_used_3;
						$quota_remain 	= $employee->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month_ot == "04") {
						$quota_used 	= $employee->quota_used_4;
						$quota_remain 	= $employee->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month_ot == "05") {
						$quota_used 	= $employee->quota_used_5;
						$quota_remain 	= $employee->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month_ot == "06") {
						$quota_used 	= $employee->quota_used_6;
						$quota_remain 	= $employee->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month_ot == "07") {
						$quota_used 	= $employee->quota_used_7;
						$quota_remain 	= $employee->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month_ot == "08") {
						$quota_used 	= $employee->quota_used_8;
						$quota_remain 	= $employee->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month_ot == "09") {
						$quota_used 	= $employee->quota_used_9;
						$quota_remain 	= $employee->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month_ot == "10") {
						$quota_used 	= $employee->quota_used_10;
						$quota_remain 	= $employee->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month_ot == "11") {
						$quota_used 	= $employee->quota_used_11;
						$quota_remain 	= $employee->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month_ot == "12") {
						$quota_used 	= $employee->quota_used_12;
						$quota_remain 	= $employee->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
				}
				$quota_pengurangan 				= $quota_used-$quota_ot_actual;
				$pengurangan_quota  			= m_employee::findOrFail($employee_id);
				$pengurangan_quota->$quota_par	= $quota_pengurangan;
				$pengurangan_quota->save();
			}
			// $check_status_mp 	= m_employee::select('*','m_departments.code as code_department','m_sub_sections.code as code_sub_section',
			// 										'm_sections.code as code_section')
			// 								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
			// 								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
			// 								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
			// 								->where('m_employees.npk','=',$npk)->get();
			// foreach ($check_status_mp as $check_status_mp) {
			// 	$month  = Carbon::now()->format('m');
			// 	$year  	= Carbon::now()->format('Y');
			// 	$id_employee=$check_status_mp->id_employee;
			// 	if($month=='01'){
			// 		$quota_remain=$check_status_mp->quota_remain_1;
			// 		$quota_used  =$check_status_mp->quota_used_1;
			// 		$quota_save  ="quota_used_1";
			// 	} else if ($month=='02'){
			// 		$quota_remain=$check_status_mp->quota_remain_2;
			// 		$quota_used  =$check_status_mp->quota_used_2;
			// 		$quota_save  ="quota_used_2";
			// 	} else if ($month=='03'){
			// 		$quota_remain=$check_status_mp->quota_remain_3;
			// 		$quota_used  =$check_status_mp->quota_used_3;
			// 		$quota_save  ="quota_used_3";
			// 	} else if ($month=='04'){
			// 		$quota_remain=$check_status_mp->quota_remain_4;
			// 		$quota_used  =$check_status_mp->quota_used_4;
			// 		$quota_save  ="quota_used_4";
			// 	} else if ($month=='05'){
			// 		$quota_remain=$check_status_mp->quota_remain_5;
			// 		$quota_used  =$check_status_mp->quota_used_5;
			// 		$quota_save  ="quota_used_5";
			// 	} else if ($month=='06'){
			// 		$quota_remain=$check_status_mp->quota_remain_6;
			// 		$quota_used  =$check_status_mp->quota_used_6;
			// 		$quota_save  ="quota_used_6";
			// 	} else if ($month=='07'){
			// 		$quota_remain=$check_status_mp->quota_remain_7;
			// 		$quota_used  =$check_status_mp->quota_used_7;
			// 		$quota_save  ="quota_used_7";
			// 	} else if ($month=='08'){
			// 		$quota_remain=$check_status_mp->quota_remain_8;
			// 		$quota_used  =$check_status_mp->quota_used_8;
			// 		$quota_save  ="quota_used_8";
			// 	} else if ($month=='09'){
			// 		$quota_remain=$check_status_mp->quota_remain_9;
			// 		$quota_used  =$check_status_mp->quota_used_9;
			// 		$quota_save  ="quota_used_9";
			// 	} else if ($month=='10'){
			// 		$quota_remain=$check_status_mp->quota_remain_10;
			// 		$quota_used  =$check_status_mp->quota_used_10;
			// 		$quota_save  ="quota_used_10";
			// 	} else if ($month=='11'){
			// 		$quota_remain=$check_status_mp->quota_remain_11;
			// 		$quota_used  =$check_status_mp->quota_used_11;
			// 		$quota_save  ="quota_used_11";
			// 	} else if ($month=='12'){
			// 		$quota_remain=$check_status_mp->quota_remain_12;
			// 		$quota_used  =$check_status_mp->quota_used_12;
			// 		$quota_save  ="quota_used_12";
			// 	}
			// 	$quota_existing			= $check_status_mp->quota_used;
			// 	$employment_status 		= $check_status_mp->employment_status;
			// 	$occupation 			= $check_status_mp->occupation;
			// 	$department 			= $check_status_mp->code_department;
			// 	$section 				= $check_status_mp->code_section;
			// 	$sub_section 			= $check_status_mp->code_sub_section;
			// }
				// $check_quota_mp=m_quota_used::where('employment_status','=',$employment_status)
				// 						->where('occupation','=',$occupation)
				// 						->where('department','=',$department)
				// 						->where('month','=',$month)
				// 						->where('fyear','=',$year)
				// 						->get();
				// foreach ($check_quota_mp as $check_quota_mp ) {
				// 	$id_quota 				= $check_quota_mp->id;
				// 	$quota_remain			= $check_quota_mp->quota_approve;
				// }

			if ($status_terakhir == "6"){
				$status_reject									= "-6";
				$id2 											= $t_spkls->id;
				$t_spkl_employees 								= t_spkl_detail::findOrFail($id2);
				$t_spkl_employees->status 						= $status_reject;
				$t_spkl_employees->save();
				\Session::flash('flash_type','alert-success');
	        	\Session::flash('flash_message','SPKL was successfully reject');
				return response()->json([
                    'status' => 'success',
                    'message' => 'SPKL was successfully rejected'
                ]);

			}else if ($status_terakhir =="7" ){
					// $pengembalian = $quota_remain+$quota_used_mp;
					// $update_quota 									= m_quota_used::findOrFail($id_quota);
					// $update_quota->quota_approve 					= $pengembalian;
					// $update_quota->save();
				$status_reject 									= "-6";
				$ids 											= $t_spkls->id;
				$t_spkl_employees 								= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 						= $status_reject;
				$t_spkl_employees->approval_3_realisasi_date 	= "$date";
				$t_spkl_employees->save();
			}
			\Session::flash('flash_type','alert-success');
        	\Session::flash('flash_message','SPKL was successfully reject');
			return response()->json([
				'status' => 'success',
				'message' => 'SPKL was successfully rejected'
			]);
		}
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	public function spkl_planning_approve($id)
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		// $year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls','t_spkl_details.npk as npk_emp')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)->get();
		foreach ($t_spkl as $t_spkls) {
		// hotfix 1.8.2 20160714 by andre, penambahan kuota apabila perubahan status dari reject menjadi approve Supervisor
			$ids 				= $t_spkls->id_spkls;
			$npk 				= $t_spkls->npk_emp;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_department;
			$date_ot 			= $t_spkls->start_date;
			if ( $status_terakhir == '-1' ){
			$month 				= Carbon::parse($date_ot)->format('m');
			$check_emp 			= m_employee::where('npk','=',$npk)->get();
			foreach ($check_emp as $check_emp) {
				$occupation 		= $check_emp->occupation;
				$id_employee 		= $check_emp->id;
				$employment_status	= $check_emp->employment_status;
				if ($month == "01") {
					$quota_used 	= $check_emp->quota_used_1;
					$quota_remain 	= $check_emp->quota_remain_1;
					$quota_par 		= "quota_used_1";
				} else if ($month == "02") {
					$quota_used 	= $check_emp->quota_used_2;
					$quota_remain 	= $check_emp->quota_remain_2;
					$quota_par 		= "quota_used_2";
				} else if ($month == "03") {
					$quota_used 	= $check_emp->quota_used_3;
					$quota_remain 	= $check_emp->quota_remain_3;
					$quota_par 		= "quota_used_3";
				} else if ($month == "04") {
					$quota_used 	= $check_emp->quota_used_4;
					$quota_remain 	= $check_emp->quota_remain_4;
					$quota_par 		= "quota_used_4";
				} else if ($month == "05") {
					$quota_used 	= $check_emp->quota_used_5;
					$quota_remain 	= $check_emp->quota_remain_5;
					$quota_par 		= "quota_used_5";
				} else if ($month == "06") {
					$quota_used 	= $check_emp->quota_used_6;
					$quota_remain 	= $check_emp->quota_remain_6;
					$quota_par 		= "quota_used_6";
				} else if ($month == "07") {
					$quota_used 	= $check_emp->quota_used_7;
					$quota_remain 	= $check_emp->quota_remain_7;
					$quota_par 		= "quota_used_7";
				} else if ($month == "08") {
					$quota_used 	= $check_emp->quota_used_8;
					$quota_remain 	= $check_emp->quota_remain_8;
					$quota_par 		= "quota_used_8";
				} else if ($month == "09") {
					$quota_used 	= $check_emp->quota_used_9;
					$quota_remain 	= $check_emp->quota_remain_9;
					$quota_par 		= "quota_used_9";
				} else if ($month == "10") {
					$quota_used 	= $check_emp->quota_used_10;
					$quota_remain 	= $check_emp->quota_remain_10;
					$quota_par 		= "quota_used_10";
				} else if ($month == "11") {
					$quota_used 	= $check_emp->quota_used_11;
					$quota_remain 	= $check_emp->quota_remain_11;
					$quota_par 		= "quota_used_11";
				} else if ($month == "12") {
					$quota_used 	= $check_emp->quota_used_12;
					$quota_remain 	= $check_emp->quota_remain_12;
					$quota_par 		= "quota_used_12";
				}
			}
			$penambahan_quota  			    = $quota_used+$quota_used_mp;
			$check_emp 						= m_employee::findOrFail($id_employee);
			$check_emp->$quota_par 			= $penambahan_quota;
			$check_emp->save();
			}

			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= "2";
				$t_spkl_employees->approval_1_planning_date = "$date";
				$t_spkl_employees->save();

		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL was successfully approve');
		return response()->json([
			'status' => 'success',
			'message' => 'SPKL was successfully approved'
		]);
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	// dev-1.6.0, Ferry, 20160615, Tambah $redirect supaya simpel coding approval di header
	public function spkl_planning_approve_2($id, $redirect = 'default')
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls','t_spkl_details.npk as npk_emp')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			//hotfix-1.8.2, by Merio, penambahan quota saat di approve ka dept
			$npk 				= $t_spkls->npk_emp;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');

			if ($status_terakhir == '-2'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used+$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}
			// $npk 				= $t_spkls->npk_emp;
			// $quota_used_mp 		= $t_spkls->quota_ot;
			// $status_terakhir	= $t_spkls->status;
			// $code_department	= $t_spkls->code_department;
			// $check_emp 			= m_employee::where('npk','=',$npk)->get();

			// foreach ($check_emp as $check_emp) {
			// 	$occupation 		= $check_emp->occupation;
			// 	$employment_status	= $check_emp->employment_status;
			// }
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 	= 0;
			// $quota_approve  = 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {

				$sub_section = t_spkl_detail::where('id_spkl','=',$id)
											->groupBy('id_spkl')
											->get();
				foreach ($sub_section as $sub_section) {
					$code_sub_section = $sub_section->sub_section;
				}
				$check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
				foreach ($check_code_section as $check_code_section) {
					$code_section = $check_code_section->code_section;
				}
				$check_section = m_section::where('code','=',$code_section)->get();
				foreach ($check_section as $check_section) {
						$code_department = $check_section->code_department;
				}
				$check_department = m_department::where('code','=',$code_department)->get();
				foreach ($check_department as $check_department) {
					$code_division 	= $check_department->code_division;
				}
				$check_division = m_division::where('code','=',$code_division)->get();
				foreach ($check_division as $check_division) {
					$npk_gm = $check_division->npk;
				}
				$status = "3";
				if ($npk_gm == "") {
					$status++;
				}

				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= $status;
				if ($status == 4) {
					$t_spkl_employees->approval_3_planning_date = "$date";
				}
				$t_spkl_employees->approval_2_planning_date = "$date";
				$t_spkl_employees->save();
			// }
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL '.$id.' is successfully approved');		// dev-1.6.0, Ferry, 20160615, sisipkan $id

        // dev-1.6.0, Ferry, 20160615, Tambah $redirect supaya simpel coding approval di header
        if ($redirect == 'default') {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
        else {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
	}
	//v1.0 by Merio, 20160128, method for approve skpl /member
	// dev-1.6.0, Ferry, 20160615, Tambah $redirect supaya simpel coding approval di header
	public function spkl_planning_approve_3($id, $redirect = 'default')
	{
		$date 	= Carbon::now()->format('Y-m-d H:i:s');
		$month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');

		$t_spkl = t_spkl_detail::select('*','t_spkl_details.id as id_spkls','t_spkl_details.npk as npk_emp')
								->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->where('t_spkl_details.id_spkl','=',$id)->get();
		foreach ($t_spkl as $t_spkls) {
			$ids 				= $t_spkls->id_spkls;
			$npk 				= $t_spkls->npk_emp;
			$quota_used_mp 		= $t_spkls->quota_ot;
			$status_terakhir	= $t_spkls->status;
			$code_department	= $t_spkls->code_department;

			//hotfix-1.8.2, by Merio, pengembalian quota saat di reject ka dept
			$npk 				= $t_spkls->npk;
			$status_terakhir	= $t_spkls->status;
			$quota_used_mp		= $t_spkls->quota_ot;
			$start_date 		= $t_spkls->start_date;
			$month 				= Carbon::parse($start_date)->format('m');
			if ($status_terakhir == '-3'){
				$check_emp 		= m_employee::where('npk','=',$npk)->get();
				foreach ($check_emp as $check_emp) {
					$id_employee = $check_emp->id;
					if ($month == "01") {
						$quota_used 	= $check_emp->quota_used_1;
						$quota_remain 	= $check_emp->quota_remain_1;
						$quota_par 		= "quota_used_1";
					} else if ($month == "02") {
						$quota_used 	= $check_emp->quota_used_2;
						$quota_remain 	= $check_emp->quota_remain_2;
						$quota_par 		= "quota_used_2";
					} else if ($month == "03") {
						$quota_used 	= $check_emp->quota_used_3;
						$quota_remain 	= $check_emp->quota_remain_3;
						$quota_par 		= "quota_used_3";
					} else if ($month == "04") {
						$quota_used 	= $check_emp->quota_used_4;
						$quota_remain 	= $check_emp->quota_remain_4;
						$quota_par 		= "quota_used_4";
					} else if ($month == "05") {
						$quota_used 	= $check_emp->quota_used_5;
						$quota_remain 	= $check_emp->quota_remain_5;
						$quota_par 		= "quota_used_5";
					} else if ($month == "06") {
						$quota_used 	= $check_emp->quota_used_6;
						$quota_remain 	= $check_emp->quota_remain_6;
						$quota_par 		= "quota_used_6";
					} else if ($month == "07") {
						$quota_used 	= $check_emp->quota_used_7;
						$quota_remain 	= $check_emp->quota_remain_7;
						$quota_par 		= "quota_used_7";
					} else if ($month == "08") {
						$quota_used 	= $check_emp->quota_used_8;
						$quota_remain 	= $check_emp->quota_remain_8;
						$quota_par 		= "quota_used_8";
					} else if ($month == "09") {
						$quota_used 	= $check_emp->quota_used_9;
						$quota_remain 	= $check_emp->quota_remain_9;
						$quota_par 		= "quota_used_9";
					} else if ($month == "10") {
						$quota_used 	= $check_emp->quota_used_10;
						$quota_remain 	= $check_emp->quota_remain_10;
						$quota_par 		= "quota_used_10";
					} else if ($month == "11") {
						$quota_used 	= $check_emp->quota_used_11;
						$quota_remain 	= $check_emp->quota_remain_11;
						$quota_par 		= "quota_used_11";
					} else if ($month == "12") {
						$quota_used 	= $check_emp->quota_used_12;
						$quota_remain 	= $check_emp->quota_remain_12;
						$quota_par 		= "quota_used_12";
					}
					$pengembalian_quota  			= $quota_used+$quota_used_mp;
					$check_emp 						= m_employee::findOrFail($id_employee);
					$check_emp->$quota_par 			= $pengembalian_quota;
					$check_emp->save();
				}
			}

			// $check_emp 			= m_employee::where('npk','=',$npk)->get();

			// foreach ($check_emp as $check_emp) {
			// 	$occupation 		= $check_emp->occupation;
			// 	$employment_status	= $check_emp->employment_status;
			// }
			// $check_status_emp 	= m_quota_used::where('department','=',$code_department)
			// 									->where('fyear','=',$year)
			// 									->where('month','=',$month)
			// 									->where('occupation','=',$occupation)
			// 									->where('employment_status','=',$employment_status)
			// 									->get();
			// $quota_plan 	= 0;
			// $quota_approve 	= 0;
			// foreach ($check_status_emp as $check_status_emp) {
			// 	$quota_plan 	= $check_status_emp->quota_plan;
			// 	$quota_approve 	= $check_status_emp->quota_approve;
			// }
			// $check_pengurangan_quota = $quota_approve-$quota_used_mp;
			// if ($check_pengurangan_quota > 0) {
				$t_spkl_employees 							= t_spkl_detail::findOrFail($ids);
				$t_spkl_employees->status 					= "4";
				$t_spkl_employees->approval_3_planning_date = "$date";
				$t_spkl_employees->save();
			// }
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','SPKL '.$id.' is successfully approved');		// dev-1.6.0, Ferry, 20160615, sisipkan $id

        // dev-1.6.0, Ferry, 20160615, Tambah $redirect supaya simpel coding approval di header
		if ($redirect == 'default') {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
        else {
        	return response()->json([
				'status' => 'success',
				'message' => 'SPKL' .$id. 'is successfully approved'
			]);
        }
	}

	//v1.0 by Merio, 20160202, method view user
	public function approval_1_view()
	{
		$m_section  = m_section::select('*','m_sections.id as id_section')
								->leftjoin('m_employees','m_employees.npk','=','m_sections.npk')->get();
                                return response()->json([
									'data' => $m_section,
									'message' => 'Data retrieved successfully',
								]);
	}
	//v1.0 by Merio, 20151230, method form update section
	public function approval_1_update($id)
	{
	 	$m_section 		= m_section::where('id', $id)->get();
	 	$m_section_all	= m_section::select('*','m_sections.id as id_section')
									->leftjoin('m_employees','m_employees.npk','=','m_sections.npk')->get();
	 	$m_employees 	= User::where('role','=','Supervisor')
	 							->get();

                                $data = [
                                    'm_section' => $m_section,
									'm_section_all' => $m_section_all,
									'm_employees' => $m_employees,
									'message' => 'Data retrieved successfully',
                                ];


								 return response()->json($data);
	}

	//v1.5.4 by Merio, 20160418, method form delete approval section
	public function approval_1_delete($id)
	{
	 	$m_section 			= m_section::findOrFail($id);
		$m_section->npk 	= '';
		$m_section->save();
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','Approval section was successfully reset');
		return response()->json([
			'status' => 'success',
			'message' => 'Approval section reset successfully',
			]);
        }
	//v1.5.4 by Merio, 20160418, method form delete approval department
	public function approval_2_delete($id)
	{
	 	$m_department 			= m_department::findOrFail($id);
		$m_department->npk 		= '';
		$m_department->save();
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','Approval section was successfully reset');
		return response()->json([
			'status' => 'success',
			'message' => 'Approval section reset successfully',
			]);
	}

	//v1.5.4 by Merio, 20160418, method form delete approval division
	public function approval_3_delete($id)
	{
	 	$m_division 			= m_division::findOrFail($id);
		$m_division->npk 		= '';
		$m_division->save();
		\Session::flash('flash_type','alert-success');
	    \Session::flash('flash_message','Approval section was successfully reset');
		return response()->json([
			'status' => 'success',
			'message' => 'Approval section reset successfully',
			]);
	}

	//v1.0 by Merio, 20160102, method save update user
	public function approval_1_update_save()
	{
		$input 	= request::all();
		$id 	= $input['id'];
		$npk 	= $input['npk'];
		$m_section 			= m_section::findOrFail($id);
		$m_section->npk 	= $npk;
		$m_section->save();
		$m_sub_sections = m_sub_section::where('code_section','=',$m_section->code)->get();
		foreach ($m_sub_sections as $m_sub_sections) {
			$m_sub_section 			= m_sub_section::findOrFail($m_sub_sections->id);
			$m_sub_section->npk 	= $npk;
			$m_sub_section->save();
		}
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','Approval '.$npk.' was successfully updated');
		return response()->json([
			'status' => 'success',
			'message' => 'Approval' .$npk. 'was successfully updated',
			]);
	}

	//v1.0 by Merio, 20160202, method view user
	public function approval_2_view()
	{
		$m_department  = m_department::select('*','m_departments.id as id_department')
										->leftjoin('m_employees','m_employees.npk','=','m_departments.npk')->get();
                                        return response()->json([
											'data' => $m_department,
											'message' => 'Data retrieved successfully',
										]);
	}

	//v1.0 by Merio, 20151230, method form update section
	public function approval_2_update($id)
	{
	 	$m_department 		= m_department::where('id', $id)->get();
	 	$m_department_all	= m_department::select('*','m_departments.id as id_department')
										->leftjoin('m_employees','m_employees.npk','=','m_departments.npk')->get();
	 	$m_employees 		= User::where('role','=','Ka Dept')->get();

        $data = [
        'm_department' => $m_department,
		'm_department_all' => $m_department_all,
		'm_employees' => $m_employees,
		'message' => 'Data retrieved successfully'];

         return response()->json($data);
	}

	//v1.0 by Merio, 20160102, method save update user
	public function approval_2_update_save()
	{
		$input 	= request::all();
		$id 	= $input['id'];
		$npk 	= $input['npk'];
		$m_department 		= m_department::findOrFail($id);
		$m_department->npk 	= $npk;
		$m_department->save();
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','Approval '.$npk.' was successfully updated');

        $data = [
			'm_department' => $m_department,
            'status' => 'success',
			'message' => 'Data retrieved successfully',
        ];

		return response()->json($data);
	}
	//v1.0 by Merio, 20160202, method view user
	public function approval_3_view()
	{
		$m_division  = m_division::select('*','m_divisions.id as id_divisi')
								->leftjoin('m_employees','m_employees.npk','=','m_divisions.npk')->get();
								return response()->json([
									'm_division' => $m_division,
									'message' => 'Data retrieved successfully'
								]);
	}

	//v1.0 by Merio, 20151230, method form update section
	public function approval_3_update($id)
	{
	 	$m_division 	= m_division::where('id', $id)->get();
	 	$m_division_all	= m_division::select('*','m_divisions.id as id_divisi')
								->leftjoin('m_employees','m_employees.npk','=','m_divisions.npk')->get();
	 	$m_employees 	= User::where('role','=','GM')
	 							->get();

                                $data = [
									'm_division' => $m_division,
									'm_division_all' => $m_division_all,
									'm_employees' => $m_employees,
									'message' => 'Data retrieved successfully'
                                ];

                                return response()->json($data);
	}

	//v1.0 by Merio, 20160102, method save update user
	public function approval_3_update_save()
	{
		$input 	= request::all();
		$id 	= $input['id'];
		$npk 	= $input['npk'];
		$m_division 		= m_division::findOrFail($id);
		$m_division->npk 	= $npk;
		$m_division->save();
		\Session::flash('flash_type','alert-success');
        \Session::flash('flash_message','Approval '.$npk.' was successfully updated');

		return response()->json([
			'status' => 'success',
			'message' => 'Approval '.$npk.' was successfully updated',
			]);
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_planning_view_search_result($id)
	{
		$input 	 		= request::all();
		$user 			= Auth::user();
		$sub_sections 	= m_employee::where('npk',$user->npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->join('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::select('*','t_spkl_details.updated_at as created','t_spkl_details.sub_section as sub_sections')
							->leftjoin('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftjoin('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->leftjoin('m_transports','m_transports.code','=','m_employees.transport')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkl_details.npk')
							->get();
		$check_update = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
								->where ( function ($q) {
                				$q->Where('t_spkl_details.status','3')
	                    			->orWhere('t_spkl_details.status','5')
	                    			->orWhere('t_spkl_details.status','6')
	                    			->orWhere('t_spkl_details.status','7')
	                    			->orWhere('t_spkl_details.status','8')
	                    			->orWhere('t_spkl_details.status','-1')
	                    			->orWhere('t_spkl_details.status','-2')
	                    			->orWhere('t_spkl_details.status','-3')
	                    			->orWhere('t_spkl_details.status','-4')
	                    			->orWhere('t_spkl_details.status','-5')
	                    			->orWhere('t_spkl_details.status','-6');
                				})
								->where('t_spkls.id_spkl','=',$id)
								->get();
		$queries2 = DB::select('select count(npk) as count from t_spkl_details where
		(status = "1" or status = "2" or status = "3" or status = "4"
		or status = "5" or status = "6" or status = "-1" or status = "-2" or status = "-3") and
		id_spkl="'.$id.'"');
        $result2 = new Collection($queries2);
        // dev-1.6.0, Ferry, 20160512
        $shift_model = m_shift::query();
        $trans_model = m_transport::query();

        $data = [
			't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'check_update' => $check_update,
			'queries2' => $result2,
			'message' => 'Data retrieved successfully',
        ];

		return response()->json($data);
	}

	public function spkl_reject_view_search_result($id)
	{
		$input 	 		= request::all();
		$user 			= Auth::user();
		$npk 			= $user->npk;
		$sub_sections 	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->join('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::select('*','t_spkl_details.updated_at as created')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->join('m_transports','m_transports.code','=','m_employees.transport')
							->where('t_spkls.id_spkl','=',''.$id.'')
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
                			->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkl_details.npk')
							->get();
		$queries2 	= DB::select('select count(npk) as count from t_spkl_details where
		(status = "-1" or status = "-2" or status = "-3" or status = "-4" or status = "-5" or status = "-6" or status = "-7") and
		id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);

        $data = [
			't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'queries2' => $result2,
			'message' => 'Data retrieved successfully',
        ];

		return response()->json($data);
	}

	public function spkl_planning_print($id){
		$user 				= \Auth::user();
		$check_sub_section 	= m_employee::where('npk','=',$user->npk)->get();
		foreach ($check_sub_section as $check_sub_section) {
			$code_sub_section 	= $check_sub_section->sub_section;
		}
		$check_code_section 	= m_sub_section::where('code','=',$code_sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section 		= $check_code_section->code_section;
		}
		$check_code_department 	= m_section::where('code','=',$code_section)->get();
		foreach ($check_code_department as $check_code_department) {
			$code_department 	= $check_code_department->code_department;
		}
		$check_pic_department 	= m_department::where('code','=',$code_department)->get();
		foreach ($check_pic_department as $check_pic_department) {
			$npk_pic_department = $check_pic_department->npk;
		}
		$check_nama_pic 		= m_employee::where('npk','=',$npk_pic_department)->get();
		foreach ($check_nama_pic as $check_nama_pic) {
			$npk_pic  = $check_nama_pic->npk;
			$nama_pic = $check_nama_pic->nama;
		}
		$check = t_spkl::where('id_spkl','=',$id)->get();
		foreach ($check as $check) {
			$category_detail_check = $check->category_detail;
		}
		$username = $user->nama;
		//hotfix-1.5.5, by Merio Aji, 20160428, bug pic spkl not username but name employee (leader/admin)
		$check_pic_section 	= m_employee::where('npk','=',$user->npk)->get();
		$array 				= t_spkl::select('*','t_spkl_details.npk as user_npk','t_spkl_details.start_date as startdate',
									't_spkl_details.end_date as enddate','t_spkl_details.start_planning as startplanning',
									't_spkl_details.end_planning as endplanning','t_spkl_details.start_actual as startactual',
									't_spkl_details.end_actual as endactual',
									'm_categories.name as name_category','m_departments.code as name_department','m_sections.name as name_section')
									->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
									->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
									->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->where('t_spkls.id_spkl',$id)
									->where ( function ($q) {
					                	$q->where('t_spkl_details.status','1')
					                    	->orWhere('t_spkl_details.status','2')
					                    	->orWhere('t_spkl_details.status','3')
					                    	->orWhere('t_spkl_details.status','4')
					                    	->orWhere('t_spkl_details.status','5')
					                    	->orWhere('t_spkl_details.status','6')
					                    	->orWhere('t_spkl_details.status','7')
					                    	->orWhere('t_spkl_details.status','8');
					                	})
					                ->groupBy('t_spkl_details.npk')
					                ->orderBy('m_employees.npk', 'ASC')
									->get();
		$queries2 	= DB::select('select count(npk) as count from t_spkl_details where
		(status="1" or status="2" or status="3" or status="4" or status="5" or status="6")
		and id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);

        $array4 	= t_spkl::select('*','m_categories.name as name_category','m_departments.code as name_department',
						'm_sections.name as name_section')
						->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkls.id_spkl',$id)
						->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
						->get();
        foreach ($array4 as $array4) {
			$is_late		= $array4->is_late;
			$status_spkl	= $array4->status;
		}

		$jml_mp 		= DB::select('select count(npk) as count from t_spkl_details where
		(status="1" or status="2" or status="3" or status="4" or status="5" or status="6")
		and id_spkl="'.$id.'"');
        $result_jml_mp 	= new Collection($jml_mp);

		foreach ($result_jml_mp as $result_jml_mp) {
			$jumlah_mp = $result_jml_mp->count;
		}
		if ($status_spkl == "1" and $is_late == "0") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4";
			}
		} elseif ($status_spkl == "1" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "2" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_1_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_1_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_1_4";
			}
		} elseif ($status_spkl == "2" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "3" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_2_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_2_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_2_4";
			}
		} elseif ($status_spkl == "3" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "4" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_3_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_3_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_3_4";
			}
		} elseif ($status_spkl == "4" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "5" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_4";
			}
		} elseif ($status_spkl == "5" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_Late_4";
			}
		} elseif ($status_spkl == "6" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_4";
			}
		} elseif ($status_spkl == "6" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_Late_4";
			}
		} elseif ($status_spkl == "7" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_4";
			}
		} elseif ($status_spkl == "7" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_Late_4";
			}
		}
		//hotfix-2.2.9, by Merio, 20161026, untuk menghitung jumlah mp dalam satu spkl
		$jml_mp 		= DB::select('select count(npk) as count from t_spkl_details where
		(status="1" or status="2" or status="3" or status="4" or status="5" or status="6") and id_spkl="'.$id.'"');
        $hasil_jml_mp 	= new Collection($jml_mp);

		Excel::load('/storage/template/'.$file.'.xlsx', function($file) use($is_late,$check_pic_section,$array,$result2,
			$id,$username,$npk_pic,$nama_pic,$user,$hasil_jml_mp) {
			foreach ($hasil_jml_mp as $hasil_jml_mp) {
				$jml_mp = $hasil_jml_mp->count;
			}
			$date 	= Carbon::now()->format('d-m-Y');
			$date2 	= Carbon::now()->format('d-m-Y, H:i');
			foreach ($array as $key => $value) {
				$is_print 			= $value->is_print;
				$note 				= $value->note;
				$category1 			= $value->type;
				$category_detail 	= $value->name_category;
				$name_department 	= $value->name_department;
				$name_section 		= $value->name_section;
				$leader_npk 		= $user->npk;
				if ($category1 == "1"){
					$category2 	= "Biasa";
				} elseif ($category1 == "2"){
					$category2 	= "Libur";
				} elseif ($category1=="3") {
					$category2 	= "Hari Libur Nasional";
				}
				$day_start = date('D', strtotime($value->start_date));
                if ($day_start == "Sun") {
                    $hari = "Minggu";
                } else if ($day_start == "Mon") {
                    $hari = "Senin";
                } else if ($day_start == "Tue") {
                    $hari = "Selasa";
                } else if ($day_start == "Wed") {
                    $hari = "Rabu";
                } else if ($day_start == "Thu") {
                    $hari = "Kamis";
                } else if ($day_start == "Fri") {
                    $hari = "Jumat";
                } else if ($day_start == "Sat") {
                    $hari = "Sabtu";
                }

                $day_end = date('D', strtotime($value->end_date));
                if ($day_end == "Sun") {
                    $hari2 = "Minggu";
                } else if ($day_end == "Mon") {
                    $hari2 = "Senin";
                } else if ($day_end == "Tue") {
                    $hari2 = "Selasa";
                } else if ($day_end == "Wed") {
                    $hari2 = "Rabu";
                } else if ($day_end == "Thu") {
                    $hari2 = "Kamis";
                } else if ($day_end == "Fri") {
                    $hari2 = "Jumat";
                } else if ($day_end == "Sat") {
                    $hari2 = "Sabtu";
                }
			}
			$a = "23";
			foreach ($array as $array3){
				$npk				= $array3->user_npk;
				$nama 				= $array3->nama;
				$notes				= $array3->notes;
				$start_date 		= $array3->startdate;
				$start_date2  		= date('d-m-Y',strtotime($start_date));
				$end_date 			= $array3->enddate;
				$end_date2 			= date('d-m-Y',strtotime($end_date));
				$start_planning 	= $array3->startplanning;
				$start_planning2 	= date('H:i',strtotime($start_planning));
				$end_planning 		= $array3->endplanning;
				$end_planning2 		= date('H:i',strtotime($end_planning));
				if ($is_late == "1") {
					$start_actual 		= $array3->startactual;
					$start_actual2 		= date('H:i',strtotime($start_actual));
					$end_actual 		= $array3->endactual;
					$end_actual2 		= date('H:i',strtotime($end_actual));
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $start_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $end_actual2);
				}
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $nama);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $npk);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $start_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', $end_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', $notes);
				//hotfix-2.2.9, by Merio, 20161027, mengatur peletakan data jika dalam satu spkl lebih dari 25 orang
				if ( $a == 47 ) {
					$a = $a+38;
				} else if ($a == 110){
					$a = $a+38;
				} else if ($a == 173){
					$a = $a+38;
				}
				$a++;
			}
			foreach ($result2 as $result2) {
        		$jml = $result2->count;
        	}

        	//hotfix-2.2.9, by Merio, 20161027, mengatur peletakan data jika dalam satu spkl lebih dari 25 orang
        	$file->setActiveSheetIndex(0)->setCellValue('I4', $date2);
        	$file->setActiveSheetIndex(0)->setCellValue('I7', $id);
			$file->setActiveSheetIndex(0)->setCellValue('I8', $category2);
			$file->setActiveSheetIndex(0)->setCellValue('I10', $hari);
			$file->setActiveSheetIndex(0)->setCellValue('I11', $start_date2);
			$file->setActiveSheetIndex(0)->setCellValue('I12', $name_department.' / '.$name_section);
			$file->setActiveSheetIndex(0)->setCellValue('I13', $nama_pic);
			$file->setActiveSheetIndex(0)->setCellValue('D50', $category_detail);
			$file->setActiveSheetIndex(0)->setCellValue('D51', $note);
			$file->setActiveSheetIndex(0)->setCellValue('I56', $date);
			$file->setActiveSheetIndex(0)->setCellValue('C48', $jml);
			if ($is_late == "1") {
				$file->setActiveSheetIndex(0)->setCellValue('I48', $jml);
			}

			foreach ($check_pic_section as $check_pic_section) {
				$pic_spkl = $check_pic_section->nama;
				$file->setActiveSheetIndex(0)->setCellValue('I61', $pic_spkl);
				if ($is_print == '0') {
	        		$file->setActiveSheetIndex(0)->setCellValue('G3', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I3', 'Printed by : '.$pic_spkl);
	        	} else {
	        		$file->setActiveSheetIndex(0)->setCellValue('G3', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I3', 'Reprinted by : '.$pic_spkl);
	        	}

				if ($jml_mp >= 26 && $jml_mp <= 50) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}
		        } else if ($jml_mp >= 51 && $jml_mp <= 75) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Reprinted by : '.$pic_spkl);
		        	}
		        } else if ($jml_mp >= 76 && $jml_mp <= 100) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Reprinted by : '.$pic_spkl);
		        	}

		        	$file->setActiveSheetIndex(0)->setCellValue('I250', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G192', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I192', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G192', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I192', 'Reprinted by : '.$pic_spkl);
		        	}
		        }
			}

			if ($jml_mp >= 26 && $jml_mp <= 50) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}
			} else if ($jml_mp >= 51 && $jml_mp <= 75) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml);
				}
			} else if ($jml_mp >= 76 && $jml_mp <= 100) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I193', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I196', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I197', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I198', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I199', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I200', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I201', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D239', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D240', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I245', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C237', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I237', $jml);
				}
			}

			//Untuk mengganti status SPKL menjadi pernah di print oleh Leader/Admin
			$id_spkls = t_spkl::where('id_spkl',$id)->get();
			foreach ($id_spkls as $id_spkls) {
				$id_spkl2 			= $id_spkls->id;
				$is_print 			= t_spkl::findOrFail($id_spkl2);
				$is_print->is_print = "1";
				$is_print->save();
			}
		})->export('xlsx');

        return Excel::download(new YourExportClassName($id), 'filename.xlsx');
	}

	//v1.0 by Merio, 20160126, method for search result approval spkl planning
	public function spkl_actual_view_search_result($id)
	{
		$input 	 	= request::all();
		$user 		= Auth::user();
		$npk 		= $user->npk;
		$sub_sections = m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','4')
	                			->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->where('t_spkls.id_spkl','=',$id)
							->where ( function ($q) {
                			$q->where('t_spkl_details.status','4')
	                			->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8');
                			})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('m_employees.npk')
							->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			(status = 4 or status = 5 or status = 6 or status = 7 or status = -1 or status = -2 or status = -3 or status = -4
			or status = -5 or status = -6) and
			id_spkl = "'.$id.'"');
        $check_employee2 	= new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml 			= $check_employee2->jml;
        }

        $data = [
			't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'check_employee' => $check_employee,
			'check_employee2' => $check_employee2,
			'jml' => $jml
        ];

		return response()->json($data);
	}

	public function spkl_history_view_search_result($id)
	{
		$input 	 		= request::all();
		$user 			= Auth::user();
		$npk 			= $user->npk;
		$sub_sections 	= m_employee::where('npk','=',$npk)->get();
		foreach ($sub_sections as $sub_sections) {
			$sub_section = $sub_sections->sub_section;
		}
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
                			->where ( function ($q) {
				                $q->Where('t_spkl_details.status','7')
				                   ->orWhere('t_spkl_details.status','8');
		                	})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
				                $q->Where('t_spkl_details.status','7')
				                   ->orWhere('t_spkl_details.status','8');
		                	})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
				                $q->Where('t_spkl_details.status','7')
				                   ->orWhere('t_spkl_details.status','8');
		                	})
							->where('t_spkl_details.sub_section','=',$sub_section)
							->groupBy('m_employees.npk')
							->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			(status = 7 or status = 8)  and id_spkl = "'.$id.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
			't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'check_employee' => $check_employee,
			'check_employee2' => $check_employee2,
			'jml' => $jml
        ];

		return response()->json($data);
	}

	public function spkl_actual_print($id){
		$user =\Auth::user();
		$array=t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
								->where('t_spkl_details.id_spkl','=',$id)
								->where ( function ($q) {
		                			$q->where('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','-2')
		                    		->orWhere('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6');
		                		})
		                		->orderBy('m_employees.npk', 'ASC')
								->get();
		$queries 	= DB::select('select count(npk) as count from t_spkl_details where (status="4" or status="5"
		or status="6" or status="7" or status="8") and id_spkl="'.$id.'"');
        $result 	= new Collection($queries);

		Excel::load('/storage/template/SIKOLA_Report(Actual).xlsx', function($file) use($array,$result){
			$a="23";
			foreach ($result as $result) {
				$jml_actual = $result->count;
			}
			foreach ($array as $array){
				$status = $array->status;
				$reject = "Reject";
				$start_actual 	= $array->start_actual;
				$start_actual2 = date('H:i',strtotime($start_actual));
				$end_actual 	= $array->end_actual;
				$end_actual2 = date('H:i',strtotime($end_actual));
				if ($status == "-1" || $status == "-2" || $status == "-3" || $status == "-4" || $status == "-5" || $status == "-6") {
					$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', "");
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $reject);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $reject);
				} else {
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $start_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $end_actual2);
				}
				$file->setActiveSheetIndex(0)->setCellValue('I48', $jml_actual);
				$a++;
			}

		})->export('xlsx');
		return response()->json([
			'message' => 'Excel file generated and exported successfully'
		]);

	}

	public function report_export_result(){
		$input 	    	= request::all();
		$start_date 	= $input['start_date'];
		$start_date2 	= date('Ymd',strtotime($start_date));
		$end_date 		= $input['end_date'];
		$end_date2 		= date('Ymd',strtotime($end_date));
		$date 			= Carbon::now()->format('Ymd');

		// hotfix-3.5.5, 20190425, Ferry, Tambahkan opsi redownload yang pernah
		$status 		= isset($input['is_all']) ? ['7','8'] : ['7'];
		$spkls 			= t_spkl_detail::where('t_spkl_details.start_date','>=',$start_date)
								->where('t_spkl_details.start_date','<=',$end_date)
								->whereIn('t_spkl_details.status', $status)
								->orderby('t_spkl_details.npk','ASC')
								->get();
		// dd($spkls);

		if ($spkls->count() <= 0) {
			\Session::flash('flash_type','alert-danger');
        	\Session::flash('flash_message','Data tidak ada');
			return back();
		}

		try {
			DB::beginTransaction();

			Excel::load('/storage/template/SIKOLA_Report.xls', function($file) use($spkls, $start_date2, $date){
				$a="1";

				foreach ($spkls as $index => $spkl){
					$npk 			= $spkl->npk;
					//hotfix-2.2.6, by Merio, 20161018, merubah format report
					$ref_code 		= $spkl->ref_code;
					$id_spkl 		= $spkl->id_spkl;
					$notes 			= $spkl->notes;
					$start_date 	= $spkl->start_date;
					$start_date2 	= date('Ymd',strtotime($start_date));
					$end_date 		= $spkl->end_date;
					$end_date2 		= date('Ymd',strtotime($end_date));
					$start_actual 	= $spkl->start_actual;
					$start_actual2 	= date('Hi',strtotime($start_actual));
					$end_actual 	= $spkl->end_actual;
					$end_actual2 	= date('Hi',strtotime($end_actual));
					//hotfix-2.2.3, by Merio, 20161004, merubah format export sikola sesuai dengan format realta
					$file->setActiveSheetIndex(0)->setCellValue('A'.$a.'', $start_date2.'/'.$npk.'/'.$ref_code.'');
					$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $npk);
					$file->setActiveSheetIndex(0)->setCellValue('C'.$a.'', $start_date2);
					$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $date);
					$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $start_date2);
					$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', $start_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('G'.$a.'', $end_date2);
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $end_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $id_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', $notes);
					$a++;

					// hotfix-3.5.5, Ferry, 20190424, Fitur hanya download yg belum di download
					$spkl->status = 8;
					$spkl->save();

					DB::commit();
				}
			})->export('xls');
			return response()->json(['message' => 'Excel file generated and exported successfully']);
		}
		catch (\Exception $e) {
			DB::rollBack();
		}
        return response()->json('report/export/result');
	}

	// ************* SPKL Print Here **************** //
	public function t_spkl_view()
	{
		$user 	= \Auth::user();
		$npk 	= $user->npk;
		$sub_section = m_employee::where('npk','=',$npk)->get();
		foreach ($sub_section as $sub_section) {
			$sub_sections = $sub_section->sub_section;
		}
		$id_spkl = t_spkl_detail::where('sub_section','=',$sub_sections)
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
								->where('is_closed','=','1')
								->orderBy('id','=','DESC')
		                		->groupBy('id_spkl')
								->get();
                                return response()->json($id_spkl);
	}

	public function t_spkl_print() {
		$user 		= Auth::user();
		$username 	= $user->nama;
		$input 		= request::all();
		$id 		= $input['id_spkl'];
		$check_sub_section = m_employee::where('npk','=',$user->npk)->get();
		foreach ($check_sub_section as $check_sub_section) {
			$code_sub_section = $check_sub_section->sub_section;
		}
		$check_code_section = m_sub_section::where('code','=',$code_sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section = $check_code_section->code_section;
		}
		$check_code_department = m_section::where('code','=',$code_section)->get();
		foreach ($check_code_department as $check_code_department) {
			$code_department = $check_code_department->code_department;
		}
		$check_pic_department = m_department::where('code','=',$code_department)->get();
		foreach ($check_pic_department as $check_pic_department) {
			$npk_pic_department = $check_pic_department->npk;
		}
		$check_nama_pic = m_employee::where('npk','=',$npk_pic_department)->get();
		foreach ($check_nama_pic as $check_nama_pic) {
			$npk_pic  = $check_nama_pic->npk;
			$nama_pic = $check_nama_pic->nama;
		}
		//hotfix-1.5.5, by Merio Aji, 20160428, bug pic spkl not username but name employee (leader/admin)
		$check_pic_section = m_employee::where('npk','=',$user->npk)->get();
		$array4=t_spkl::select('*','m_categories.name as name_category','m_departments.code as name_department',
							'm_sections.name as name_section')
						->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkls.id_spkl',$id)
						->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
                    		->orWhere('t_spkl_details.status','2')
                    		->orWhere('t_spkl_details.status','3')
                    		->orWhere('t_spkl_details.status','4')
                    		->orWhere('t_spkl_details.status','5')
                    		->orWhere('t_spkl_details.status','6')
                    		->orWhere('t_spkl_details.status','7')
                    		->orWhere('t_spkl_details.status','8');
                			})
						->get();
		$array=t_spkl::select('*','m_categories.name as name_category','m_departments.code as name_department',
							'm_sections.name as name_section')
						->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkls.id_spkl',$id)
						->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
                    		->orWhere('t_spkl_details.status','2')
                    		->orWhere('t_spkl_details.status','3')
                    		->orWhere('t_spkl_details.status','4')
                    		->orWhere('t_spkl_details.status','5')
                    		->orWhere('t_spkl_details.status','6')
                    		->orWhere('t_spkl_details.status','7')
                    		->orWhere('t_spkl_details.status','8');
                			})
						->get();
		$queries2 = DB::select('select count(npk) as count from t_spkl_details where
			(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
			and id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);
		$array2 	= m_employee::where('npk','=',$user->npk)->get();
		$array3 	= t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkl_details.id_spkl','=',$id)
									->where ( function ($q) {
		                				$q->where('t_spkl_details.status','1')
		                    			->orWhere('t_spkl_details.status','2')
		                    			->orWhere('t_spkl_details.status','3')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6')
		    	                		->orWhere('t_spkl_details.status','7')
		    	                		->orWhere('t_spkl_details.status','8');
		        	        			})
									->get();
		foreach ($array4 as $array4) {
			$status 	= $array4->status;
			$is_late	= $array4->is_late;
		}
		$jml_mp 		= DB::select('select count(npk) as count from t_spkl_details where
		(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
		and id_spkl="'.$id.'"');
        $result_jml_mp 	= new Collection($jml_mp);

        foreach ($result_jml_mp as $result_jml_mp) {
			$jumlah_mp = $result_jml_mp->count;
		}
		if ($status == "1" and $is_late == "0") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4";
			}
		} elseif ($status == "1" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status == "2" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_1_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_1_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_1_4";
			}
		} elseif ($status == "2" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status == "3" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_2_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_2_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_2_4";
			}
		} elseif ($status == "3" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status == "4" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_3_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_3_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_3_4";
			}
		} elseif ($status == "4" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status == "5" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_4";
			}
		} elseif ($status == "5" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_Late_4";
			}
		} elseif ($status == "6" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_4";
			}
		} elseif ($status == "6" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_Late_4";
			}
		} elseif ($status == "7" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_4";
			}
		} elseif ($status == "7" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_Late_4";
			}
		} elseif ($status == "8" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_4";
			}
		} elseif ($status == "8" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_Late_4";
			}
		}

		$queries = DB::select('select count(npk) as count from t_spkl_details where
			(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
			and id_spkl="'.$id.'"');
        $result = new Collection($queries);
        $queries2 = DB::select('select count(npk) as count from t_spkl_details where
        	(status="4" or status="5" or status="6" or status="7" or status="8") and
			id_spkl="'.$id.'"');
        $result2 = new Collection($queries2);

        $jml_mp = DB::select('select count(npk) as count from t_spkl_details where
			(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
			and id_spkl="'.$id.'"');
        $jml_mp_result = new Collection($jml_mp);

		Excel::load('/storage/template/'.$file.'.xlsx', function($file) use($is_late,$check_pic_section,$array,$array2,
			$array3,$result,$result2,$status,$npk_pic,$nama_pic,$username,$jml_mp_result){
			$user 	=\Auth::user();
			$date 	= Carbon::now()->format('d-m-Y');
			$date2 	= Carbon::now()->format('d-m-Y, H:i');
			foreach ($array as $key => $value) {
				$id_spkl 			= $value->id_spkl;
				$start_date 		= $value->start_date;
				$start_date2 		= date('d-m-Y',strtotime($start_date));
				$end_date 			= $value->end_date;
				$end_date2 			= date('d-m-Y',strtotime($end_date));
				$note 				= $value->note;
				$category1 			= $value->category;
				$category_detail 	= $value->name_category;
				$name_department 	= $value->name_department;
				$name_section 		= $value->name_section;
				$leader_name 		= $user->npk;
				if($category1 == "1"){
					$category2 = "Biasa";
				} elseif ($category1 == "2"){
					$category2 = "Libur";
				} elseif ($category1 == "3") {
					$category2 = "Hari Libur Nasional";
				}
				$day_start = date('D', strtotime($value->start_date));
                if ($day_start == "Sun") {
                    $hari = "Minggu";
                } else if ($day_start == "Mon") {
                    $hari = "Senin";
                } else if ($day_start == "Tue") {
                    $hari = "Selasa";
                } else if ($day_start == "Wed") {
                    $hari = "Rabu";
                } else if ($day_start == "Thu") {
                    $hari = "Kamis";
                } else if ($day_start == "Fri") {
                    $hari = "Jumat";
                } else if ($day_start == "Sat") {
                    $hari = "Sabtu";
                }
                $day_end = date('D', strtotime($value->end_date));
                if ($day_end == "Sun") {
                    $hari2 = "Minggu";
                } else if ($day_end == "Mon") {
                    $hari2 = "Senin";
                } else if ($day_end == "Tue") {
                    $hari2 = "Selasa";
                } else if ($day_end == "Wed") {
                    $hari2 = "Rabu";
                } else if ($day_end == "Thu") {
                    $hari2 = "Kamis";
                } else if ($day_end == "Fri") {
                    $hari2 = "Jumat";
                } else if ($day_end == "Sat") {
                    $hari2 = "Sabtu";
                }
				$status2 = $value->status;
			}
			foreach ($array2 as $array2) {
				$nama = $array2->nama;
			}

			foreach ($jml_mp_result as $jml_mp_result) {
				$jml_mp = $jml_mp_result->count;
			}

			$a = "23";
			foreach ($array3 as $array3){
				$npk				= $array3->npk;
				$nama 				= $array3->nama;
				$start_planning 	= $array3->start_planning;
				$start_planning2 	= date('H:i',strtotime($start_planning));
				$end_planning 		= $array3->end_planning;
				$end_planning2 		= date('H:i',strtotime($end_planning));
				$notes_mp			= $array3->notes;
				if ($is_late == "1") {
					$start_actual 	= $array3->start_actual;
					$start_actual2 	= date('H:i',strtotime($start_actual));
					$end_actual 	= $array3->end_actual;
					$end_actual2 	= date('H:i',strtotime($end_actual));
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $start_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $end_actual2);
				}
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $nama);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $npk);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $start_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', $end_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', $notes_mp);
				if ($status2 == "5" || $status2 == "6" || $status2 == "7" || $status2 == "8") {
					$start_actual   = $array3->start_actual;
					$start_actual2  = date('H:i',strtotime($start_actual));
					$end_actual 	= $array3->end_actual;
					$end_actual2    = date('H:i',strtotime($end_actual));
					$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $start_actual2);
					$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $end_actual2);
				}
				//hotfix-2.2.9, by Merio, 20161027, mengatur peletakan data jika dalam satu spkl lebih dari 25 orang
				if ( $a == 47 ) {
					$a = $a+38;
				} else if ($a == 110) {
					$a = $a+38;
				} else if ($a == 173) {
					$a = $a+38;
				}

				$a++;
			}
			foreach ($result as $result) {
        		$jml=$result->count;
        	}

			foreach ($result2 as $result2) {
				if ($is_late == "1" and ($status == "4" or $status == "5" or $status == "6" or $status == "7"
					or $status == "8") ) {
        			$jml2 = $result2->count;
        		}
        		$jml2 = $result2->count;
        	}
			$file->setActiveSheetIndex(0)->setCellValue('I7', $id_spkl);
			$file->setActiveSheetIndex(0)->setCellValue('I8', $category2);
			$file->setActiveSheetIndex(0)->setCellValue('I4', $date2);
			$file->setActiveSheetIndex(0)->setCellValue('I10', $hari);
			$file->setActiveSheetIndex(0)->setCellValue('I11', $start_date2);
			$file->setActiveSheetIndex(0)->setCellValue('I12', $name_department.' / '.$name_section);
			$file->setActiveSheetIndex(0)->setCellValue('I13', $nama_pic);
			$file->setActiveSheetIndex(0)->setCellValue('D50', $category_detail);
			$file->setActiveSheetIndex(0)->setCellValue('D51', $note);
			$file->setActiveSheetIndex(0)->setCellValue('I56', $date);
			$file->setActiveSheetIndex(0)->setCellValue('C48', $jml);
			if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
			|| $status == '8') {
				$file->setActiveSheetIndex(0)->setCellValue('I48', $jml2);
			}
			if ($jml_mp >= 26 && $jml_mp <= 50) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml2);
				}
			} else if ($jml_mp >= 51 && $jml_mp <= 75) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml2);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml2);
				}
			} else if ($jml_mp >= 76 && $jml_mp <= 100) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml2);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml2);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I193', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I196', $id_spkl);
				$file->setActiveSheetIndex(0)->setCellValue('I197', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I198', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I199', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I200', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I201', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D239', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D240', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I245', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C237', $jml);
				if ( ($status == '4' and $is_late == '1') || $status == '5' || $status == '6' || $status == '7'
					|| $status == '8') {
					$file->setActiveSheetIndex(0)->setCellValue('I237', $jml2);
				}
			}

			foreach ($check_pic_section as $check_pic_section) {
				$pic_spkl = $check_pic_section->nama;
				$file->setActiveSheetIndex(0)->setCellValue('I61', $pic_spkl);
	        	$file->setActiveSheetIndex(0)->setCellValue('G3', 'App Version : 3.0.2');
				$file->setActiveSheetIndex(0)->setCellValue('I3', 'Reprinted by : '.$pic_spkl);

				if ($jml_mp >= 26 && $jml_mp <= 50) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        } else if ($jml_mp >= 51 && $jml_mp <= 75) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);

		        } else if ($jml_mp >= 76 && $jml_mp <= 100) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);

					$file->setActiveSheetIndex(0)->setCellValue('I250', $pic_spkl);
					$file->setActiveSheetIndex(0)->setCellValue('G192', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I192', 'Printed by : '.$pic_spkl);
		        }
			}

		})->export('xlsx');

		return response()->json(['message' => 'Excel file exported successfully'], 200);
	}

	//method untuk download spkl di view spkl realisasi
	public function t_spkl_print_2($id){
		$user 		= Auth::user();
		$username 	= $user->nama;
		$input 		= request::all();
		$check_sub_section 		= m_employee::where('npk','=',$user->npk)->get();
		foreach ($check_sub_section as $check_sub_section) {
			$code_sub_section 	= $check_sub_section->sub_section;
		}
		$check_code_section 	= m_sub_section::where('code','=',$code_sub_section)->get();
		foreach ($check_code_section as $check_code_section) {
			$code_section 		= $check_code_section->code_section;
		}
		$check_code_department 	= m_section::where('code','=',$code_section)->get();
		foreach ($check_code_department as $check_code_department) {
			$code_department 	= $check_code_department->code_department;
		}
		$check_pic_department 	= m_department::where('code','=',$code_department)->get();
		foreach ($check_pic_department as $check_pic_department) {
			$npk_pic_department = $check_pic_department->npk;
		}
		$check_nama_pic 		= m_employee::where('npk','=',$npk_pic_department)->get();
		foreach ($check_nama_pic as $check_nama_pic) {
			$npk_pic  = $check_nama_pic->npk;
			$nama_pic = $check_nama_pic->nama;
		}
		//hotfix-1.5.5, by Merio Aji, 20160428, bug pic spkl not username but name employee (leader/admin)
		$check_pic_section = m_employee::where('npk','=',$user->npk)->get();
		$array4 = t_spkl::select('*','m_categories.name as name_category','m_departments.code as name_department',
							'm_sections.name as name_section')
						->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkls.id_spkl',$id)
						->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
                    		->orWhere('t_spkl_details.status','2')
                    		->orWhere('t_spkl_details.status','3')
                    		->orWhere('t_spkl_details.status','4')
                    		->orWhere('t_spkl_details.status','5')
                    		->orWhere('t_spkl_details.status','6')
                    		->orWhere('t_spkl_details.status','7')
                    		->orWhere('t_spkl_details.status','8');
                			})
						->get();
		$array = t_spkl::select('*','m_categories.name as name_category','m_departments.code as name_department',
							'm_sections.name as name_section')
						->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
						->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
						->join('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('t_spkls.id_spkl',$id)
						->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
                    		->orWhere('t_spkl_details.status','2')
                    		->orWhere('t_spkl_details.status','3')
                    		->orWhere('t_spkl_details.status','4')
                    		->orWhere('t_spkl_details.status','5')
                    		->orWhere('t_spkl_details.status','6')
                    		->orWhere('t_spkl_details.status','7')
                    		->orWhere('t_spkl_details.status','8');
                			})
						->get();
		// $queries2 	= DB::select('select count(npk) as count from t_spkl_details where
		// 	(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
		// 	and id_spkl = "'.$id.'"');
  //       $result2 	= new Collection($queries2);
		$array2 	= m_employee::where('npk','=',$user->npk)->get();
		$array3 	= t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
									->where('t_spkl_details.id_spkl','=',$id)
									->where ( function ($q) {
		                				$q->where('t_spkl_details.status','1')
		                    			->orWhere('t_spkl_details.status','2')
		                    			->orWhere('t_spkl_details.status','3')
			                    		->orWhere('t_spkl_details.status','4')
			                    		->orWhere('t_spkl_details.status','5')
			                    		->orWhere('t_spkl_details.status','6')
		    	                		->orWhere('t_spkl_details.status','7')
		    	                		->orWhere('t_spkl_details.status','8');
		        	        			})
									->get();
		foreach ($array4 as $array4) {
			$status_spkl 	= $array4->status;
			$is_late	= $array4->is_late;
		}

		$jml_mp 		= DB::select('select count(npk) as count from t_spkl_details where
		(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
		and id_spkl="'.$id.'"');
        $result_jml_mp 	= new Collection($jml_mp);

		foreach ($result_jml_mp as $result_jml_mp) {
			$jumlah_mp = $result_jml_mp->count;
		}

		if ($status_spkl == "1" and $is_late == "0") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4";
			}
		} elseif ($status_spkl == "1" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "2" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_1_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_1_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_1_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_1_4";
			}
		} elseif ($status_spkl == "2" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "3" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_2_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_2_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_2_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_2_4";
			}
		} elseif ($status_spkl == "3" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "4" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_3_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_3_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_3_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_3_4";
			}
		} elseif ($status_spkl == "4" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_Late_4";
			}
		} elseif ($status_spkl == "5" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_4";
			}
		} elseif ($status_spkl == "5" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_4_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_4_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_4_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_4_Late_4";
			}
		} elseif ($status_spkl == "6" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_4";
			}
		} elseif ($status_spkl == "6" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_5_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_5_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_5_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_5_Late_4";
			}
		} elseif ($status_spkl == "7" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_4";
			}
		} elseif ($status_spkl == "7" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_Late_4";
			}
		} elseif ($status_spkl == "8" and ($is_late == "0"  || $is_late == "")) {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_4";
			}
		} elseif ($status_spkl == "8" and $is_late == "1") {
			if ($jumlah_mp <= 25) {
				$file = "SIKOLA_Report_6_Late_1";
			} else if ($jumlah_mp >= 26 && $jumlah_mp <= 50) {
				$file = "SIKOLA_Report_6_Late_2";
			} else if ($jumlah_mp >= 51 && $jumlah_mp <= 75) {
				$file = "SIKOLA_Report_6_Late_3";
			} else if ($jumlah_mp >= 76 && $jumlah_mp <= 100) {
				$file = "SIKOLA_Report_6_Late_4";
			}
		}

		$queries 	= DB::select('select count(npk) as count from t_spkl_details where
			(status="1" or status="2" or status="3" or status="4" or status="5" or status="6" or status="7" or status="8")
			and id_spkl="'.$id.'"');
        $result 	= new Collection($queries);
        $queries2 	= DB::select('select count(npk) as count from t_spkl_details where
        	(status="4" or status="5" or status="6" or status="7" or status="8") and
			id_spkl="'.$id.'"');
        $result2 	= new Collection($queries2);
		Excel::load('/storage/template/'.$file.'.xlsx', function($file) use($is_late,$check_pic_section,$array,$array2,
			$array3,$result,$result2,$status_spkl,$npk_pic,$nama_pic,$username, $id){
			foreach ($result2 as $result2) {
				$queries2 = $result2->count;
			}
			$user 	=\Auth::user();
			$date 	= Carbon::now()->format('d-m-Y');
			$date2 	= Carbon::now()->format('d-m-Y, H:i');
			foreach ($array as $key => $value) {
				$id_spkl 			= $value->id_spkl;
				$is_print 			= $value->is_print;
				$start_date 		= $value->start_date;
				$start_date2 		= date('d-m-Y',strtotime($start_date));
				$end_date 			= $value->end_date;
				$end_date2 			= date('d-m-Y',strtotime($end_date));
				$note 				= $value->note;
				$category1 			= $value->category;
				$category_detail 	= $value->name_category;
				$name_department 	= $value->name_department;
				$name_section 		= $value->name_section;
				$leader_name 		= $user->npk;
				if($category1 == "1"){
					$category2 = "Biasa";
				} elseif ($category1 == "2"){
					$category2 = "Libur";
				} elseif ($category1 == "3") {
					$category2 = "Hari Libur Nasional";
				}
				$day_start = date('D', strtotime($value->start_date));
                if ($day_start == "Sun") {
                    $hari = "Minggu";
                } else if ($day_start == "Mon") {
                    $hari = "Senin";
                } else if ($day_start == "Tue") {
                    $hari = "Selasa";
                } else if ($day_start == "Wed") {
                    $hari = "Rabu";
                } else if ($day_start == "Thu") {
                    $hari = "Kamis";
                } else if ($day_start == "Fri") {
                    $hari = "Jumat";
                } else if ($day_start == "Sat") {
                    $hari = "Sabtu";
                }
                $day_end = date('D', strtotime($value->end_date));
                if ($day_end == "Sun") {
                    $hari2 = "Minggu";
                } else if ($day_end == "Mon") {
                    $hari2 = "Senin";
                } else if ($day_end == "Tue") {
                    $hari2 = "Selasa";
                } else if ($day_end == "Wed") {
                    $hari2 = "Rabu";
                } else if ($day_end == "Thu") {
                    $hari2 = "Kamis";
                } else if ($day_end == "Fri") {
                    $hari2 = "Jumat";
                } else if ($day_end == "Sat") {
                    $hari2 = "Sabtu";
                }
				$status2 = $value->status;
			}


			//hotfix-2.2.9, by Yudo Maryanto, mengubah report > 25
			$a = "23";
			foreach ($array3 as $array3){
				$npk				= $array3->npk;
				$nama 				= $array3->nama;
				$notes				= $array3->notes;
				$start_date 		= $array3->start_date;
				$start_date2  		= date('d-m-Y',strtotime($start_date));
				$end_date 			= $array3->end_date;
				$end_date2 			= date('d-m-Y',strtotime($end_date));
				$start_planning 	= $array3->start_planning;
				$start_planning2 	= date('H:i',strtotime($start_planning));
				$end_planning 		= $array3->end_planning;
				$end_planning2 		= date('H:i',strtotime($end_planning));
				$start_actual 		= $array3->start_actual;
				$start_actual2 		= date('H:i',strtotime($start_actual));
				$end_actual 		= $array3->end_actual;
				$end_actual2 		= date('H:i',strtotime($end_actual));
				$file->setActiveSheetIndex(0)->setCellValue('H'.$a.'', $start_actual2);
				$file->setActiveSheetIndex(0)->setCellValue('I'.$a.'', $end_actual2);
				$file->setActiveSheetIndex(0)->setCellValue('B'.$a.'', $nama);
				$file->setActiveSheetIndex(0)->setCellValue('D'.$a.'', $npk);
				$file->setActiveSheetIndex(0)->setCellValue('E'.$a.'', $start_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('F'.$a.'', $end_planning2);
				$file->setActiveSheetIndex(0)->setCellValue('J'.$a.'', $notes);
				//hotfix-2.2.9, by Merio, 20161027, mengatur peletakan data jika dalam satu spkl lebih dari 25 orang
				if ( $a == 47 ) {
					$a = $a+38;
				} else if ($a == 110){
					$a = $a+38;
				} else if ($a == 173){
					$a = $a+38;
				}
				$a++;
			}
			foreach ($result as $result) {
        		$jml = $result->count;
        	}

        	//hotfix-2.2.9, by Merio, 20161027, mengatur peletakan data jika dalam satu spkl lebih dari 25 orang
        	$file->setActiveSheetIndex(0)->setCellValue('I4', $date2);
        	$file->setActiveSheetIndex(0)->setCellValue('I7', $id);
			$file->setActiveSheetIndex(0)->setCellValue('I8', $category2);
			$file->setActiveSheetIndex(0)->setCellValue('I10', $hari);
			$file->setActiveSheetIndex(0)->setCellValue('I11', $start_date2);
			$file->setActiveSheetIndex(0)->setCellValue('I12', $name_department.' / '.$name_section);
			$file->setActiveSheetIndex(0)->setCellValue('I13', $nama_pic);
			$file->setActiveSheetIndex(0)->setCellValue('D50', $category_detail);
			$file->setActiveSheetIndex(0)->setCellValue('D51', $note);
			$file->setActiveSheetIndex(0)->setCellValue('I56', $date);
			$file->setActiveSheetIndex(0)->setCellValue('C48', $jml);
			if ($is_late == "1") {
				$file->setActiveSheetIndex(0)->setCellValue('I48', $jml);
			}

			foreach ($check_pic_section as $check_pic_section) {
				$pic_spkl = $check_pic_section->nama;
				$file->setActiveSheetIndex(0)->setCellValue('I61', $pic_spkl);
				if ($is_print == '0') {
	        		$file->setActiveSheetIndex(0)->setCellValue('G3', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I3', 'Printed by : '.$pic_spkl);
	        	} else {
	        		$file->setActiveSheetIndex(0)->setCellValue('G3', 'App Version : 3.0.2');
					$file->setActiveSheetIndex(0)->setCellValue('I3', 'Reprinted by : '.$pic_spkl);
	        	}

				if ($queries2 >= 26 && $queries2 <= 50) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}
		        } else if ($queries2 >= 51 && $queries2 <= 75) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Reprinted by : '.$pic_spkl);
		        	}
		        } else if ($queries2 >= 76 && $queries2 <= 100) {
					$file->setActiveSheetIndex(0)->setCellValue('I124', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G66', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I66', 'Reprinted by : '.$pic_spkl);
		        	}

		        	$file->setActiveSheetIndex(0)->setCellValue('I187', $pic_spkl);
					if ($is_print == '0') {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Printed by : '.$pic_spkl);
		        	} else {
		        		$file->setActiveSheetIndex(0)->setCellValue('G129', 'App Version : 3.0.2');
						$file->setActiveSheetIndex(0)->setCellValue('I129', 'Reprinted by : '.$pic_spkl);
		        	}
		        }
			}

			if ($queries2 >= 26 && $queries2 <= 50) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}
			} else if ($queries2 >= 51 && $queries2 <= 75) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml);
				}
			} else if ($queries2 >= 76 && $queries2 <= 100) {
				$file->setActiveSheetIndex(0)->setCellValue('I67', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I70', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I71', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I73', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I74', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I75', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I76', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D113', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D114', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I119', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C111', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I111', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I130', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I133', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I134', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I136', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I137', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I138', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I139', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D176', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D177', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I182', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C174', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I174', $jml);
				}

				$file->setActiveSheetIndex(0)->setCellValue('I193', $date2);
				$file->setActiveSheetIndex(0)->setCellValue('I196', $id);
				$file->setActiveSheetIndex(0)->setCellValue('I197', $category2);
				$file->setActiveSheetIndex(0)->setCellValue('I198', $hari);
				$file->setActiveSheetIndex(0)->setCellValue('I199', $start_date2);
				$file->setActiveSheetIndex(0)->setCellValue('I200', $name_department.' / '.$name_section);
				$file->setActiveSheetIndex(0)->setCellValue('I201', $nama_pic);
				$file->setActiveSheetIndex(0)->setCellValue('D239', $category_detail);
				$file->setActiveSheetIndex(0)->setCellValue('D240', $note);
				$file->setActiveSheetIndex(0)->setCellValue('I245', $date);
				$file->setActiveSheetIndex(0)->setCellValue('C237', $jml);
				if ($is_late == "1") {
					$file->setActiveSheetIndex(0)->setCellValue('I237', $jml);
				}
			}

			//Untuk mengganti status SPKL menjadi pernah di print oleh Leader/Admin
			$id_spkls = t_spkl::where('id_spkl',$id)->get();
			foreach ($id_spkls as $id_spkls) {
				$id_spkl2 			= $id_spkls->id;
				$is_print 			= t_spkl::findOrFail($id_spkl2);
				$is_print->is_print = "1";
				$is_print->save();
			}
		})->export('xlsx');
		return response()->json(['message' => 'Excel file exported successfully'], 200);
	}

	//hotfix-1.9.2, by Merio Aji, 20160811, leader/operator cancel overtime pada saat input realisasi
	public function spkl_reject($id,$id2) {

		$tanggal_server 	= Carbon::now()->format('Y-m-d H:i:s');
		$update_spkl 		= t_spkl_detail::where('id_spkl',$id)
											->where('npk',$id2)
											->where ( function ($q) {
					                			$q->where('t_spkl_details.status','4')
					                    		->orWhere('t_spkl_details.status','5');
					                		})
											->get();
		foreach ($update_spkl as $update_spkl) {
			$id_update 	= $update_spkl->id;
			if ($update_spkl->quota_ot_actual > 0) {
				$quota_ot  	= $update_spkl->quota_ot_actual;
			} else {
				$quota_ot  	= $update_spkl->quota_ot;
			}
			$date_ot 	= $update_spkl->start_date;
			$month 		= Carbon::parse($date_ot)->format('m');
		}
		$budget_employee = m_employee::where('npk',$id2)->get();
		foreach ($budget_employee as $budget_employee) {
			$id_employee 				= $budget_employee->id;
			$employment_status			= $budget_employee->employment_status;
			if ($month == "01") {
				$quota_used 	= $budget_employee->quota_used_1;
				$quota_remain 	= $budget_employee->quota_remain_1;
				$quota_par 		= "quota_used_1";
			} else if ($month == "02") {
				$quota_used 	= $budget_employee->quota_used_2;
				$quota_remain 	= $budget_employee->quota_remain_2;
				$quota_par 		= "quota_used_2";
			} else if ($month == "03") {
				$quota_used 	= $budget_employee->quota_used_3;
				$quota_remain 	= $budget_employee->quota_remain_3;
				$quota_par 		= "quota_used_3";
			} else if ($month == "04") {
				$quota_used 	= $budget_employee->quota_used_4;
				$quota_remain 	= $budget_employee->quota_remain_4;
				$quota_par 		= "quota_used_4";
			} else if ($month == "05") {
				$quota_used 	= $budget_employee->quota_used_5;
				$quota_remain 	= $budget_employee->quota_remain_5;
				$quota_par 		= "quota_used_5";
			} else if ($month == "06") {
				$quota_used 	= $budget_employee->quota_used_6;
				$quota_remain 	= $budget_employee->quota_remain_6;
				$quota_par 		= "quota_used_6";
			} else if ($month == "07") {
				$quota_used 	= $budget_employee->quota_used_7;
				$quota_remain 	= $budget_employee->quota_remain_7;
				$quota_par 		= "quota_used_7";
			} else if ($month == "08") {
				$quota_used 	= $budget_employee->quota_used_8;
				$quota_remain 	= $budget_employee->quota_remain_8;
				$quota_par 		= "quota_used_8";
			} else if ($month == "09") {
				$quota_used 	= $budget_employee->quota_used_9;
				$quota_remain 	= $budget_employee->quota_remain_9;
				$quota_par 		= "quota_used_9";
			} else if ($month == "10") {
				$quota_used 	= $budget_employee->quota_used_10;
				$quota_remain 	= $budget_employee->quota_remain_10;
				$quota_par 		= "quota_used_10";
			} else if ($month == "11") {
				$quota_used 	= $budget_employee->quota_used_11;
				$quota_remain 	= $budget_employee->quota_remain_11;
				$quota_par 		= "quota_used_11";
			} else if ($month == "12") {
				$quota_used 	= $budget_employee->quota_used_12;
				$quota_remain 	= $budget_employee->quota_remain_12;
				$quota_par 		= "quota_used_12";
			}
		}
		$pengembalian_quota  			= $quota_used-$quota_ot;
		$check_emp 						= m_employee::findOrFail($id_employee);
		$check_emp->$quota_par 			= $pengembalian_quota;
		$check_emp->save();

		$query_update 					= t_spkl_detail::findOrFail($id_update);
		$query_update->status 			= '-7';
		$query_update->reject_date 		= $tanggal_server;
		$query_update->save();

        $data = [
            'quota_returned' => $pengembalian_quota,
            'update' => $query_update,
        ];

		return response()->json($data);
	}

	//hotfix-1.9.3, 20160815, by Merio, fitur SPKL List
	public function spkl_list()
	{
		// dev-2.1, Ferry, 20160830, Tampilan untuk spkl list menggunakan ajax
		$type = config('constant.spkl.step.planning');
		$months = t_spkl::getMonths();

        $data = [
            'type' => $type,
			'months' => $months
        ];

		return response()->json($data);
	}

	//hotfix-1.9.3, 20160815, by Merio, fitur SPKL List
	public function spkl_list_realization()
	{
		// dev-2.1, Ferry, 20160830, Tampilan untuk spkl list menggunakan ajax
		$type = config('constant.spkl.step.realization');
		$months = t_spkl::getMonths();

        $data = [
			'type' => $type,
			'months' => $months
        ];

		return response()->json($data);
	}

	public function spkl_list_done()
	{
		// dev-2.1, Ferry, 20160830, Tampilan untuk spkl list menggunakan ajax
		$type = config('constant.spkl.step.done');
		$months = t_spkl::getMonths();

        $data =[
            'type' => $type,
			'months' => $months
        ];

        return response()->json($data);
    }

	public function spkl_list_reject()
	{
		// dev-2.1, Ferry, 20160830, Tampilan untuk spkl list menggunakan ajax
		$type = config('constant.spkl.step.rejected');
		$months = t_spkl::getMonths();

        $data = [
			'type' => $type,
			'months' => $months
        ];

		return response()->json($data);
    	}

	public function spkl_list_view_search_result($id)
	{
		$input 	 		= request::all();
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
                			->where ( function ($q) {
	                			$q->where('t_spkl_details.status','1')
		                    		->orWhere('t_spkl_details.status','2')
		                    		->orWhere('t_spkl_details.status','3')
		                    		->orWhere('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6')
		                    		->orWhere('t_spkl_details.status','7')
		                    		->orWhere('t_spkl_details.status','8')
		                    		->orWhere('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','-2')
		                    		->orWhere('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','-4')
		                    		->orWhere('t_spkl_details.status','-5')
		                    		->orWhere('t_spkl_details.status','-6')
		                    		->orWhere('t_spkl_details.status','-7');
	                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
	                			$q->where('t_spkl_details.status','1')
		                    		->orWhere('t_spkl_details.status','2')
		                    		->orWhere('t_spkl_details.status','3')
		                    		->orWhere('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6')
		                    		->orWhere('t_spkl_details.status','7')
		                    		->orWhere('t_spkl_details.status','8')
		                    		->orWhere('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','-2')
		                    		->orWhere('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','-4')
		                    		->orWhere('t_spkl_details.status','-5')
		                    		->orWhere('t_spkl_details.status','-6')
		                    		->orWhere('t_spkl_details.status','-7');
	                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::select('*', 't_spkl_details.updated_at AS latest_update')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
	                			$q->where('t_spkl_details.status','1')
		                    		->orWhere('t_spkl_details.status','2')
		                    		->orWhere('t_spkl_details.status','3')
		                    		->orWhere('t_spkl_details.status','4')
		                    		->orWhere('t_spkl_details.status','5')
		                    		->orWhere('t_spkl_details.status','6')
		                    		->orWhere('t_spkl_details.status','7')
		                    		->orWhere('t_spkl_details.status','8')
		                    		->orWhere('t_spkl_details.status','-1')
		                    		->orWhere('t_spkl_details.status','-2')
		                    		->orWhere('t_spkl_details.status','-3')
		                    		->orWhere('t_spkl_details.status','-4')
		                    		->orWhere('t_spkl_details.status','-5')
		                    		->orWhere('t_spkl_details.status','-6')
		                    		->orWhere('t_spkl_details.status','-7');
	                			})
							->groupBy('m_employees.npk')
							->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			(status = 1 or status = 2 or status = 3 or status = 4 or status = 5 or status = 6 or status = 7
			or status = -1 or status = -2 or status = -3 or status = -4 or status = -5 or status = -6 or status = -7)
			and id_spkl = "'.$id.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
            't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'check_employee' => $check_employee,
			'check_employee2' => $check_employee2,
			'jml' => $jml
        ];

		return response()->json($data);
	}

	public function spkl_list_view_search_result2()
	{
		$user    		= Auth::user();
		$input 	 		= request::all();
		$id 			= $input['id_spkl'];
		if ($user->role == "Leader") {
			$m_employees = m_employee::where('npk',$user->npk)->get();
			foreach ($m_employees as $m_employees) {
				$sub_section = $m_employees->sub_section;
			}
			$check_spkl = t_spkl_detail::where('id_spkl',$id)->get();
			if (count($check_spkl)==0) {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, tidak ditemukan SPKL dengan ID SPKL '.$id.' ');
				return response()->json([
                    'status' => 'Error',
                    'message' => 'tidak ditemukan SPKL dengan Id SPKL' .$id.' '
                ]);
			}
			foreach ($check_spkl as $check_spkl) {
				$sub_sections = $check_spkl->sub_section;
			}
			if ($sub_section != $sub_sections) {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, anda tidak mempunyai akses untuk membuka SPKL dengan Id SPKL '.$id.' ');
				return response()->json([
                    'status' => 'Error',
                    'message' => 'anda tidak mempunyai akses untuk membuka SPKL dengan Id SPKL' .$id.' '
                ]);
			}
		}
		$t_spkl  = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl',$id)
                			->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$category = t_spkl::select('*','m_categories.name as name_category')
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
							->groupBy('t_spkls.id_spkl')
							->get();
		$t_spkl_employee = t_spkl::join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->join('m_employees','m_employees.npk','=','t_spkl_details.npk')
							->where('t_spkls.id_spkl','=',$id)
	                    	->where ( function ($q) {
                			$q->where('t_spkl_details.status','1')
	                    		->orWhere('t_spkl_details.status','2')
	                    		->orWhere('t_spkl_details.status','3')
	                    		->orWhere('t_spkl_details.status','4')
	                    		->orWhere('t_spkl_details.status','5')
	                    		->orWhere('t_spkl_details.status','6')
	                    		->orWhere('t_spkl_details.status','7')
	                    		->orWhere('t_spkl_details.status','8')
	                    		->orWhere('t_spkl_details.status','-1')
	                    		->orWhere('t_spkl_details.status','-2')
	                    		->orWhere('t_spkl_details.status','-3')
	                    		->orWhere('t_spkl_details.status','-4')
	                    		->orWhere('t_spkl_details.status','-5')
	                    		->orWhere('t_spkl_details.status','-6')
	                    		->orWhere('t_spkl_details.status','-7');
                			})
							->groupBy('m_employees.npk')
							->get();
		//hotfix-1.5.16, by Merio Aji, 20161205, add jumlah man power
		$check_employee  = DB::select('select count(npk) as jml from t_spkl_details where
			(status = 1 or status = 2 or status = 3 or status = 4 or status = 5 or status = 6 or status = 7
			or status = -1 or status = -2 or status = -3 or status = -4 or status = -5 or status = -6 or status = -7)
			and id_spkl = "'.$id.'"');
        $check_employee2 = new Collection($check_employee);
        foreach ($check_employee2 as $check_employee2) {
        	$jml = $check_employee2->jml;
        }

        $data = [
			'id' => $id,
			't_spkl' => $t_spkl,
			'category' => $category,
			't_spkl_employee' => $t_spkl_employee,
			'check_employee' => $check_employee,
			'check_employee2' => $check_employee2,
			'jml' => $jml
        ];

		return response()->json($data);
	}

	public function spkl_list_2_view() {
		$user 		= Auth::user();
		$spkl_op 	= t_spkl::select('*','t_spkls.id_spkl as id_spkls',
							'm_sections.name as section_name','m_sub_sections.name as sub_section_name',
							'm_departments.name as department_name','m_categories.name as category_name',
							DB::raw('count(t_spkl_details.npk) as jml'))
							->leftJoin('m_categories','m_categories.code','=','t_spkls.category_detail')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->leftjoin('m_sub_sections','m_sub_sections.code','=','t_spkl_details.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->where('t_spkls.npk_2',$user->npk)
							->where ( function ($q) {
		                			$q->Where('t_spkl_details.status','1')
		                			->orWhere('t_spkl_details.status','2')
		                			->orWhere('t_spkl_details.status','3')
		                			->orWhere('t_spkl_details.status','4')
		                			->orWhere('t_spkl_details.status','5')
		                			->orWhere('t_spkl_details.status','6')
		                			->orWhere('t_spkl_details.status','7')
		                			->orWhere('t_spkl_details.status','8');
		                	})
							->groupBy('t_spkls.id_spkl')
							->orderBy('t_spkls.id','=','DESC')
							->get();
		$spkl_hours 	= 0;	// dev-1.7.0, Ferry, 20160617, inisiasi untuk perhitungan di list approval

        $data = [
			'spkl_op' => $spkl_op,
			'spkl_hours' => $spkl_hours
        ];

        return response()->json($data);
	}

	public function check_overtime_mp()
	{
        $user 		  = Auth::user();
		if ($user->role == "HR Admin") {
			$m_employee = m_employee::where('status_emp','=','1')
									->where ( function ($q) {
			                			$q->where('occupation','LDR')
			                    		->orWhere('occupation','OPR');
			                			})
									->get();
		}elseif ($user->role == "Supervisor") {
	        $m_employee = m_employee::select('*')
	        			  ->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
	        			  ->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
	        			  ->where ( function ($q) {
                			$q->where('occupation','LDR')
                    		  ->orWhere('occupation','OPR');
			                })
	        			  ->where('status_emp','=','1')
	        			  ->where('m_sections.npk',$user->npk)
						  ->get();
		} else {
			$sub_section  = m_employee::where('npk',$user->npk)->get();
			foreach ($sub_section as $sub_section) {
				$sub_sections = $sub_section->sub_section;
			}
			$sections = m_section::where('npk_admin', $user->npk)->get();
			if ($sections->count() > 0) {
				$sub_secs = [];
				foreach ($sections as $tes) {
					foreach ($tes->hasManySubSection as $subsec) {
						array_push($sub_secs, $subsec->code);
					}
				}
				array_push($sub_secs, $sub_sections);

				$m_employee = m_employee::where('status_emp','=','1')
									->whereIn('sub_section',$sub_secs)
									->get();
			}else{
				$m_employee = m_employee::where('status_emp','=','1')
									->where('sub_section',$sub_sections)
									->get();
			}

		}
        return response()->json($m_employee);
	}

	public function check_overtime_mp2()
	{
		$user 		  = Auth::user();
		if ($user->role == "HR Admin") {
			$m_employee = m_employee::where('status_emp','=','1')
									->where ( function ($q) {
			                		$q->where('occupation','LDR')
			                    		->orWhere('occupation','OPR');
			                		})
									->get();
		} else {
			$sub_section  = m_employee::where('npk',$user->npk)->get();
			foreach ($sub_section as $sub_section) {
				$sub_sections = $sub_section->sub_section;
			}
	        $sections = m_section::where('npk_admin', $user->npk)->get();
			if ($sections->count() > 0) {
				$sub_secs = [];
				foreach ($sections as $tes) {
					foreach ($tes->hasManySubSection as $subsec) {
						array_push($sub_secs, $subsec->code);
					}
				}
				array_push($sub_secs, $sub_sections);

				$m_employee = m_employee::where('status_emp','=','1')
									->whereIn('sub_section',$sub_secs)
									->get();
			}else{
				$m_employee = m_employee::where('status_emp','=','1')
									->where('sub_section',$sub_sections)
									->get();
			}
		}
		$input 	 	= request::all();
		$npk 		= $input['npk'];
		$month 		= $input['month'];
		$queries 	= DB::select('select * from t_spkl_details
            where month(start_date) = '.$month.' and npk = "'.$npk.'"
            order by id DESC');
        $result 	= new Collection($queries);

        $data = [
			'm_employee' => $m_employee,
			'spkl_details' => $result
        ];
        return response()->json($data);
	}

	//hotfix-2.2.2, by Merio, 20160922, untuk menampilkan form inputan change mp overtime di realisasi
	public function change_ot_actual_view($id,$id2)
	{
		$spkl_actual 		= t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
											->where('t_spkl_details.id_spkl',$id)
											->where('t_spkl_details.npk',$id2)
											->get();
		$check_clv 			= t_spkl::select('kolektif')
									->where('id_spkl',$id)
									->get();
		foreach ($check_clv as $check_clv) {
			$kolektif = $check_clv->kolektif;
		}
		$check_sub_section 	= m_employee::select('m_employees.sub_section as sub_sections',
										'm_departments.code as code_departments')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->where('m_employees.npk',$id2)
										->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section 		= $check_sub_section->sub_sections;
			$code_department 	= $check_sub_section->code_departments;
		}
		if ($kolektif == '1') {
			$m_employee = m_employee::select('*','m_employees.npk as npk_mp')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->whereNotIn('m_employees.npk', function($q) use ($id) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','1')
										->where('t_spkl_details.is_clv','=','1')
										->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('m_departments.code',$code_department)
									->where('m_employees.status_emp','1')
									->get();
		} else {
			$m_employee = m_employee::select('*','npk as npk_mp')
									->whereNotIn('m_employees.npk', function($q) use ($id) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','1')
										->where('t_spkl_details.is_clv','=','0')
										->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('sub_section',$sub_section)
									->where('status_emp','1')
									->get();
		}
		$employee_before = m_employee::where('npk',$id2)->get();

        $data = [
            'spkl_actual' => $spkl_actual,
			'kolektif' => $kolektif,
			'check_sub_section' => $check_sub_section,
			'm_employee' => $m_employee,
			'employee_before' => $employee_before
        ];

        return response()->json($data);
    }

	//hotfix-2.2.2, by Merio, 20160922, untuk save change overtime
	public function change_ot_actual_save()
	{
		$input 			= request::all();
		$npk 			= $input['npk'];
		$npk_1 			= $input['npk_1'];
		$id_spkl  		= $input['id_spkl'];
		$check_exist 	= t_spkl_detail::where('npk',$npk)
										->where('id_spkl',$id_spkl)
										->get();
		if (count($check_exist)>0) {
			Session::flash('flash_type','alert-error');
	        Session::flash('flash_message','Error, karyawan dengan NPK '.$npk.' sudah dilemburkan di SPKL dengan ID SPKL '.$id_spkl.'');

            $data = [
                'npk' => $npk,
                'npk_1' => $npk_1,
                'id_spkl' => $id_spkl,
                'check_exist' => $check_exist
            ];
            return response()->json($data);
		}
		$check_quota 	= t_spkl_detail::where('npk',$npk_1)
										->where('id_spkl',$id_spkl)
										->get();
		foreach ($check_quota as $check_quota) {
			$id_spkl_update 	= $check_quota->id;
			$date_ot 			= $check_quota->start_date;
			$quota_ot 			= $check_quota->quota_ot;
			$quota_ot_actual 	= $check_quota->quota_ot_actual;
			$start_actual 		= $check_quota->start_actual;
			$end_actual 		= $check_quota->end_actual;
		}

		if ($start_actual == "00:00:00" && $end_actual == "00:00:00") {
			$quota_ot_used = $quota_ot;
		} else {
			$quota_ot_used = $quota_ot_actual;
		}

		$mounth = Carbon::parse($date_ot)->format('n');

		$check_employee_before 	= m_employee::where('npk',$npk_1)->get();
		$check_employee_after 	= m_employee::where('npk',$npk)->get();

		foreach ($check_employee_before as $check_employee_before) {
			$id_employee_before 	= $check_employee_before->id;
			if ($mounth == '1') {
				$quota_used_before 		= $check_employee_before->quota_used_1;
				$quota_used_before1 	= 'quota_used_1';
			} else if ($mounth == '2') {
				$quota_used_before 		= $check_employee_before->quota_used_2;
				$quota_used_before1 	= 'quota_used_2';
			} else if ($mounth == '3') {
				$quota_used_before 		= $check_employee_before->quota_used_3;
				$quota_used_before1 	= 'quota_used_3';
			} else if ($mounth == '4') {
				$quota_used_before 		= $check_employee_before->quota_used_4;
				$quota_used_before1 	= 'quota_used_4';
			} else if ($mounth == '5') {
				$quota_used_before 		= $check_employee_before->quota_used_5;
				$quota_used_before1 	= 'quota_used_5';
			} else if ($mounth == '6') {
				$quota_used_before 		= $check_employee_before->quota_used_6;
				$quota_used_before1 	= 'quota_used_6';
			} else if ($mounth == '7') {
				$quota_used_before 		= $check_employee_before->quota_used_7;
				$quota_used_before1 	= 'quota_used_7';
			} else if ($mounth == '8') {
				$quota_used_before 		= $check_employee_before->quota_used_8;
				$quota_used_before1 	= 'quota_used_8';
			} else if ($mounth == '9') {
				$quota_used_before 		= $check_employee_before->quota_used_9;
				$quota_used_before1 	= 'quota_used_9';
			} else if ($mounth == '10') {
				$quota_used_before  	= $check_employee_before->quota_used_10;
				$quota_used_before1 	= 'quota_used_10';
			} else if ($mounth == '11') {
				$quota_used_before 		= $check_employee_before->quota_used_11;
				$quota_used_before1 	= 'quota_used_11';
			} else if ($mounth == '12') {
				$quota_used_before 		= $check_employee_before->quota_used_12;
				$quota_used_before1 	= 'quota_used_12';
			}
		}
		foreach ($check_employee_after as $check_employee_after) {
			$id_employee_after 	= $check_employee_after->id;
			if ($mounth == '1') {
				$quota_used_after 		= $check_employee_after->quota_used_1;
				$quota_used_after1 		= 'quota_used_1';
				$quota_remain_after 	= $check_employee_after->quota_remain_1;
			} else if ($mounth == '2') {
				$quota_used_after 		= $check_employee_after->quota_used_2;
				$quota_used_after1 		= 'quota_used_2';
				$quota_remain_after 	= $check_employee_after->quota_remain_2;
			} else if ($mounth == '3') {
				$quota_used_after 		= $check_employee_after->quota_used_3;
				$quota_used_after1 		= 'quota_used_3';
				$quota_remain_after 	= $check_employee_after->quota_remain_3;
			} else if ($mounth == '4') {
				$quota_used_after 		= $check_employee_after->quota_used_4;
				$quota_used_after1 		= 'quota_used_4';
				$quota_remain_after 	= $check_employee_after->quota_remain_4;
			} else if ($mounth == '5') {
				$quota_used_after 		= $check_employee_after->quota_used_5;
				$quota_used_after1 		= 'quota_used_5';
				$quota_remain_after 	= $check_employee_after->quota_remain_5;
			} else if ($mounth == '6') {
				$quota_used_after 		= $check_employee_after->quota_used_6;
				$quota_used_after1 		= 'quota_used_6';
				$quota_remain_after 	= $check_employee_after->quota_remain_6;
			} else if ($mounth == '7') {
				$quota_used_after 		= $check_employee_after->quota_used_7;
				$quota_used_after1 		= 'quota_used_7';
				$quota_remain_after 	= $check_employee_after->quota_remain_7;
			} else if ($mounth == '8') {
				$quota_used_after 		= $check_employee_after->quota_used_8;
				$quota_used_after1 		= 'quota_used_8';
				$quota_remain_after 	= $check_employee_after->quota_remain_8;
			} else if ($mounth == '9') {
				$quota_used_after 		= $check_employee_after->quota_used_9;
				$quota_used_after1 		= 'quota_used_9';
				$quota_remain_after 	= $check_employee_after->quota_remain_9;
			} else if ($mounth == '10') {
				$quota_used_after  		= $check_employee_after->quota_used_10;
				$quota_used_after1 		= 'quota_used_10';
				$quota_remain_after 	= $check_employee_after->quota_remain_10;
			} else if ($mounth == '11') {
				$quota_used_after 		= $check_employee_after->quota_used_11;
				$quota_used_after1 		= 'quota_used_11';
				$quota_remain_after 	= $check_employee_after->quota_remain_11;
			} else if ($mounth == '12') {
				$quota_used_after 		= $check_employee_after->quota_used_12;
				$quota_used_after1 		= 'quota_used_12';
				$quota_remain_after 	= $check_employee_after->quota_remain_12;
			}
		}

		// $pengurangan_kuota adalah jumlah quota yang sudah digunakann dan sudah di tambah
		$employee 	= m_employee::where('npk',$npk)->first();
        $division = $employee->hasSubSection->hasSection->hasDepartment->hasDivision;
        $specialLimit = $division->specialLimit; // in minute
        $pengurangan_kuota = $quota_used_after + $quota_ot;

        if ($specialLimit) {
        	$cek_approved = t_approved_limit_spesial::where('npk',$npk)->first();

        	if (!$cek_approved) {
            	if ($pengurangan_kuota > $specialLimit->quota_limit) {
					Session::flash('flash_type','alert-danger');
			        Session::flash('flash_message','Error, Quota '. $employee->nama .' sudah melebihi '. round((int)$specialLimit->quota_limit / 60) .' jam parameter, silakan hubungi GM untuk membuka akses SPKL');
			        return response()->json()->back();
            	}
            }

        	//delete npk ketika sudah ditambah ke tabel approved
			// $delete_approved = t_approved_limit_spesial::where('npk',$employee->npk)->delete();
        }
		//hotfix-2.2.3 Mengganti MP yang lembur
		$quota_check = $quota_used_after + $quota_ot_used;
		if ($quota_check > $quota_remain_after){
			Session::flash('flash_type','alert-danger');
        	Session::flash('flash_message','Error, quota untuk karyawan dengan NPK '.$check_employee_after->npk.'melebihi batas limit, silakan hubungi Atasan Langsung anda');
				return response()->json([
					'status' => 'Error',
					'message' => 'quota untuk karyawan dengan NPK '.$check_employee_after->npk.'melebihi batas limit, silakan hubungi Atasan Langsung anda'
				]);
		}
		else{
			$quota_temp = $quota_used_before - $quota_ot_used;

			DB::table('m_employees')
            ->where('id', $id_employee_before)
            ->update([$quota_used_before1 => $quota_temp]);
		}
			$quota_temp2 = $quota_used_after + $quota_ot_used;
			DB::table('m_employees')
            ->where('id', $id_employee_after)
            ->update([$quota_used_after1 => $quota_temp2]);

		$update_spkl 				= t_spkl_detail::findOrFail($id_spkl_update);
		$update_spkl->npk 			= $npk;
		//hotfix-2.2.6, by Merio, 20161020, menambahkan informasi npk sebelumnya sebelum di update
		$update_spkl->npk_before 	= $npk_1;
		$update_spkl->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, proses perubahan MP dari NPK '.$npk.' menjadi NPK '.$npk_1.'di SPKL dengan ID SPKL '.$id_spkl.' berhasil');
            return response()->json([
				'status' => 'success',
				'message' => 'proses perubahan MP dari NPK '.$npk.' menjadi NPK '.$npk_1.'di SPKL dengan ID SPKL '.$id_spkl.' berhasil'
			]);
	}


		//hotfix-2.2.3, by Merio, 20161003, untuk menampilkan form inputan change mp overtime di planning
	public function change_ot_planning_view($id,$id2)
	{
		$spkl_planning 		= t_spkl_detail::join('m_employees','m_employees.npk','=','t_spkl_details.npk')
											->where('t_spkl_details.id_spkl',$id)
											->where('t_spkl_details.npk',$id2)
											->get();
		$check_clv 			= t_spkl::select('kolektif')
									->where('id_spkl',$id)
									->get();
		foreach ($check_clv as $check_clv) {
			$kolektif = $check_clv->kolektif;
		}
		$check_sub_section 	= m_employee::select('m_employees.sub_section as sub_sections',
										'm_departments.code as code_departments')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->where('m_employees.npk',$id2)
										->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section 		= $check_sub_section->sub_sections;
			$code_department 	= $check_sub_section->code_departments;
		}
		if ($kolektif == '1') {
			$m_employee = m_employee::select('*','m_employees.npk as npk_mp')
									->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->join('m_departments','m_departments.code','=','m_sections.code_department')
									->whereNotIn('m_employees.npk', function($q) use ($id) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','1')
										->where('t_spkl_details.is_clv','=','1')
										->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('m_departments.code',$code_department)
									->where('m_employees.status_emp','1')
									->get();
		} else {
			$m_employee = m_employee::select('*','npk as npk_mp')
									->where('sub_section',$sub_section)
									->whereNotIn('npk', function($q) use ($id) {
									$q->select('t_spkl_details.npk')
										->from('t_spkl_details')
										->where('t_spkl_details.is_closed','=','1')
										->where('t_spkl_details.is_clv','=','0')
										->where('t_spkl_details.id_spkl','=',$id);
									})
									->where('status_emp','1')
									->get();
		}
		$employee_before = m_employee::where('npk',$id2)->get();

        $data = [
			'spkl_planning' => $spkl_planning,
			'check_clv' => $check_clv,
			'check_sub_section' => $check_sub_section,
			'm_employee' => $m_employee,
			'employee_before' => $employee_before
        ];
		return response()->json($data);
	}

	//hotfix-2.2.3, by Merio, 20161003, untuk save change overtime planning
	public function change_ot_planning_save()
	{
		$input 			= request::all();
		$npk 			= $input['npk'];
		$npk_1 			= $input['npk_1'];
		$id_spkl  		= $input['id_spkl'];
		$check_exist 	= t_spkl_detail::where('npk',$npk)
										->where('id_spkl',$id_spkl)
										->get();
		if (count($check_exist)>0) {
			Session::flash('flash_type','alert-error');
	        Session::flash('flash_message','Error, karyawan dengan NPK '.$npk.' sudah dilemburkan di SPKL dengan ID SPKL '.$id_spkl.'');
                return response()->json([
					'status' => 'Error',
					'message' => 'karyawan dengan NPK '.$npk.' sudah dilemburkan di SPKL dengan ID SPKL '.$id_spkl. ' berhasil'
				]);
			}

		$check_quota 	= t_spkl_detail::where('npk',$npk_1)
										->where('id_spkl',$id_spkl)
										->get();
		foreach ($check_quota as $check_quota) {
			$id_spkl_update 	= $check_quota->id;
			$date_ot 			= $check_quota->start_date;
			$quota_ot 			= $check_quota->quota_ot;
			$end_date 			= $check_quota->end_date;
			$start_planning 	= $check_quota->start_planning;
			$end_planning	 	= $check_quota->end_planning;
		}

		$mounth = Carbon::parse($date_ot)->format('n');

		$check_employee_before 	= m_employee::where('npk',$npk_1)->get();
		$check_employee_after 	= m_employee::where('npk',$npk)->get();

		foreach ($check_employee_before as $check_employee_before) {
			$id_employee_before 	= $check_employee_before->id;
			if ($mounth == '1') {
				$quota_used_before 		= $check_employee_before->quota_used_1;
				$quota_used_before1 	= 'quota_used_1';
			} else if ($mounth == '2') {
				$quota_used_before 		= $check_employee_before->quota_used_2;
				$quota_used_before1 	= 'quota_used_2';
			} else if ($mounth == '3') {
				$quota_used_before 		= $check_employee_before->quota_used_3;
				$quota_used_before1 	= 'quota_used_3';
			} else if ($mounth == '4') {
				$quota_used_before 		= $check_employee_before->quota_used_4;
				$quota_used_before1 	= 'quota_used_4';
			} else if ($mounth == '5') {
				$quota_used_before 		= $check_employee_before->quota_used_5;
				$quota_used_before1 	= 'quota_used_5';
			} else if ($mounth == '6') {
				$quota_used_before 		= $check_employee_before->quota_used_6;
				$quota_used_before1 	= 'quota_used_6';
			} else if ($mounth == '7') {
				$quota_used_before 		= $check_employee_before->quota_used_7;
				$quota_used_before1 	= 'quota_used_7';
			} else if ($mounth == '8') {
				$quota_used_before 		= $check_employee_before->quota_used_8;
				$quota_used_before1 	= 'quota_used_8';
			} else if ($mounth == '9') {
				$quota_used_before 		= $check_employee_before->quota_used_9;
				$quota_used_before1 	= 'quota_used_9';
			} else if ($mounth == '10') {
				$quota_used_before  	= $check_employee_before->quota_used_10;
				$quota_used_before1 	= 'quota_used_10';
			} else if ($mounth == '11') {
				$quota_used_before 		= $check_employee_before->quota_used_11;
				$quota_used_before1 	= 'quota_used_11';
			} else if ($mounth == '12') {
				$quota_used_before 		= $check_employee_before->quota_used_12;
				$quota_used_before1 	= 'quota_used_12';
			}
		}
		foreach ($check_employee_after as $check_employee_after) {
			$id_employee_after 	= $check_employee_after->id;
			if ($mounth == '1') {
				$quota_used_after 		= $check_employee_after->quota_used_1;
				$quota_used_after1 		= 'quota_used_1';
				$quota_remain_after 	= $check_employee_after->quota_remain_1;
			} else if ($mounth == '2') {
				$quota_used_after 		= $check_employee_after->quota_used_2;
				$quota_used_after1 		= 'quota_used_2';
				$quota_remain_after 	= $check_employee_after->quota_remain_2;
			} else if ($mounth == '3') {
				$quota_used_after 		= $check_employee_after->quota_used_3;
				$quota_used_after1 		= 'quota_used_3';
				$quota_remain_after 	= $check_employee_after->quota_remain_3;
			} else if ($mounth == '4') {
				$quota_used_after 		= $check_employee_after->quota_used_4;
				$quota_used_after1 		= 'quota_used_4';
				$quota_remain_after 	= $check_employee_after->quota_remain_4;
			} else if ($mounth == '5') {
				$quota_used_after 		= $check_employee_after->quota_used_5;
				$quota_used_after1 		= 'quota_used_5';
				$quota_remain_after 	= $check_employee_after->quota_remain_5;
			} else if ($mounth == '6') {
				$quota_used_after 		= $check_employee_after->quota_used_6;
				$quota_used_after1 		= 'quota_used_6';
				$quota_remain_after 	= $check_employee_after->quota_remain_6;
			} else if ($mounth == '7') {
				$quota_used_after 		= $check_employee_after->quota_used_7;
				$quota_used_after1 		= 'quota_used_7';
				$quota_remain_after 	= $check_employee_after->quota_remain_7;
			} else if ($mounth == '8') {
				$quota_used_after 		= $check_employee_after->quota_used_8;
				$quota_used_after1 		= 'quota_used_8';
				$quota_remain_after 	= $check_employee_after->quota_remain_8;
			} else if ($mounth == '9') {
				$quota_used_after 		= $check_employee_after->quota_used_9;
				$quota_used_after1 		= 'quota_used_9';
				$quota_remain_after 	= $check_employee_after->quota_remain_9;
			} else if ($mounth == '10') {
				$quota_used_after  		= $check_employee_after->quota_used_10;
				$quota_used_after1 		= 'quota_used_10';
				$quota_remain_after 	= $check_employee_after->quota_remain_10;
			} else if ($mounth == '11') {
				$quota_used_after 		= $check_employee_after->quota_used_11;
				$quota_used_after1 		= 'quota_used_11';
				$quota_remain_after 	= $check_employee_after->quota_remain_11;
			} else if ($mounth == '12') {
				$quota_used_after 		= $check_employee_after->quota_used_12;
				$quota_used_after1 		= 'quota_used_12';
				$quota_remain_after 	= $check_employee_after->quota_remain_12;
			}
		}
		// $pengurangan_kuota adalah jumlah quota yang sudah digunakann dan sudah di tambah
		$employee 	= m_employee::where('npk',$npk)->first();
        $division = $employee->hasSubSection->hasSection->hasDepartment->hasDivision;
        $specialLimit = $division->specialLimit; // in minute
        $pengurangan_kuota = $quota_used_after + $quota_ot;
        $limit_holiday = $specialLimit->quota_limit_holiday;
        $limit_weekday = $specialLimit->quota_limit_weekday;
        $cek_approved = t_approved_limit_spesial::where('npk',$npk)->first();

        if ($specialLimit) {

        	$cek_its_holiday 	= m_holiday::where('date_holiday', $date_ot)->first();
        	$cek_approved 		= t_approved_limit_spesial::where('npk',$npk)->first();
        	$date1 				= Carbon::parse($date_ot.' '.$start_planning);
			$date2 				= Carbon::parse($end_date.' '.$end_planning);
			$hasil_selisih	    = $date1->diffInMinutes($date2);

        		if ($cek_its_holiday) {

        			$carbon_tgl_inputan = Carbon::parse($date_ot)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_holiday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumHoliday'))
														->whereIn('start_date', $arr)
														->where('npk', $npk)
														->get();
					//mendapatkan remain holiday
        			$remain_holiday = $limit_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

	            	if (($remain_holiday < 0) && (!$cek_approved)) {
	            		$limit_by_jam = $limit_holiday/60;
						Session::flash('flash_type','alert-danger');
			    		Session::flash('flash_message','Error, Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Holiday GM, silakan hubungi GM untuk membuka akses membuat SPKL');
						return response()->json([
							'status' => 'Error',
							'message' => 'Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Holiday GM, silakan hubungi GM untuk membuka akses membuat SPKL'
						]);
	            	}
	            	elseif (($remain_holiday < 0) && ($cek_approved)) {

	            		$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;

						$remain_holiday = $hrd_holiday - ($get_sum_holiday[0]->sumHoliday + $hasil_selisih);

						if ($remain_holiday < 0) {

							$limit_by_jam = $hrd_holiday/60;
							Session::flash('flash_type','alert-danger');
			    			Session::flash('flash_message','Error, Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Holiday HRD, silakan hubungi HRD untuk membuka akses membuat SPKL');
				        	return response()->json([
								'status' => 'Error',
								'message' => 'Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Holiday HRD, silakan hubungi HRD untuk membuka akses membuat SPKL'
							]);
						}

	            	}
        		}
        		else {

        			$carbon_tgl_inputan = Carbon::parse($date_ot)->format('Y-m');
					$holiday = m_holiday::select('date_holiday')->where('date_holiday', 'like', '%' . $carbon_tgl_inputan . '%')->get();
					$arr = [];

					foreach ($holiday as $hol) {
						array_push($arr, $hol->date_holiday);
					}

					$get_sum_weekday = t_spkl_detail::select(DB::raw('sum(quota_ot_actual) as sumWeekday'))
														->whereNotIn('start_date', $arr)
														->where('start_date', 'like', '%' . $carbon_tgl_inputan . '%')
														->where('npk', $npk)
														->get();
					//mendapatkan remain quota weekday
					$remain_weekday = $limit_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

					if (($remain_weekday < 0) && (!$cek_approved)) {

						$limit_by_jam = $limit_weekday/60;
						Session::flash('flash_type','alert-danger');
			    		Session::flash('flash_message','Error, Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Weekday GM, silakan hubungi GM untuk membuka akses membuat SPKL');
				        return response()->json([
							'status' => 'Error',
							'message' => 'Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Weekday GM, silakan hubungi GM untuk membuka akses membuat SPKL'
						]);
					}
					elseif (($remain_weekday < 0) && ($cek_approved)) {

						$now = Carbon::now()->format('Y-m');
						$hrd = m_spesial_limits::where('npk', "Administrator")->first();
						$hrd_holiday = $hrd->quota_limit_holiday;
						$hrd_weekday = $hrd->quota_limit_weekday;
						$cek_bulan_approve = Carbon::parse($cek_approved->created_at)->format('Y-m');

						$remain_weekday = $hrd_weekday - ($get_sum_weekday[0]->sumWeekday + $hasil_selisih);

						if ($remain_weekday < 0) {

							$limit_by_jam = $hrd_weekday/60;
							Session::flash('flash_type','alert-danger');
			    		Session::flash('flash_message','Error, Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Weekday HRD, silakan hubungi HRD untuk membuka akses membuat SPKL');
						return response()->json([
							'status' => 'Error',
							'message' => 'Quota ' . $employee->nama .' sudah melebihi '. $limit_by_jam.' jam parameter Weekday HRD, silakan hubungi HRD untuk membuka akses membuat SPKL'
						]);
						}
					}
        		}

        	//delete npk ketika sudah ditambah ke tabel approved
			// $delete_approved = t_approved_limit_spesial::where('npk',$employee->npk)->delete();
        }

		//hotfix-2.2.3 Mengganti MP yang lembur
		$quota_check = $quota_used_after + $quota_ot;
		if ($quota_check > $quota_remain_after){
			Session::flash('flash_type','alert-danger');
        	Session::flash('flash_message','Error, quota untuk karyawan dengan NPK '.$check_employee_after->npk.'melebihi batas limit, silakan hubungi Atasan Langsung anda');
			return response()->json([
				'status' => 'Error',
				'message' => 'quota untuk karyawan dengan NPK '.$check_employee_after->npk.'melebihi batas limit, silakan hubungi Atasan Langsung anda'
			]);
		}
		else{
			$quota_temp = $quota_used_before - $quota_ot;

			DB::table('m_employees')
            ->where('id', $id_employee_before)
            ->update([$quota_used_before1 => $quota_temp]);
		}
			$quota_temp2 = $quota_used_after + $quota_ot;
			DB::table('m_employees')
            ->where('id', $id_employee_after)
            ->update([$quota_used_after1 => $quota_temp2]);

		$update_spkl 				= t_spkl_detail::findOrFail($id_spkl_update);
		$update_spkl->npk 			= $npk;
		//hotfix-2.2.6, by Merio, 20161020, menambahkan informasi npk sebelumnya sebelum di update
		$update_spkl->npk_before	= $npk_1;
		$update_spkl->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, proses perubahan MP dari NPK '.$npk.' menjadi NPK '.$npk_1.'di SPKL dengan ID SPKL '.$id_spkl.' berhasil');
            return response()->json([
				'status' => 'success',
				'message' => 'proses perubahan MP dari NPK '.$npk.' menjadi NPK '.$npk_1.'di SPKL dengan ID SPKL '.$id_spkl.' berhasil'
			]);
	}
	//hotfix-2.3.2, by Merio, 20161004, menambahkan fungsi untuk update limit open access overtime late oleh HRD
	public function open_access_ot_late() {
		$m_employee = m_open_access::select('*', 'm_open_accesses.id as id_open_access')
									->join('m_employees','m_employees.npk','=','m_open_accesses.npk_user')
									->leftjoin('m_departments','m_departments.npk','=','m_employees.npk')
									->where('m_open_accesses.is_active','1')
									->get();
                                    return response()->json($m_employee);
	}

	public function open_access_ot_late_update($id) {
		$m_employee = m_open_access::select('*','m_open_accesses.id as id_open_access')
									->join('m_employees','m_employees.npk','=','m_open_accesses.npk_user')
									->leftjoin('m_departments','m_departments.npk','=','m_employees.npk')
									->where('m_open_accesses.is_active','1')
									->where('m_open_accesses.id',$id)
									->get();
                                    return response()->json($m_employee);
    }

	public function open_access_ot_late_save() {
		$user 			= Auth::user();
		$input 			= request::all();
		$id_open_access = $input['id'];
		$limit 			= $input['limit'];
		$update_open_access = m_open_access::findOrFail($id_open_access);
		$update_open_access->limit = $limit;
		$update_open_access->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, proses update limit open access overtime late berhasil');
		return response()->json([
			'status' => 'success',
			'message' => 'proses update limit openaccess oovertime late berhasil'
		]);
	}
}
