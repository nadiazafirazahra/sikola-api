<?php

namespace App\Http\Controllers\Api;

use App\Models\m_holiday;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MHolidayResource;

class MHolidayController extends Controller{

    public function index()
    {
        //get all holiday
        $m_holidays = m_holiday::latest()->paginate(5);

        //return collection of holiday as a resource
        return new MHolidayResource(true, 'List Data Holiday', $m_holidays);
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

        //create holiday
        $m_holiday = m_holiday::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MHolidayResource(true, 'Data Holiday Berhasil Ditambahkan!', $m_holiday);
    }

    public function show($id)
    {
        $m_holiday = m_holiday::find($id);   //find holiday by ID

        return new MHolidayResource(true, 'Detail Data Holiday!', $m_holiday);   //return single holiday as a resource
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

        //find holiday by ID
            $m_holiday = m_holiday::find($id);
            $m_holiday->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MHolidayResource(true, 'Data Holiday Berhasil Diubah!', $m_holiday); //return response
    }

    public function destroy($id)
    {
        $m_holiday = m_holiday::find($id); //find holiday by ID
        $m_holiday->delete();  //delete holiday

        //return response
        return new MHolidayResource(true, 'Data Holiday Berhasil Dihapus!', null);
    }
}
