<?php

namespace App\Http\Controllers\Api;

use App\Models\t_approved_limit_spesial;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TApprovedLimitSpesialResource;

class TApprovedLimitSpesialController extends Controller{

    public function index()
    {
        //get all approved limit spesial
        $t_approved_limit_spesials = t_approved_limit_spesial::latest()->paginate(20);

        //return collection of approved limit spesial as a resource
        return new TApprovedLimitSpesialResource(true, 'List Data Approved Limit Spesial', $t_approved_limit_spesials);
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

        //create approved limit spesial
        $t_approved_limit_spesial = t_approved_limit_spesial::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new TApprovedLimitSpesialResource(true, 'Data Approved Limit Spesial Berhasil Ditambahkan!', $t_approved_limit_spesial);
    }

    public function show($id)
    {
        $t_approved_limit_spesial = t_approved_limit_spesial::find($id);   //find approved limit spesial by ID

        return new TApprovedLimitSpesialResource(true, 'Detail Data Approved Limit Spesial!', $t_approved_limit_spesial);   //return single approved limit spesial as a resource
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

        //find approved limit spesial by ID
            $t_approved_limit_spesial = t_approved_limit_spesial::find($id);
            $t_approved_limit_spesial->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TApprovedLimitSpesialResource(true, 'Data Approved Limit Spesial Berhasil Diubah!', $t_approved_limit_spesial); //return response
    }

    public function destroy($id)
    {
        $t_approved_limit_spesial = t_approved_limit_spesial::find($id); //find approved limit spesial by ID
        $t_approved_limit_spesial->delete();  //delete approved limit spesial

        //return response
        return new TApprovedLimitSpesialResource(true, 'Data Approved Limit Spesial Berhasil Dihapus!', null);
    }
}
