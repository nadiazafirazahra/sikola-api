<?php

namespace App\Http\Controllers\Api;

use App\Models\m_spesial_limit_histories;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MSpesialLimitHistoriesResource;

class MSpesialLimitHistoriesController extends Controller{

    public function index()
    {
        //get all spesial limit histories
        $m_spesial_limit_histories = m_spesial_limit_histories::latest()->paginate(20);

        //return collection of spesial limit histories as a resource
        return new MSpesialLimitHistoriesResource(true, 'List Data Spesial Limit Histories', $m_spesial_limit_histories);
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

        //create spesial limit histories
        $m_spesial_limit_histories = m_spesial_limit_histories::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MSpesialLimitHistoriesResource(true, 'Data Spesial Limit Histories Berhasil Ditambahkan!', $m_spesial_limit_histories);
    }

    public function show($id)
    {
        $m_spesial_limit_histories = m_spesial_limit_histories::find($id);   //find spesial limit histories by ID

        return new MSpesialLimitHistoriesResource(true, 'Detail Data Spesial Limit Histories!', $m_spesial_limit_histories);   //return single spesial limit histories as a resource
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

        //find spesial limit histories by ID
            $m_spesial_limit_histories = m_spesial_limit_histories::find($id);
            $m_spesial_limit_histories->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MSpesialLimitHistoriesResource(true, 'Data Spesial Limit Histories Berhasil Diubah!', $m_spesial_limit_histories); //return response
    }

    public function destroy($id)
    {
        $m_spesial_limit_histories = m_spesial_limit_histories::find($id); //find spesial limit histories by ID
        $m_spesial_limit_histories->delete();  //delete spesial limit histories

        //return response
        return new MSpesialLimitHistoriesResource(true, 'Data Spesial Limit Histories Berhasil Dihapus!', null);
    }
}
