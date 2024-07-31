<?php

namespace App\Http\Controllers\Api;

use App\Models\m_quota_request;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MQuotaRequestResource;

class MQuotaRequestController extends Controller{

    public function index()
    {
        //get all quota request
        $m_quota_requests = m_quota_request::latest()->paginate(5);

        //return collection of quota request as a resource
        return new MQuotaRequestResource(true, 'List Data Quota Request', $m_quota_requests);
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

        //create quota request
        $m_quota_request = m_quota_request::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MQuotaRequestResource(true, 'Data Quota Request Berhasil Ditambahkan!', $m_quota_request);
    }

    public function show($id)
    {
        $m_quota_request = m_quota_request::find($id);   //find quota request by ID

        return new MQuotaRequestResource(true, 'Detail Data Quota Request!', $m_quota_request);   //return single quota request as a resource
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

        //find quota request by ID
            $m_quota_request = m_quota_request::find($id);
            $m_quota_request->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MQuotaRequestResource(true, 'Data Quota Request Berhasil Diubah!', $m_quota_request); //return response
    }

    public function destroy($id)
    {
        $m_quota_request = m_quota_request::find($id); //find quota request by ID
        $m_quota_request->delete();  //delete quota request

        //return response
        return new MQuotaRequestResource(true, 'Data Quota Request Berhasil Dihapus!', null);
    }
}
