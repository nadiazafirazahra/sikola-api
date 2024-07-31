<?php

namespace App\Http\Controllers\Api;

use App\Models\t_approved_limit_spesial_log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TApprovedLimitSpesialLogResource;

class TApprovedLimitSpesialLogController extends Controller{

    public function index()
    {
        //get all approved limit spesial log
        $t_approved_limit_spesial_logs = t_approved_limit_spesial_log::latest()->paginate(20);

        //return collection of approved limit spesial log as a resource
        return new TApprovedLimitSpesialLogResource(true, 'List Data Approved Limit Spesial Log', $t_approved_limit_spesial_logs);
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

        //create approved limit spesial log
        $t_approved_limit_spesial_log = t_approved_limit_spesial_log::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new TApprovedLimitSpesialLogResource(true, 'Data Approved Limit Spesial Log Berhasil Ditambahkan!', $t_approved_limit_spesial_log);
    }

    public function show($id)
    {
        $t_approved_limit_spesial_log = t_approved_limit_spesial_log::find($id);   //find approved limit spesial log by ID

        return new TApprovedLimitSpesialLogResource(true, 'Detail Data Approved Limit Spesial Log!', $t_approved_limit_spesial_log);   //return single approved limit spesial log as a resource
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

        //find approved limit spesial log by ID
            $t_approved_limit_spesial_log = t_approved_limit_spesial_log::find($id);
            $t_approved_limit_spesial_log->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TApprovedLimitSpesialLogResource(true, 'Data Approved Limit Spesial Log Berhasil Diubah!', $t_approved_limit_spesial_log); //return response
    }

    public function destroy($id)
    {
        $t_approved_limit_spesial_log = t_approved_limit_spesial_log::find($id); //find approved limit spesial log by ID
        $t_approved_limit_spesial_log->delete();  //delete approved limit spesial log

        //return response
        return new TApprovedLimitSpesialLogResource(true, 'Data Approved Limit Spesial Log Berhasil Dihapus!', null);
    }
}
