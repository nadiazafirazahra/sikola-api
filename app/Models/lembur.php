<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class lembur extends Model {
    protected $table = 'lemburs';
	protected $fillable =  [
        'id',
        'nama',
        'npk',
		'title',
		'tanggal_lembur',
		'jam_masuk',
		'jam_pulang',
		'created_at',
        'updated_at',
	];

	// dev-2.1, Ferry, 20160830, Mendapatkan list tahun-bulan (yyyy-mm)
	public static function getMonths() {
		$months = self::selectRaw('DATE_FORMAT(t_spkl_details.start_date, \'%Y-%m\') as ym')
							->join('t_spkl_details','t_spkl_details.id_spkl','=','t_spkls.id_spkl')
							->whereRaw ('month(t_spkl_details.start_date) > 0 AND month(t_spkl_details.start_date) <= 12')
							->orderBy('t_spkl_details.start_date', 'DESC')
							->distinct()
							->get();

		// month untuk bulan Now harus include
		if (Carbon::now()->format('Y-m') != $months->first()->ym) {
			$months->prepend(['ym' => Carbon::now()->format('Y-m')]);
		}
		return $months;		// dev-2.1, months sekarang jadi plain collection, tidak bisa diakses by ->ym , tapi ['ym']
	}

	// dev-2.0, Ferry, 20160820, Formatting output of start_date
	public function getStartDateFormattedAttribute($value) {

		$day	= Carbon::parse($value)->format('D');

		switch (ucfirst($day)) {
		 	case 'Sun':
		 		$day = $value.', Minggu';
		 		break;
		 	case 'Mon':
		 		$day =  $value.', Senin';
		 		break;
		 	case 'Tue':
		 		$day =  $value.', Selasa';
		 		break;
		 	case 'Wed':
		 		$day =  $value.', Rabu';
		 		break;
		 	case 'Thu':
		 		$day =  $value.', Kamis';
		 		break;
		 	case 'Fri':
		 		$day =  $value.', Jumat';
		 		break;
		 	case 'Sat':
		 		$day =  $value.', Sabtu';
		 		break;
		 	default:
		 		$day =  '';
		 		break;
		}
		return $day;
	}

	// dev-2.0, Ferry, 20160819, Formatting output of note
	public function getNoteCapitalizedAttribute($value) {
        return ucfirst($value);
    }

	// dev-2.0, Ferry, 20160819, Formatting output of Total jam di SPKL Planning
	public function getSumHoursPlanAttribute($value) {
        return round($value / 60, 2);
    }

	// dev-2.0, Ferry, 20160819, Formatting output of Total jam di SPKL Actual
	public function getSumHoursActualAttribute($value) {
        return round($value / 60, 2);
    }

    // dev-2.0, Ferry, 20160819, Formatting output of quota rounded
    public function getQuotaUsedRoundedAttribute($value) {
        return round($value / 60, 2);
    }

    public function getQuotaRemainRoundedAttribute($value) {
        return round($value / 60, 2);
    }

    public function getMaxHoursRoundedAttribute($value) {
        $pieces = explode("|", $value);
        $pieces[0] = round($pieces[0] / 60, 2);
        $pieces[1] = round($pieces[1] / 60, 2);

        return '('.$pieces[0].'/'.$pieces[1].')';
    }
}
