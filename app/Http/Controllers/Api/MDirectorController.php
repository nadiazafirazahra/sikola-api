<?php

namespace App\Http\Controllers\Api;

use App\Models\m_director;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MDirectorResource;

class MDirectorController extends Controller{

    public function index()
    {
        //get all director
        $m_directors = m_director::latest()->paginate(5);

        //return collection of director as a resource
        return new MDirectorResource(true, 'List Data Director', $m_directors);
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

        //create director
        $m_director = m_director::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MDirectorResource(true, 'Data Director Berhasil Ditambahkan!', $m_director);
    }

    public function show($id)
    {
        $m_director = m_director::find($id);   //find director by ID

        return new MDirectorResource(true, 'Detail Data Director!', $m_director);   //return single director as a resource
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

        //find director by ID
            $m_director = m_director::find($id);
            $m_director->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MDirectorResource(true, 'Data Director Berhasil Diubah!', $m_director); //return response
    }

    public function destroy($id)
    {
        $m_director = m_director::find($id); //find director by ID
        $m_director->delete();  //delete director

        //return response
        return new MDirectorResource(true, 'Data Director Berhasil Dihapus!', null);
    }
}
