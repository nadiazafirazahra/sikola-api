<?php

namespace App\Http\Controllers\Api;

use App\Models\m_spesial_limits;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MSpesialLimitsResource;

class MSpesialLimitsController extends Controller{

    public function index()
    {
        //get all spesial limits
        $m_spesial_limits = m_spesial_limits::latest()->paginate(20);

        //return collection of spesial limits as a resource
        return new MSpesialLimitsResource(true, 'List Data Spesial Limits', $m_spesial_limits);
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

        //create spesial limits
        $m_spesial_limits = m_spesial_limits::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MSpesialLimitsResource(true, 'Data Spesial Limits Berhasil Ditambahkan!', $m_spesial_limits);
    }

    public function show($id)
    {
        $m_spesial_limits = m_spesial_limits::find($id);   //find spesial limits by ID

        return new MSpesialLimitsResource(true, 'Detail Data Spesial Limits!', $m_spesial_limits);   //return single spesial limits as a resource
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

        //find spesial limits by ID
            $m_spesial_limits = m_spesial_limits::find($id);
            $m_spesial_limits->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MSpesialLimitsResource(true, 'Data Spesial Limits Berhasil Diubah!', $m_spesial_limits); //return response
    }

    public function destroy($id)
    {
        $m_spesial_limits = m_spesial_limits::find($id); //find spesial limits by ID
        $m_spesial_limits->delete();  //delete spesial limits

        //return response
        return new MSpesialLimitsResource(true, 'Data Spesial Limits Berhasil Dihapus!', null);
    }
}
