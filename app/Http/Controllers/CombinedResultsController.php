<?php

namespace App\Http\Controllers;

use LDAP\Result;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CombinedResultsController extends Controller
{
    public function storeCombinedResults(): JsonResponse
    {
        $result = DB::table('lemburs')
            ->join('t_spkls', 'lemburs.id', '=', 't_spkls.id_spkl')
            ->join('m_employees', 'lemburs.npk', '=', 'm_employees.npk')
            ->select('lemburs.id as lembur_id', 't_spkls.id as t_spkl_id', 'm_employees.id as m_employee_id')
            ->get();

            if ($result->isEmpty()) {
                return response()->json(['message' => 'No results to store'], 404);
            }

        foreach($result as $result){
            DB::table('combined_results')->insert([
                'lembur_id' => $result->lembur_id,
                't_spkl_id' => $result->t_spkl_id,
                'm_employee_id' => $result->employee_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return response()->json(['message' => 'Combined results store successfully']);
    }
}
