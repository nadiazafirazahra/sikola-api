<?php

namespace App\Http\Controllers\Api;

use App\Models\m_shift;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MShiftResource;

class MShiftController extends Controller{

    public function index()
    {
        //get all shift
        $m_shifts = m_shift::latest()->paginate(20);

        //return collection of shift as a resource
        return new MShiftResource(true, 'List Data Shift', $m_shifts);
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

        //create shift
        $m_shift = m_shift::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MShiftResource(true, 'Data Shift Berhasil Ditambahkan!', $m_shift);
    }

    public function show($id)
    {
        $m_shift = m_shift::find($id);   //find shift by ID

        return new MShiftResource(true, 'Detail Data Shift!', $m_shift);   //return single shift as a resource
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

        //find shift by ID
            $m_shift = m_shift::find($id);
            $m_shift->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MShiftResource(true, 'Data Shift Berhasil Diubah!', $m_shift); //return response
    }

    public function destroy($id)
    {
        $m_shift = m_shift::find($id); //find shift by ID
        $m_shift->delete();  //delete shift

        //return response
        return new MShiftResource(true, 'Data Shift Berhasil Dihapus!', null);
    }
}
