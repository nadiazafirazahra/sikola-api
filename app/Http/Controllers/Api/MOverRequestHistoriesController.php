<?php

namespace App\Http\Controllers\Api;

use App\Models\m_over_request_histories;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MOverRequestHistoriesResource;

class MOverRequestHistoriesController extends Controller{

    public function index()
    {
        //get all over request histories
        $m_over_request_historieses = m_over_request_histories::latest()->paginate(5);

        //return collection of over request histories as a resource
        return new MOverRequestHistoriesResource(true, 'List Data Over Request Histories', $m_over_request_historieses);
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

        //create over request histories
        $m_over_request_histories = m_over_request_histories::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MOverRequestHistoriesResource(true, 'Data Over Request Histories Berhasil Ditambahkan!', $m_over_request_histories);
    }

    public function show($id)
    {
        $m_over_request_histories = m_over_request_histories::find($id);   //find over request histories by ID

        return new MOverRequestHistoriesResource(true, 'Detail Data Over Request Histories!', $m_over_request_histories);   //return single over request histories as a resource
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

        //find over request histories by ID
            $m_over_request_histories = m_over_request_histories::find($id);
            $m_over_request_histories->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MOverRequestHistoriesResource(true, 'Data Over Request Histories Berhasil Diubah!', $m_over_request_histories); //return response
    }

    public function destroy($id)
    {
        $m_over_request_histories = m_over_request_histories::find($id); //find over request histories by ID
        $m_over_request_histories->delete();  //delete over request histories

        //return response
        return new MOverRequestHistoriesResource(true, 'Data Over Request Histories Berhasil Dihapus!', null);
    }
}
