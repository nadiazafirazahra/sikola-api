<?php

namespace App\Http\Controllers\Api;

use App\Models\m_quota_add;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MQuotaAddResource;

class MQuotaAddController extends Controller{

    public function index()
    {
        //get all quota add
        $m_quota_adds = m_quota_add::latest()->paginate(5);

        //return collection of quota add as a resource
        return new MQuotaAddResource(true, 'List Data Quota Add', $m_quota_adds);
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

        //create quota add
        $m_quota_add = m_quota_add::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MQuotaAddResource(true, 'Data Quota Add Berhasil Ditambahkan!', $m_quota_add);
    }

    public function show($id)
    {
        $m_quota_add = m_quota_add::find($id);   //find quota add by ID

        return new MQuotaAddResource(true, 'Detail Data Quota Add!', $m_quota_add);   //return single quota add as a resource
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

        //find quota add by ID
            $m_quota_add = m_quota_add::find($id);
            $m_quota_add->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MQuotaAddResource(true, 'Data Quota Add Berhasil Diubah!', $m_quota_add); //return response
    }

    public function destroy($id)
    {
        $m_quota_add = m_quota_add::find($id); //find quota add by ID
        $m_quota_add->delete();  //delete quota add

        //return response
        return new MQuotaAddResource(true, 'Data Quota Add Berhasil Dihapus!', null);
    }
}
