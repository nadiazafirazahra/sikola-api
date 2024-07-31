<?php

namespace App\Http\Controllers\Api;

use App\Models\m_quota_department;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MQuotaDepartmentResource;

class MQuotaDepartmentController extends Controller{

    public function index()
    {
        //get all quota department
        $m_quota_departments = m_quota_department::latest()->paginate(5);

        //return collection of quota department as a resource
        return new MQuotaDepartmentResource(true, 'List Data Quota Department', $m_quota_departments);
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

        //create quota department
        $m_quota_department = m_quota_department::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MQuotaDepartmentResource(true, 'Data Quota Department Berhasil Ditambahkan!', $m_quota_department);
    }

    public function show($id)
    {
        $m_quota_department = m_quota_department::find($id);   //find quota department by ID

        return new MQuotaDepartmentResource(true, 'Data Detail Data Quota Department!', $m_quota_department);   //return single quota department as a resource
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

        //find quota department by ID
            $m_quota_department = m_quota_department::find($id);
            $m_quota_department->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MQuotaDepartmentResource(true, 'Quota Department Berhasil Diubah!', $m_quota_department); //return response
    }

    public function destroy($id)
    {
        $m_quota_department = m_quota_department::find($id); //find quota department by ID
        $m_quota_department->delete();  //delete quota department

        //return response
        return new MQuotaDepartmentResource(true, 'Data Quota Department Berhasil Dihapus!', null);
    }
}
