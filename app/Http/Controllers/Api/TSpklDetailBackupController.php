<?php

namespace App\Http\Controllers\Api;

use App\Models\t_spkl_detail_backup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TSpklDetailBackupResource;

class TSpklDetailBackupController extends Controller{

    public function index()
    {
        //get all spkl detail backup
        $t_spkl_detail_backups = t_spkl_detail_backup::latest()->paginate(20);

        //return collection of  spkl detail backup as a resource
        return new TSpklDetailBackupResource(true, 'List Data SPKL Detail Backup', $t_spkl_detail_backups);
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

        //create
        $t_spkl_detail_backup = t_spkl_detail_backup::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new TSpklDetailBackupResource(true, 'Data SPKL Detail Backup Berhasil Ditambahkan!', $t_spkl_detail_backup);
    }

    public function show($id)
    {
        $t_spkl_detail_backup = t_spkl_detail_backup::find($id);   //find spkl detail backup by ID

        return new TSpklDetailBackupResource(true, 'Detail Data SPKL Detail Backup!', $t_spkl_detail_backup);   //return single spkl detail backup as a resource
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

        //find spkl detail backup by ID
            $t_spkl_detail_backup = t_spkl_detail_backup::find($id);
            $t_spkl_detail_backup->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TSpklDetailBackupResource(true, 'Data SPKL Detail Backup Berhasil Diubah!', $t_spkl_detail_backup); //return response
    }

    public function destroy($id)
    {
        $t_spkl_detail_backup = t_spkl_detail_backup::find($id); //find spkl detail backup by ID
        $t_spkl_detail_backup->delete();  //delete spkl detail backup

        //return response
        return new TSpklDetailBackupResource(true, 'Data SPKL Detail Backup Berhasil Dihapus!', null);
    }
}
