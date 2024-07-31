<?php

namespace App\Http\Controllers\Api;

use App\Models\m_department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MDepartmentResource;

class MDepartmentController extends Controller{

    public function index()
    {
        //get all department
        $m_departments = m_department::latest()->paginate(5);

        //return collection of department as a resource
        return new MDepartmentResource(true, 'List Data Department', $m_departments);
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

        //create department
        $m_department = m_department::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MDepartmentResource(true, 'Data Department Berhasil Ditambahkan!', $m_department);
    }

    public function show($id)
    {
        $m_department = m_department::find($id);   //find department by ID

        return new MDepartmentResource(true, 'Detail Data Department!', $m_department);   //return single department as a resource
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

        //find department by ID
            $m_department = m_department::find($id);
            $m_department->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MDepartmentResource(true, 'Data Department Berhasil Diubah!', $m_department); //return response
    }

    public function destroy($id)
    {
        $m_department = m_department::find($id); //find department by ID
        $m_department->delete();  //delete department

        //return response
        return new MDepartmentResource(true, 'Data Department Berhasil Dihapus!', null);
    }
}
