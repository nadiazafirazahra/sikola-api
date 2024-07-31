<?php

namespace App\Http\Controllers\Api;

use App\Models\m_sub_section;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MSubSectionResource;

class MSubSectionController extends Controller{

    public function index()
    {
        //get all sub section
        $m_sub_sections = m_sub_section::latest()->paginate(20);

        //return collection of sub section as a resource
        return new MSubSectionResource(true, 'List Data Sub Section', $m_sub_sections);
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

        //create sub section
        $m_sub_section = m_sub_section::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MSubSectionResource(true, 'Data Sub Section Berhasil Ditambahkan!', $m_sub_section);
    }

    public function show($id)
    {
        $m_sub_section = m_sub_section::find($id);   //find sub section by ID

        return new MSubSectionResource(true, 'Detail Data Sub Section!', $m_sub_section);   //return single sub section as a resource
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

        //find sub section by ID
            $m_sub_section = m_sub_section::find($id);
            $m_sub_section->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MSubSectionResource(true, 'Data Sub Section Berhasil Diubah!', $m_sub_section); //return response
    }

    public function destroy($id)
    {
        $m_sub_section = m_sub_section::find($id); //find sub section by ID
        $m_sub_section->delete();  //delete sub section

        //return response
        return new MSubSectionResource(true, 'Data Sub Section Berhasil Dihapus!', null);
    }
}
