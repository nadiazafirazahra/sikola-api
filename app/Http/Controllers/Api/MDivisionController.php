<?php

namespace App\Http\Controllers\Api;

use App\Models\m_division;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MDivisionResource;

class MDivisionController extends Controller{

    public function index()
    {
        //get all division
        $m_divisions = m_division::latest()->paginate(5);

        //return collection of division as a resource
        return new MDivisionResource(true, 'List Data Division', $m_divisions);
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

        //create division
        $m_division = m_division::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MDivisionResource(true, 'Data Division Berhasil Ditambahkan!', $m_division);
    }

    public function show($id)
    {
        $m_division = m_division::find($id);   //find division by ID

        return new MDivisionResource(true, 'Detail Data Division!', $m_division);   //return single division as a resource
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

        //find division by ID
            $m_division = m_division::find($id);
            $m_division->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MDivisionResource(true, 'Data Division Berhasil Diubah!', $m_division); //return response
    }

    public function destroy($id)
    {
        $m_division = m_division::find($id); //find division by ID
        $m_division->delete();  //delete division

        //return response
        return new MDivisionResource(true, 'Data Division Berhasil Dihapus!', null);
    }
}
