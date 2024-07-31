<?php

namespace App\Http\Controllers\Api;

use DateTime;
use App\Models\lembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\LemburResource;
use Illuminate\Support\Facades\Validator;

class LemburController extends Controller{

    public function index(Request $request)
    {
        // Ambil parameter 'date' dari query string
        $date = $request->query('date');

        // Validasi format tanggal jika ada
        if ($date) {
            $validator = Validator::make(['tanggal' => $date], [
                'tanggal' => 'date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tanggal tidak valid',
                    'data' => null
                ], 400);
            }
        }

        // Ambil data dengan atau tanpa filter berdasarkan tanggal
        $query = Lembur::query();

        if ($date) {
            $query->whereDate('tanggal_lembur', $date);
        }

        $data = $query->get();

        return response()->json([
            'status' => true,
            'message' => $date ? 'List Data Lembur' : 'List Data Lembur',
            'data' => $data
        ]);
    }
    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'nama'              => 'required',
            'npk'               => 'required',
            'title'             => 'required',
            'tanggal_lembur'    => 'required|date',
            'jam_masuk'         => 'required|date_format:H:i',
            'jam_pulang'        => 'required|date_format:H:i',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //create
        $lembur = lembur::create([
            'nama'              => $request->nama,
            'npk'               => $request->npk,
            'title'             => $request->title,
            'tanggal_lembur'    => $request->tanggal_lembur,
            'jam_masuk'         => $request->jam_masuk,
            'jam_pulang'        => $request->jam_pulang
        ]);

        //return response
        return new LemburResource(true, 'Data Lembur Berhasil Ditambahkan!', $lembur);
    }

    public function show($id)
    {
        $lembur = lembur::find($id);   //find lembur by ID

        return new LemburResource(true, 'Detail Data Lembur!', $lembur);   //return single lembur as a resource
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [  //define validation rules
           'nama'              => 'required',
           'npk'               => 'required',
           'title'             => 'required',
           'tanggal_lembur'    => 'required|date',
           'jam_masuk'         => 'required|date_format:H:i',
           'jam_pulang'        => 'required|date_format:H:i',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);  //check if validation fails
        }

        //find lembur by ID
            $lembur = lembur::find($id);
            $lembur->update([
                'nama'              => $request->nama,
                'npk'               => $request->npk,
                'title'             => $request->title,
                'tanggal_lembur'    => $request->tanggal_lembur,
                'jam_masuk'         => $request->jam_masuk,
                'jam_pulang'        => $request->jam_pulang
            ]);

        return new LemburResource(true, 'Data Lembur Berhasil Diubah!', $lembur); //return response
    }

    public function destroy($id)
    {
        $lembur = lembur::find($id); //find lrmbur by ID
        $lembur->delete();  //delete lembur

        //return response
        return new LemburResource(true, 'Data Lembur Berhasil Dihapus!', null);
    }


    // Method to get lembur data by date
    public function getByDate($tanggal)
    {
        // Validasi format tanggal
        $validator = Validator::make(['tanggal' => $tanggal], [
            'tanggal' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal tidak valid',
                'data' => null
            ], 400);
        }

        // Ambil data berdasarkan tanggal
        $data = Lembur::whereDate('tanggal_lembur', $tanggal)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Tidak ada data untuk tanggal ini',
                'data' => null
            ]);
        }

        // Mengembalikan data dengan format resource
        return response()->json([
            'status' => true,
            'message' => 'Detail Data Lembur!',
            'data' => LemburResource::collection($data),
            'tanggal' => $tanggal
        ]);
    }

    // Method to get lembur data by npk (if needed)
    public function getByNpk($npk)
    {
        $data = Lembur::where('npk', $npk)->get();

        if ($data->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Tidak ada data untuk NPK ini',
                'data' => null
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Detail Data Lembur!',
            'data' => LemburResource::collection($data),
            'npk' => $npk
        ]);
    }
}



    // *******************************************************************************************************************************************************************************************************//
    // *******************************************************************LEMBUR******************************************************************************************************************************//
    // *******************************************************************************************************************************************************************************************************//


    // public function getByDate($tanggal)
    // {
    //     // Validasi format tanggal
    //     $validator = Validator::make(['tanggal' => $tanggal], [
    //         'tanggal' => 'required|date_format:Y-m-d',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'Tanggal tidak valid',
    //             'data' => null
    //         ], 400);
    //     }

    //     // Ambil data berdasarkan tanggal
    //     $data = Lembur::whereDate('tanggal_lembur', $tanggal)->get();

    //     if ($data->isEmpty()) {
    //         return response()->json([
    //             'status' => true,
    //             'message' => 'Tidak ada data untuk tanggal ini',
    //             'data' => null
    //         ]);
    //     }

    //     // Mengembalikan data dengan format resource
    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Detail Data Lembur!',
    //         'data' => LemburResource::collection($data),
    //         'tanggal' => $tanggal
    //     ]);
    // }

    // *******************************************************************************************************************************************************************************************************//
    // *******************************************************************************************************************************************************************************************************//
    // *******************************************************************************************************************************************************************************************************//

//     public function search(Request $request)
//     {
//         $query = DB::table('lemburs')
//             ->join('t_spkls', 'lemburs.id', '=', 't_spkls.id_spkl')
//             ->join('m_employees', 'lemburs.npk', '=', 'm_employees.npk')
//             ->select(
//                 'lemburs.id as lembur_id',
//                 'lemburs.title as lembur_title',
//                 'lemburs.tanggal_lembur',
//                 'lemburs.jam_masuk',
//                 'lemburs.jam_pulang',
//                 't_spkls.id as spkl_id',
//                 't_spkls.category as spkl_category',
//                 'm_employees.nama as employee_name',
//                 'm_employees.npk as employee_npk'
//             );

//         // Filter by tanggal_lembur if provided
//         if ($request->has('tanggal_lembur')) {
//             $query->where('lemburs.tanggal_lembur', $request->input('tanggal_lembur'));
//         }

//         // Filter by npk if provided
//         if ($request->has('npk')) {
//             $query->where('lemburs.npk', $request->input('npk'));
//         }

//         $results = $query->get()
//             ->map(function ($item) {
//                 return [
//                     'lembur_id' => $item->lembur_id,
//                     'lembur_title' => $item->lembur_title,
//                     'tanggal_lembur' => $item->tanggal_lembur,
//                     'jam_masuk' => $item->jam_masuk,
//                     'jam_pulang' => $item->jam_pulang,
//                     'spkls' => [
//                         'spkl_id' => $item->spkl_id,
//                         'spkl_category' => $item->spkl_category,
//                     ],
//                     'employee' => [
//                         'nama' => $item->employee_name,
//                         'npk' => $item->employee_npk,
//                     ],
//                 ];
//             });

//         return response()->json([
//             'status' => true,
//             'message' => 'Detail Data Lembur!',
//             'data' => $results,
//         ]);
//     }
