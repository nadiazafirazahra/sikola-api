<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Client\Request;
use App\Http\Controllers\Controller;


class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // app/Http/Controllers/Api/EmployeeController.php

public function index(Request $request)
{
    $m = $request->input('m');
    $employees = 'm_employee'::select('m_employees.npk as npk_mp', 'm_employees.nama',
        'm_employees.quota_used_'.$m.' as quota_used',
        'm_employees.quota_remain_'.$m.' as quota_remain',
        'm_sub_sections.name as sub_section_name',
        'm_sections.name as section_name',
        'm_departments.name as department_name')
        ->join('m_sub_sections', 'm_sub_sections.code', '=', 'm_employees.sub_section')
        ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section')
        ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department')
        ->join('m_divisions', 'm_divisions.code', '=', 'm_departments.code_division')
        ->where(function ($q) {
            $q->where('m_employees.occupation', 'OPR')
                ->orWhere('m_employees.occupation', 'LDR');
        })
        ->where('m_employees.status_emp', 1)
        ->orderBy('m_employees.npk')
        ->get();

    return response()->json($employees);
}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
{
    $m = $request->input('m');
    $validatedData = $request->validate([
        'nama' => 'required|string|max:255',
        'quota_used_'.$m. 'required|integer',
        'quota_remain_'.$m. 'required|integer',
        'sub_section' => 'required|integer',
        'occupation' => 'required|in:OPR,LDR',
        'status_emp' => 'required|boolean',
    ]);
    return response()->json($validatedData);
}
    /**
     * Display the specified resource.
     */
    public function show (Request $request)
    {
        $m = $request->input('m');
        $employee = 'm_employee'::select('m_employees.npk as npk_mp', 'm_employees.nama',
            'm_employees.quota_used_'.$m.' as quota_used',
            'm_employees.quota_remain_'.$m.' as quota_remain',
            'm_sub_sections.name as sub_section_name',
            'm_sections.name as section_name',
            'm_departments.name as department_name')
            ->join('m_sub_sections', 'm_sub_sections.code', '=', 'm_employees.sub_section')
            ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section')
            ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department')
            ->join('m_divisions', 'm_divisions.code', '=', 'm_departments.code_division')
            ->where(function ($q) {
                $q->where('m_employees.occupation', 'OPR')
                    ->orWhere('m_employees.occupation', 'LDR');
            })
            ->where('m_employees.status_emp', 1)
            ->findOrFail();

        return response()->json($employee);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
{
    $m = $request->input('m');
    $employee = 'm_employee'::findOrFail($id);

    $validatedData = $request->validate([
        'nama' => 'required|string|max:255',
        'quota_used_'.$m => 'required|integer',
        'quota_remain_'.$m => 'required|integer',
        'sub_section' => 'required|integer',
        'occupation' => 'required|in:OPR,LDR',
        'status_emp' => 'required|boolean',
    ]);

    $employee->update($validatedData);

    return response()->json($employee);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
{
    $employee = 'm_employee'::findOrFail($id);
    $employee->delete();

    return response()->json([
        'message' => 'Employee deleted successfully'
    ]);
}
}
