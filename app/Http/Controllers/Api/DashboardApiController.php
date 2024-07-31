<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\m_employee;
use App\Http\Requests;
// dev-3.2.0, Ferry, 20170612, use models here
use App\Models\m_quota_department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Autsh;

class DashboardApiController extends Controller {

	// dev-3.2.0, Ferry, 20170612, Koleksi dari generate dashboard view dan fungsi-fungsinya

	/**
	 * Create a new controller instance. Authentication required
	 *
	 * @return void
	 */

	public function getShow($id, $n_month = '')
	{
		// for gm dashboard - 1
		if ($id == 'gm1') {

			$arr_view = $this->getAjaxGm1($n_month);
			return response()->json('dashboard.gm1', $arr_view);
		}
		// for gm dashboard - 1
		elseif ($id == 'mgr1') {

			$arr_view = $this->getAjaxMgr1($n_month);
			return response()->json('dashboard.mgr1', $arr_view);
		}
		else {
            return response()->json('home');
		}
	}

	//hotfix-3.2.1, by yudo, 20170704, download excel
	public function getDownload($id, $n_month = '')
	{
		$c_refdate = Carbon::create(null, $n_month, 1);
		$rows = 9;

		// for gm dashboard - 1
		if ($id == 'gm1') {

			$arr_view = $this->getAjaxGm1($n_month);


			$title = 'Overtime Report Division '.$arr_view['a_user']->hasEmployee->hasDivision->name.' ( '. $c_refdate->formatLocalized('%b %Y').' ) ';
			$rows = 9;
			$data = $arr_view['q_gm2'];


		}
		// for gm dashboard - 1
		elseif ($id == 'mgr1') {

			$arr_view = $this->getAjaxMgr1($n_month);
			$title = 'Overtime Report Department '.$arr_view['a_user']->hasEmployee->hasDepartment->name .' ( '. $c_refdate->formatLocalized('%b %Y').' ) ';
			$data = $arr_view['q_mgr2'];

		}
	}
	// end of yudo

	///////////////////////////////////// COLLECTION OF DASHBOARD AJAX ///////////////////////////////////////

	public function getAjaxMgr1($n_month = '') {

		// get user division code
		$a_user   = Auth::user();
		$arr_result['a_user'] = $a_user;

		// get time / month reference
		$c_refdate = trim($n_month) == '' ? Carbon::now() : Carbon::create(null, $n_month, 1);
		$arr_result['c_refdate'] = $c_refdate;

		// get quota_plan and quota_used from department
		$arr_result['q_mgr1'] =
			m_employee::selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan')
						->selectRaw('sum(quota_used_'.$c_refdate->month.') as q_used')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->where('m_sections.code_department', $a_user->hasEmployee->hasDepartment->code)
		                ->where('m_employees.status_emp', '1')
						->first();

		// get quota_plan (budget) from HRD
		$arr_result['q_hr_budget'] = m_quota_department::selectRaw('sum(quota_plan) as total')
						->where('m_quota_departments.code_department', $a_user->hasEmployee->hasDepartment->code)
		                ->where('m_quota_departments.month', $c_refdate->month)
						->first();

		// get total member who can overtime
		$arr_result['n_member'] = m_employee::selectRaw('count(m_employees.npk) as total')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->where('m_sections.code_department', $a_user->hasEmployee->hasDepartment->code)
		                ->where('m_employees.status_emp', '1')
						->first();

		// get quota shares (in percentage) vs HR Budget
		$arr_result['q_mgr2'] =
			m_employee::selectRaw('m_sections.code, m_sections.alias, m_quota_departments.quota_plan as q_hr')
						->selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan, sum(quota_used_'.$c_refdate->month.') as q_used')
						->selectRaw('abs((quota_plan - sum(quota_used_'.$c_refdate->month.')) / quota_plan)  * 100 as q_share')
						->selectRaw('round((sum(quota_used_'.$c_refdate->month.') / quota_plan)  * 100, 2) as q_share_rounded')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->join('m_quota_departments','m_departments.code','=','m_quota_departments.code_department')
						->where('m_sections.code_department', $a_user->hasEmployee->hasDepartment->code)
		                ->where('m_employees.status_emp', '1')
		                ->where('m_quota_departments.month', $c_refdate->month)
		                ->groupBy('m_sections.code')
		                ->orderBy('q_share', 'desc')
						->get();

		$arr_result['q_mgr2_best'] = $arr_result['q_mgr2']->first();

		// get total of quota plan and used from manager budget
		$arr_result['q_mgr2_plan'] =  0;
		$arr_result['q_mgr2_used'] =  0;
		$mgr_budgets = $arr_result['q_mgr2'];

		foreach ($mgr_budgets as $mgr_budget) {
			$arr_result['q_mgr2_plan'] += $mgr_budget->q_plan;
			$arr_result['q_mgr2_used'] += $mgr_budget->q_used;
		}

		// get quota shares (in percentage) vs Department Budget
		$arr_result['q_mgr3_best'] =
			m_employee::selectRaw('m_sections.code, m_sections.alias')
						->selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan, sum(quota_used_'.$c_refdate->month.') as q_used')
						->selectRaw('abs((sum(quota_remain_'.$c_refdate->month.') - sum(quota_used_'.$c_refdate->month.')) / sum(quota_remain_'.$c_refdate->month.'))  * 100 as q_share')
						->selectRaw('round((sum(quota_used_'.$c_refdate->month.') / sum(quota_remain_'.$c_refdate->month.'))  * 100, 2) as q_share_rounded')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('m_sections.code_department', $a_user->hasEmployee->hasDepartment->code)
		                ->where('m_employees.status_emp', '1')
		                ->groupBy('m_sections.code')
		                ->orderBy('q_share', 'desc')
						->first();

		// get quota_plan (budget) from HRD in percentage
		$arr_result['q_hr_share'] = ($arr_result['q_hr_budget']->total <= 0) ? 0 :
			round($arr_result['q_mgr2_used'] * 100 / $arr_result['q_hr_budget']->total, 2);

		// get quota_plan (budget) from MGR in percentage
		$arr_result['q_mgr_share'] = ($arr_result['q_mgr2_plan'] <= 0) ? 0 :
			round($arr_result['q_mgr2_used'] * 100 / $arr_result['q_mgr2_plan'], 2);

		  return response()->json ($arr_result);
	}

	public function getAjaxMgr1OnlyChart($n_year = '') {

		// get user division code
		$a_user   = Auth::user();

		// get time / month reference
		$c_refdate = trim($n_year) == '' ? Carbon::now() : Carbon::create($n_year, 1, 1);

		// get array value of quota from hr
		// first get all active departments from hr
		$e_sections = m_quota_department::select('m_sections.code')
					->join('m_sections','m_sections.code_department','=','m_quota_departments.code_department')
					->where('m_sections.code_department', $a_user->hasEmployee->hasDepartment->code)
					->distinct()
					->get();

		$arr_result['q_hr'] = array();
		$arr_result['q_used'] = array();
		$n_section = 1;	// khusus manager hanya ada 1 budget HR tidak perlu berulang section

		foreach ($e_sections as $e_section) {

			$values_hr = array();
			$values_used = array();

			for ($i = 4; $i <= 15; $i++) {
				// query quota hr
				// khusus manager hanya ada 1 budget HR tidak perlu berulang section
				if ($n_section == 1) {
					$e_qhr =
						m_quota_department::where('m_sections.code', $e_section->code)
											->join('m_sections','m_sections.code_department','=','m_quota_departments.code_department')
											->where('month', ($i > 12 ? $i-12 : $i))
											->sum('quota_plan');

					array_push($values_hr, round($e_qhr / 60, 1));
				}

				// query quota used
				$e_quotas =
					m_employee::join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->where('m_employees.status_emp', '1')
			                ->where('m_sections.code', $e_section->code)
							->sum('quota_used_'.($i > 12 ? $i-12 : $i));

				array_push($values_used, round($e_quotas / 60, 1));
			}

			if ($n_section == 1) {
				$arr_result['q_hr'] =
					array(
						"h_title_spline" => "HR Budget",
						"h_value_spline" => $values_hr,
					);
			}

			array_push($arr_result['q_used'],
				array(
					"h_title_bar" => $e_section->code." Used",
					"h_value_bar" => $values_used,
				)
			);

			$n_section++;
		}

	  return response()->json ($arr_result);
	}

	public function getAjaxGm1($n_month = '') {

		// get user division code
		$a_user   = Auth::user();
		$arr_result['a_user'] = $a_user;

		// get time / month reference
		$c_refdate = trim($n_month) == '' ? Carbon::now() : Carbon::create(null, $n_month, 1);
		$arr_result['c_refdate'] = $c_refdate;

		// get quota_plan and quota_used from gm
		$arr_result['q_gm1'] =
			m_employee::selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan')
						->selectRaw('sum(quota_used_'.$c_refdate->month.') as q_used')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
		                ->where('m_employees.status_emp', '1')
						->first();

		// get quota_plan (budget) from HRD
		$arr_result['q_hr_budget'] = m_quota_department::selectRaw('sum(quota_plan) as total')
						->join('m_departments','m_departments.code','=','m_quota_departments.code_department')
						->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
		                ->where('m_quota_departments.month', $c_refdate->month)
						->first();

		// get total member who can overtime
		$arr_result['n_member'] = m_employee::selectRaw('count(m_employees.npk) as total')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
		                ->where('m_employees.status_emp', '1')
						->first();

		// get quota shares (in percentage) vs HR Budget
		$arr_result['q_gm2'] =
			m_employee::selectRaw('m_departments.code, m_departments.alias, m_quota_departments.quota_plan as q_hr')
						->selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan, sum(quota_used_'.$c_refdate->month.') as q_used')
						->selectRaw('abs((quota_plan - sum(quota_used_'.$c_refdate->month.')) / quota_plan)  * 100 as q_share')
						->selectRaw('round((sum(quota_used_'.$c_refdate->month.') / quota_plan)  * 100, 2) as q_share_rounded')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->join('m_quota_departments','m_departments.code','=','m_quota_departments.code_department')
						->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
		                ->where('m_employees.status_emp', '1')
		                ->where('m_quota_departments.month', $c_refdate->month)
		                ->groupBy('m_departments.code')
		                ->orderBy('q_share', 'desc')
						->get();

		$arr_result['q_gm2_best'] = $arr_result['q_gm2']->first();

		// get total of quota plan and used from gm budget
		$arr_result['q_gm2_plan'] =  0;
		$arr_result['q_gm2_used'] =  0;
		$gm_budgets = $arr_result['q_gm2'];

		foreach ($gm_budgets as $gm_budget) {
			$arr_result['q_gm2_plan'] += $gm_budget->q_plan;
			$arr_result['q_gm2_used'] += $gm_budget->q_used;
		}

		// get quota shares (in percentage) vs Division Budget
		$arr_result['q_gm3_best'] =
			m_employee::selectRaw('m_departments.code, m_departments.alias')
						->selectRaw('sum(quota_remain_'.$c_refdate->month.') as q_plan, sum(quota_used_'.$c_refdate->month.') as q_used')
						->selectRaw('abs((sum(quota_remain_'.$c_refdate->month.') - sum(quota_used_'.$c_refdate->month.')) / sum(quota_remain_'.$c_refdate->month.'))  * 100 as q_share')
						->selectRaw('round((sum(quota_used_'.$c_refdate->month.') / sum(quota_remain_'.$c_refdate->month.'))  * 100, 2) as q_share_rounded')
						->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')
						->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
		                ->where('m_employees.status_emp', '1')
		                ->groupBy('m_departments.code')
		                ->orderBy('q_share', 'desc')
						->first();

		// get quota_plan (budget) from HRD in percentage
		$arr_result['q_hr_share'] =
			round(($arr_result['q_hr_budget']->total - $arr_result['q_gm2_used']) * 100 / $arr_result['q_hr_budget']->total, 2);

		// get quota_plan (budget) from GM in percentage
		$arr_result['q_gm_share'] =
			round(($arr_result['q_gm2_plan'] - $arr_result['q_gm2_used']) * 100 / $arr_result['q_gm2_plan'], 2);

	  return response()->json ($arr_result);
	}

	public function getAjaxGm1OnlyChart($n_year = '') {

		// get user division code
		$a_user   = Auth::user();

		// get time / month reference
		$c_refdate = trim($n_year) == '' ? Carbon::now() : Carbon::create($n_year, 1, 1);

		// get array value of quota from hr
		// first get all active departments from hr
		$e_depts = m_quota_department::select('code_department')
					->join('m_departments','m_departments.code','=','m_quota_departments.code_department')
					->where('m_departments.code_division', $a_user->hasEmployee->hasDivision->code)
					->distinct()
					->get();

		$arr_result['q_hr'] = array();
		$arr_result['q_used'] = array();

		foreach ($e_depts as $e_dept) {

			$values_hr = array();
			$values_used = array();

			for ($i = 4; $i <= 15; $i++) {
				// query quota hr
				$e_qhr =
					m_quota_department::where('code_department', $e_dept->code_department)
										->where('month', ($i > 12 ? $i-12 : $i))
										->sum('quota_plan');

				array_push($values_hr, round($e_qhr / 60, 1));

				// query quota used
				$e_quotas =
					m_employee::join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->join('m_departments','m_departments.code','=','m_sections.code_department')
			                ->where('m_employees.status_emp', '1')
			                ->where('m_departments.code', $e_dept->code_department)
							->sum('quota_used_'.($i > 12 ? $i-12 : $i));

				array_push($values_used, round($e_quotas / 60, 1));
			}

			array_push($arr_result['q_hr'],
				array(
					"h_title_spline" => $e_dept->code_department." Budget",
					"h_value_spline" => $values_hr,
				)
			);

			array_push($arr_result['q_used'],
				array(
					"h_title_bar" => $e_dept->code_department." Used",
					"h_value_bar" => $values_used,
				)
			);
		}

	  return response()->json ($arr_result);
	}
}
