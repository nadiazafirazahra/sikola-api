<?php

namespace App\Http\Controllers\Api;

use App\Models\m_employee;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MEmployeeResource;

class MEmployeeController extends Controller{

    public function index()
    {
        //get all employee
        $m_employees = m_employee::latest()->paginate(5);

        //return collection of division as a resource
        return new MEmployeeResource(true, 'List Data Employee', $m_employees);
    }

    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'content'   => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //create employee
        $m_employee = m_employee::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MEmployeeResource(true, 'Data Employee Berhasil Ditambahkan!', $m_employee);
    }

    public function show($id)
    {
        $m_employee = m_employee::find($id);   //find employee by ID

        return new MEmployeeResource(true, 'Detail Data Employee!', $m_employee);   //return single employee as a resource
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [  //define validation rules
            'title'     => 'required',
            'content'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);  //check if validation fails
        }

        //find employee by ID
            $m_employee = m_employee::find($id);
            $m_employee->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MEmployeeResource(true, 'Data Employee Berhasil Diubah!', $m_employee); //return response
    }

    public function destroy($id)
    {
        $m_employee = m_employee::find($id); //find employee by ID
        $m_employee->delete();  //delete employee

        //return response
        return new MEmployeeResource(true, 'Data Employee Berhasil Dihapus!', null);
    }
}
