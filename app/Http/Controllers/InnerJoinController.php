<?php

namespace App\Http\Controllers;

use App\Models\lembur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InnerJoinController extends Controller
{
    public function getJoinedData(Request $request)
    {
        // Mengambil parameter dari query string
        $npk = $request->query('npk');
        $start_date = $request->query('start_date');

        // Validasi format tanggal jika disediakan
        if ($start_date && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) {
            return response()->json([
                'status' => false,
                'message' => 'Tanggal format is invalid. Use YYYY-MM-DD',
                'data' => null
            ], 400);
        }

        // Logging nilai start_date untuk debugging
        Log::info('Start Date: ' . $start_date);

        // Membangun query dengan join dan filter
        $query = DB::table('t_spkl_details')
            ->join('m_employees', 't_spkl_details.npk', '=', 'm_employees.npk')
            ->select(
                'm_employees.id as id',
                'm_employees.nama as name',
                'm_employees.npk as npk',
                't_spkl_details.start_date as start_date',
                't_spkl_details.end_date as end_date',
                't_spkl_details.start_planning as start_planning',
                't_spkl_details.end_planning as end_planning',
                't_spkl_details.sub_section as sub_section'
            );

        // Menerapkan filter jika disediakan
        if ($npk) {
            $query->where('m_employees.npk', $npk);
        }

        if ($start_date) {
            $query->whereDate('t_spkl_details.start_date', $start_date);
        }

        // Menjalankan query dan mengambil hasilnya
        $results = $query->get();

        // Mengelompokkan hasil berdasarkan start_date
        $groupedResults = $results->groupBy('start_date');

        // Logging hasil query untuk debugging
        Log::info('Query Results: ', $results->toArray());

        // Mengembalikan respons JSON
        return response()->json([
            'status' => true,
            'message' => 'Detail Data SPKL!',
            'data' => $groupedResults,
        ]);
    }

}






// ***************************************************URL TANGGAL**********************************************************************************//
// ******************************************************SPKL**************************************************************************************//




//     public function getJoinedData(Request $request)
//     {
//         $npk = $request->query('npk');
//         $tanggal = $request->query('tanggal');

//         // Validate date format if provided
//         if ($tanggal && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
//             return response()->json([
//                 'status' => false,
//                 'message' => 'Tanggal format is invalid. Use YYYY-MM-DD',
//                 'data' => null
//             ], 400);
//         }

//         $query = DB::table('t_spkls')
//             ->join('m_employees', 't_spkls.tanggal_spkl', '=', 'm_employees.npk') // Adjusted join condition
//             ->select(
//                 'm_employees.id as id',
//                 'm_employees.nama as name',
//                 'm_employees.npk as npk',
//                 't_spkls.tanggal_spkl',
//                 't_spkls.jam_masuk',
//                 't_spkls.jam_pulang'
//             )
//             ->distinct(); // Ensure distinct results

//         // Apply filters if provided
//         if ($npk) {
//             $query->where('m_employees.npk', $npk);
//         }

//         if ($tanggal) {
//             $query->whereDate('t_spkls.tanggal_spkl', $tanggal);
//         }

//         $results = $query->get(); // Retrieve data

//         return response()->json([
//             'status' => true,
//             'message' => 'Detail Data SPKL!',
//             'data' => $results,
//         ]);
//     }
// }

























