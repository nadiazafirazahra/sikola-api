<?php

namespace App\Http\Controllers\Api;

use App\Models\m_line;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MLineResource;

class MLineController extends Controller{

    public function index()
    {
        //get all line
        $m_lines = m_line::latest()->paginate(5);

        //return collection of line as a resource
        return new MLineResource(true, 'List Data Line', $m_lines);
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

        //create line
        $m_line = m_line::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MLineResource(true, 'Data Line Berhasil Ditambahkan!', $m_line);
    }

    public function show($id)
    {
        $m_line = m_line::find($id);   //find line by ID

        return new MLineResource(true, 'Detail Data Line!', $m_line);   //return single line as a resource
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

        //find line by ID
            $m_line = m_line::find($id);
            $m_line->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MLineResource(true, 'Data Line Berhasil Diubah!', $m_line); //return response
    }

    public function destroy($id)
    {
        $m_line = m_line::find($id); //find line by ID
        $m_line->delete();  //delete line

        //return response
        return new MLineResource(true, 'Data Line Berhasil Dihapus!', null);
    }
}
