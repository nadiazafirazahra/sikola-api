<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CombinedResult extends Model
{
    // Tentukan kolom-kolom yang Anda inginkan dalam hasil gabungan
    protected $fillable = [
        'lembur_id', 'lembur_nama', 'lembur_title', 'lembur_tanggal_lembur',
        'spkl_id', 'spkl_type', 'spkl_category', 'spkl_note',
        'employee_npk', 'employee_nama', 'employee_occupation'
    ];

    // Tentukan tabel jika berbeda dari nama default
    protected $table = 'combined_results';
}
