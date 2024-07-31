<?php

namespace App\Http\Controllers\Api;

use App\Models\m_open_access;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MOpenAccessResource;

class MOpenAccessController extends Controller{

    public function index()
    {
        //get all open access
        $m_open_accesses = m_open_access::latest()->paginate(5);

        //return collection of open access as a resource
        return new MOpenAccessResource(true, 'List Data Open Access', $m_open_accesses);
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

        //create open access
        $m_open_access = m_open_access::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MOpenAccessResource(true, 'Data Open Access Berhasil Ditambahkan!', $m_open_access);
    }

    public function show($id)
    {
        $m_open_access = m_open_access::find($id);   //find open access by ID

        return new MOpenAccessResource(true, 'Detail Data Open Access!', $m_open_access);   //return single open access as a resource
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

        //find open access by ID
            $m_open_access = m_open_access::find($id);
            $m_open_access->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MOpenAccessResource(true, 'Data Open Access Berhasil Diubah!', $m_open_access); //return response
    }

    public function destroy($id)
    {
        $m_open_access = m_open_access::find($id); //find open access by ID
        $m_open_access->delete();  //delete open access

        //return response
        return new MOpenAccessResource(true, 'Data Open Access Berhasil Dihapus!', null);
    }
}
