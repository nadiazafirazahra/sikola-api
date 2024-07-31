<?php

namespace App\Http\Controllers\Api;

use App\Models\m_weekday_holiday;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MWeekdayHolidayResource;

class MWeekdayHolidayController extends Controller{

    public function index()
    {
        //get all weekday holiday
        $m_weekday_holidays = m_weekday_holiday::latest()->paginate(20);

        //return collection of weekday holiday as a resource
        return new MWeekdayHolidayResource(true, 'List Data Weekday Holiday', $m_weekday_holidays);
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

        //create weekday holiday
        $m_weekday_holiday = m_weekday_holiday::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new MWeekdayHolidayResource(true, 'Data Weekday Holiday Berhasil Ditambahkan!', $m_weekday_holiday);
    }

    public function show($id)
    {
        $m_weekday_holiday = m_weekday_holiday::find($id);   //find weekday holiday by ID

        return new MWeekdayHolidayResource(true, 'Detail Data Weekday Holiday!', $m_weekday_holiday);   //return single weekday holiday as a resource
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

        //find weekday holiday by ID
            $m_weekday_holiday = m_weekday_holiday::find($id);
            $m_weekday_holiday->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new MWeekdayHolidayResource(true, 'Data Weekday Holiday Berhasil Diubah!', $m_weekday_holiday); //return response
    }

    public function destroy($id)
    {
        $m_weekday_holiday = m_weekday_holiday::find($id); //find weekday holiday by ID
        $m_weekday_holiday->delete();  //delete weekday holiday

        //return response
        return new MWeekdayHolidayResource(true, 'Data Weekday Holiday Berhasil Dihapus!', null);
    }
}
