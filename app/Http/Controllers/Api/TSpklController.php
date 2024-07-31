<?php

namespace App\Http\Controllers\Api;

use App\Models\t_spkl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\TSpklResource;
use Illuminate\Support\Facades\Validator;

class TSpklController extends Controller{

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
        $query = t_spkl::query();

        if ($date) {
            $query->whereDate('tanggal_spkl', $date);
        }

        $data = $query->paginate(20);

        return response()->json([
            'status' => true,
            'message' => $date ? 'List Data SPKL' : 'List Data SPKL',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $t_spkl = t_spkl::find($id);   //find spkl by ID

        return new TSpklResource(true, 'Detail Data SPKL!', $t_spkl);   //return single spkl as a resource
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

        //find spkl by ID
            $t_spkl = t_spkl::find($id);
            $t_spkl->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TSpklResource(true, 'Data SPKL Berhasil Diubah!', $t_spkl); //return response
    }

    public function destroy($id)
    {
        $t_spkl = t_spkl::find($id); //find spkl by ID
        $t_spkl->delete();  //delete spkl

        //return response
        return new TSpklResource(true, 'Data SPKL Berhasil Dihapus!', null);
    }

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
        $data = t_spkl::whereDate('tanggal_spkl', $tanggal)->get();

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
            'message' => 'Detail Data SPKL!',
            'data' => TSpklResource::collection($data),
            'tanggal' => $tanggal
        ]);
    }

    public function search(Request $request)
    {
        $query = DB::table('t_spkls')
            ->join('m_employees', 't_spkls.npk_1', '=', 'm_employees.npk')
            ->select(
                'm_employees.id as id',
                'm_employees.nama as name',
                'm_employees.npk as npk',
                't_spkls.tanggal_spkl',
                't_spkls.jam_masuk',
                't_spkls.jam_pulang'
            )
            ->distinct();

        // Filter by tanggal_spkl if provided
        if ($request->has('tanggal_spkl')) {
            $query->where('t_spkls.tanggal_spkl', $request->input('tanggal_spkl'));
        }

        // Filter by npk if provided
        if ($request->has('npk')) {
            $query->where('m_employees.npk', $request->input('npk'));
        }

        $results = $query->get()
            ->map(function ($item) {
                return [
                    'id' => $item->employee_id,
                    'nama' => $item->employee_name,
                    'npk' => $item->employee_npk,
                    'tanggal_spkl' => $item->tanggal_spkl,
                    'jam_masuk' => $item->jam_masuk,
                    'jam_pulang' => $item->jam_pulang,
                ];
            });

        return response()->json([
            'status' => true,
            'message' => 'Detail Data SPKL!',
            'data' => $results,
        ]);
    }
}
