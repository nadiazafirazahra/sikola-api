<?php

namespace App\Http\Controllers\Api;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ShowQuery extends Command
{
    protected $signature = 'query:show';
    protected $description = 'Show the SQL query for the join operation';

    public function handle()
    {
        $query = DB::table('lemburs')
            ->join('spkls', 'lemburs.id', '=', 'spkls.id_spkl')
            ->join('employees', 'lemburs.npk', '=', 'employees.npk')
            ->select(
                'lemburs.id as lembur_id', 'lemburs.nama as lembur_nama', 'lemburs.title as lembur_title', 'lemburs.tanggal_lembur as lembur_tanggal_lembur',
                'spkls.id as spkl_id', 'spkls.type as spkl_type', 'spkls.category as spkl_category', 'spkls.note as spkl_note',
                'employees.npk as employee_npk', 'employees.nama as employee_nama', 'employees.occupation as employee_occupation'
            );

        // Tampilkan query SQL
        $this->info($query->toSql());
    }
}
