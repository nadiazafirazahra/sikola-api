<?php

namespace App\Http\Controllers\Api;

use App\Models\m_quota_real;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MQuotaRealResource;

class MQuotaRealController extends Controller{

    public function index()
    {
        //get all quota real
        $m_quota_reals = m_quota_real::latest()->paginate(5);

        //return collection of quota real as a resource
        return new MQuotaRealResource(true, 'List Data Quota Real', $m_quota_reals);
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

        //create quota real
        $m_quota_real = m_quota_real::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MQuotaRealResource(true, 'Data Quota Real Berhasil Ditambahkan!', $m_quota_real);
    }

    public function show($id)
    {
        $m_quota_real = m_quota_real::find($id);   //find quota real by ID

        return new MQuotaRealResource(true, 'Detail Data Quota Real!', $m_quota_real);   //return single quota real as a resource
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

        //find quota real by ID
            $m_quota_real = m_quota_real::find($id);
            $m_quota_real->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MQuotaRealResource(true, 'Data Quota Real Berhasil Diubah!', $m_quota_real); //return response
    }

    public function destroy($id)
    {
        $m_quota_real = m_quota_real::find($id); //find quota real by ID
        $m_quota_real->delete();  //delete quota real

        //return response
        return new MQuotaRealResource(true, 'Data Quota Real Berhasil Dihapus!', null);
    }
}
