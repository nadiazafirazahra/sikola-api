<?php

namespace App\Http\Controllers\Api;

use App\Models\m_occupation;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MOccupationResource;

class MOccupationController extends Controller{

    public function index()
    {
        //get all occupation
        $m_occupations = m_occupation::latest()->paginate(5);

        //return collection of occupation as a resource
        return new MOccupationResource(true, 'List Data Occupation', $m_occupations);
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

        //create occupation
        $m_occupation = m_occupation::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MOccupationResource(true, 'Data Occupation Berhasil Ditambahkan!', $m_occupation);
    }

    public function show($id)
    {
        $m_occupation = m_occupation::find($id);   //find occupation by ID

        return new MOccupationResource(true, 'Detail Data Occupation!', $m_occupation);   //return single occupation as a resource
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

        //find occupation by ID
            $m_occupation = m_occupation::find($id);
            $m_occupation->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MOccupationResource(true, 'Data Occupation Berhasil Diubah!', $m_occupation); //return response
    }

    public function destroy($id)
    {
        $m_occupation = m_occupation::find($id); //find occupation by ID
        $m_occupation->delete();  //delete occupation

        //return response
        return new MOccupationResource(true, 'Data Occupation Berhasil Dihapus!', null);
    }
}
