<?php

namespace App\Http\Controllers\Api;

use App\Models\m_quota_used;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MQuotaUsedResource;

class MQuotaUsedController extends Controller{

    public function index()
    {
        //get all quota used
        $m_quota_useds = m_quota_used::latest()->paginate(5);

        //return collection of quota used as a resource
        return new MQuotaUsedResource(true, 'List Data Quota Used', $m_quota_useds);
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

        //create quota used
        $m_quota_used = m_quota_used::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MQuotaUsedResource(true, 'Data Quota Used Berhasil Ditambahkan!', $m_quota_used);
    }

    public function show($id)
    {
        $m_quota_used = m_quota_used::find($id);   //find quota used by ID

        return new MQuotaUsedResource(true, 'Detail Data Quota Used!', $m_quota_used);   //return single quota used as a resource
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

        //find quota used by ID
            $m_quota_used = m_quota_used::find($id);
            $m_quota_used->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MQuotaUsedResource(true, 'Data Quota Used Berhasil Diubah!', $m_quota_used); //return response
    }

    public function destroy($id)
    {
        $m_quota_used = m_quota_used::find($id); //find quota used by ID
        $m_quota_used->delete();  //delete quota used

        //return response
        return new MQuotaUsedResource(true, 'Data Quota Used Berhasil Dihapus!', null);
    }
}
