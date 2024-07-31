<?php

namespace App\Http\Controllers\Api;

use App\Models\m_section;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MSectionResource;

class MSectionController extends Controller{

    public function index()
    {
        //get all section
        $m_sections = m_section::latest()->paginate(5);

        //return collection of section as a resource
        return new MSectionResource(true, 'List Data Section', $m_sections);
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

        //create section
        $m_section = m_section::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MSectionResource(true, 'Data Section Berhasil Ditambahkan!', $m_section);
    }

    public function show($id)
    {
        $m_section = m_section::find($id);   //find section by ID

        return new MSectionResource(true, 'Detail Data Section!', $m_section);   //return single section as a resource
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

        //find section by ID
            $m_section = m_section::find($id);
            $m_section->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MSectionResource(true, 'Data Section Berhasil Diubah!', $m_section); //return response
    }

    public function destroy($id)
    {
        $m_section = m_section::find($id); //find section by ID
        $m_section->delete();  //delete section

        //return response
        return new MSectionResource(true, 'Data Section Berhasil Dihapus!', null);
    }
}
