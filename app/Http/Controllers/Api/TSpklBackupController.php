<?php

namespace App\Http\Controllers\Api;

use App\Models\t_spkl_backup;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TSpklBackupResource;

class TSpklBackupController extends Controller{

    public function index()
    {
        //get all spkl backup
        $t_spkl_backups = t_spkl_backup::latest()->paginate(20);

        //return collection of  spkl backup as a resource
        return new TSpklBackupResource(true, 'List Data SPKL Backup', $t_spkl_backups);
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
        $t_spkl_backup = t_spkl_backup::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new TSpklBackupResource(true, 'Data SPKL Backup Berhasil Ditambahkan!', $t_spkl_backup);
    }

    public function show($id)
    {
        $t_spkl_backup = t_spkl_backup::find($id);   //find spkl backup by ID

        return new TSpklBackupResource(true, 'Detail Data SPKL Backup!', $t_spkl_backup);   //return single spkl backup as a resource
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

        //find spkl backup by ID
            $t_spkl_backup = t_spkl_backup::find($id);
            $t_spkl_backup->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TSpklBackupResource(true, 'Data SPKL Backup Berhasil Diubah!', $t_spkl_backup); //return response
    }

    public function destroy($id)
    {
        $t_spkl_backup = t_spkl_backup::find($id); //find spkl backup by ID
        $t_spkl_backup->delete();  //delete spkl backup

        //return response
        return new TSpklBackupResource(true, 'Data SPKL Backup Berhasil Dihapus!', null);
    }
}
