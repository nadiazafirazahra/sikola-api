<?php

namespace App\Http\Controllers\Api;

use App\Models\m_transport;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MTransportResource;

class MTransportController extends Controller{

    public function index()
    {
        //get all trasnport
        $m_transports = m_transport::latest()->paginate(20);

        //return collection of trasnport as a resource
        return new MTransportResource(true, 'List Data Transport', $m_transports);
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

        //create trasnport
        $m_transport = m_transport::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MTransportResource(true, 'Data Transport Berhasil Ditambahkan!', $m_transport);
    }

    public function show($id)
    {
        $m_transport = m_transport::find($id);   //find trasnport by ID

        return new MTransportResource(true, 'Detail Data Transport!', $m_transport);   //return single trasnport as a resource
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

        //find trasnport by ID
            $m_transport = m_transport::find($id);
            $m_transport->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MTransportResource(true, 'Data Transport Berhasil Diubah!', $m_transport); //return response
    }

    public function destroy($id)
    {
        $m_transport = m_transport::find($id); //find trasnport by ID
        $m_transport->delete();  //delete trasnport

        //return response
        return new MTransportResource(true, 'Data Transport Berhasil Dihapus!', null);
    }
}
