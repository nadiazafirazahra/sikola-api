<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MBreakOtResource;
use App\Models\m_break_ot; // hotfix-1.5.21, by Merio Aji, 20160525, add master break
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class MBreakOtController extends Controller{

    public function index()
    {
        //get all break_ot
        $m_break_ots = m_break_ot::latest()->paginate(5);

        //return collection of break_ot as a resource
        return new MBreakOtResource(true, 'List Data Break Ot', $m_break_ots);
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

        //create break_ot
        $m_break_ot = m_break_ot::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MBreakOtResource(true, 'Data Break Ot Berhasil Ditambahkan!', $m_break_ot);
    }

    public function show($id)
    {
        $m_break_ot = m_break_ot::find($id);   //find break_ot by ID

        return new MBreakOtResource(true, 'Detail Data Break Ot!', $m_break_ot);   //return single break_ot as a resource
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

        //find break_ot by ID
            $m_break_ot = m_break_ot::find($id);
            $m_break_ot->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MBreakOtResource(true, 'Data Break Ot Berhasil Diubah!', $m_break_ot); //return response
    }

    public function destroy($id)
    {
        $m_break_ot = m_break_ot::find($id); //find break_ot by ID
        $m_break_ot->delete();  //delete break_ot

        //return response
        return new MBreakOtResource(true, 'Data Break Ot Berhasil Dihapus!', null);
    }
}
