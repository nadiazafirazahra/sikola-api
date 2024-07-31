<?php

namespace App\Http\Controllers\Api;

use App\Models\quota_add_itd;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\QuotaAddItdResource;

class QuotaAddItdController extends Controller{

    public function index()
    {
        //get all quota add itd
        $quota_add_itds = quota_add_itd::latest()->paginate(20);

        //return collection of quota add itd as a resource
        return new QuotaAddItdResource(true, 'List Data Quota Add Itd', $quota_add_itds);
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

        //create quota add itd
        $quota_add_itd = quota_add_itd::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new QuotaAddItdResource(true, 'Data Quota Add Itd Berhasil Ditambahkan!', $quota_add_itd);
    }

    public function show($id)
    {
        $quota_add_itd = quota_add_itd::find($id);   //find quota add itd by ID

        return new QuotaAddItdResource(true, 'Detail Data Quota Add Itd!', $quota_add_itd);   //return single quota add itd as a resource
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

        //find quota add itd by ID
            $quota_add_itd = quota_add_itd::find($id);
            $quota_add_itd->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new QuotaAddItdResource(true, 'Data Quota Add Itd Berhasil Diubah!', $quota_add_itd); //return response
    }

    public function destroy($id)
    {
        $quota_add_itd = quota_add_itd::find($id); //find quota add itd by ID
        $quota_add_itd->delete();  //delete quota add itd

        //return response
        return new QuotaAddItdResource(true, 'Data Quota Add Itd Berhasil Dihapus!', null);
    }
}
