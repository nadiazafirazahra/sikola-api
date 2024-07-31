<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

use Carbon\Carbon;
use App\Models\m_transport;
use App\m_employee;
use App\Models\m_break_ot;		// hotfix-1.6.3, Ferry, 20160621

class m_shift extends Model {

	// dev-1.6.0, Ferry, 20160511, m_shift models untuk shift makan dan shift transport
	protected $fillable =  [
        'id',
		'kode',
		'nama',
		'tipe_golongan',
		'tipe_hari_kode',
		'tipe_hari_desc',
		'time_in',
		'time_out',
		'time_cutoff1',
		'time_cutoff2',
		'days_out',
        'created_at',
        'updated_at'
	];

    public function getShiftMakanInfoAttribute()
    {
        $result = ($this->time_cutoff1 ? $this->time_cutoff1 : '').
        					($this->time_cutoff2 ? ' - '.$this->time_cutoff2 : '');
        return (empty($result) ? 'No Meal' : $result);	// hotfix-1.6.3, 20160622, Ferry, more sophisticated message
    }

    public static function generateCodeTransport ($carbon_dt_start, $carbon_dt_end, $trans_code = '9999', $day_type = 1) {
    	// Sementara pakai acuan transport yang ada di Model m_transport

    	$trans_code = is_null($trans_code) ? '9999' : $trans_code;
    	// if ($trans_code = '9999') {
    	// 	return $trans_code;
    	// }

		$short_shift = self::where('time_in', '00:00')
						->where('time_out', '00:00')
						->where('tipe_golongan', 'Transport')
						->where('tipe_hari_kode', $day_type)
						->pluck('kode');

    	// Ambil Route dari m_transports
    	$route_name = m_transport::where('code', $trans_code)->pluck('route');
    	$routes = m_transport::where('route', $route_name)->get();

    	$current_diff = 9999;
    	$result_transport = collect (['kd_shift' => $short_shift, 'code' => $trans_code]);

    	foreach ($routes as $route) {
    		// Get values by pluck
    		$m_time_in = self::where('kode', $route->kd_shift)->pluck('time_in');
    		$m_time_out = self::where('kode', $route->kd_shift)->pluck('time_out');
    		$m_days_out = self::where('kode', $route->kd_shift)->pluck('days_out');

			// reformat time_in dan time_out dalam bentuk Carbon
			$temp_dt_start = Carbon::parse($carbon_dt_start->toDateTimeString());
			$shift_dt_in = Carbon::parse($temp_dt_start->toDateString().' '.$m_time_in);
			$shift_dt_out = Carbon::parse($temp_dt_start->addDays($m_days_out)->toDateString().' '.$m_time_out);


	    	// Jika hari biasa maka cek jam keluar saja yg difference kecil dgn timeout master transport
	    	// Jika hari libur maka cek jam in Out diff dgn time in/out transport
			$calc_diff = ($day_type == 1) ? $shift_dt_out->diffInMinutes($carbon_dt_end) :
											$shift_dt_in->diffInMinutes($carbon_dt_start) +
													$shift_dt_out->diffInMinutes($carbon_dt_end);
			// Cari selisih start dan end nya yg terkecil/mendekati maka dialah kandidat shift terdekatnya
            if ($calc_diff < $current_diff) {
            	$result_transport = $route;
            	$current_diff = $calc_diff;
            }
    	}
    	return $result_transport;
    }

	public static function generateShiftMakan ($carbon_dt_start, $carbon_dt_end, $day_type = 1) {

		// Saring shift type yang sesuai
		$short_shift = self::where('tipe_hari_kode', $day_type)
						->where('tipe_golongan', 'Makan')
						->whereNull('time_cutoff1')
						->whereNull('time_cutoff2')
						->pluck('kode');

		$shifts = self::where('tipe_hari_kode', $day_type)
						->where('tipe_golongan', 'Makan')
						->where(function ($query) {
								    $query->whereNotNull('time_cutoff1')
								          ->orWhereNotNull('time_cutoff2');
								})
						->get();

		// Cari selisih start dan end nya yg terkecil/mendekati, cek apakah dpt makan di time_cutoff1
		$result_shift = $short_shift;	// Jika tidak dapat makan, maka masuk short shift
		$current_diff = 9999;

		// hotfix-1.6.3, Ferry, 20160621, Bugs belum cek durasi terhadap break
		if (! self::is_lembur_valid($carbon_dt_start, $carbon_dt_end)) {
			return $short_shift;
		}

		foreach ($shifts as $shift) {
			// reformat time_in dan time_out dalam bentuk Carbon
			$temp_dt_start = Carbon::parse($carbon_dt_start->toDateTimeString());
			$shift_dt_in = Carbon::parse($temp_dt_start->toDateString().' '.$shift->time_in);
			$shift_dt_out = Carbon::parse($temp_dt_start->addDays($shift->days_out)->toDateString().' '.$shift->time_out);
			$calc_diff = $shift_dt_in->diffInMinutes($carbon_dt_start) + $shift_dt_out->diffInMinutes($carbon_dt_end);

			// Cari selisih start dan end nya yg terkecil/mendekati maka dialah kandidat shift terdekatnya
            if ($calc_diff < $current_diff) {

            	// cek dulu time_cutoff1, jika tdk null, apakah diantara jam overtime
            	if (! is_null($shift->time_cutoff1)) {

            		$dt_cutoff1 = Carbon::parse($carbon_dt_start->toDateString().' '.$shift->time_cutoff1);

            		if ($dt_cutoff1->between($carbon_dt_start, $carbon_dt_end)) {

            			// Sebelum putuskan, cek dulu cutoff2 nya, jika between juga boleh lah dianggap
            			// $temp_dt_start sudah addDay()
            			if (! is_null($shift->time_cutoff2)) {
	            			$dt_cutoff2 = Carbon::parse($temp_dt_start->toDateString().' '.$shift->time_cutoff2);

	            			if ($dt_cutoff2->between($carbon_dt_start, $carbon_dt_end)) {
				            	$current_diff = $calc_diff;
				            	$result_shift = $shift->kode;
				            }
				        }
				        else {
							$current_diff = $calc_diff;
				            $result_shift = $shift->kode;
				        }
		            }
            	}
            	// cek lagi addDay+time_cutoff2, jika tdk null, apakah diantara jam overtime
            	elseif (! is_null($shift->time_cutoff2)) {
            		// $temp_dt_start sudah addDay()
            		$dt_cutoff2 = Carbon::parse($temp_dt_start->toDateString().' '.$shift->time_cutoff2);

	            	if ($dt_cutoff2->between($carbon_dt_start, $carbon_dt_end)) {
		            	$current_diff = $calc_diff;
		            	$result_shift = $shift->kode;
		            }
            	}
            }
        }

        return $result_shift;
	}

	// hotfix-1.6.3, Ferry, 20160621, validitas lembur adalah 3 jam lebih atau 180 menit lebih
	public static function is_lembur_valid($carbon_dt_start, $carbon_dt_end) {

		// Hiitung Selisih jam akhir - jam awal lembur
		$calc_diff = $carbon_dt_end->diffInMinutes($carbon_dt_start);

		// Cari durasi break dan kurangkan
		$breaktimes = m_break_ot::where('day_break', $carbon_dt_start->format('N'))
								->where('status_break','1')->get();

		// loop sambil cek jika interval masuk maka kurangkan
		foreach ($breaktimes as $breaktime) {
			// reformat time_in dan time_out dalam bentuk Carbon
			$break_dt_in = Carbon::parse($carbon_dt_start->toDateString().' '.$breaktime->start_break);
			$break_dt_out = Carbon::parse($carbon_dt_start->toDateString().' '.$breaktime->end_break);

			// break ada di range lembur --> kurangi
			if ( ($break_dt_in->between($carbon_dt_start, $carbon_dt_end)) &&
					($break_dt_out->between($carbon_dt_start, $carbon_dt_end)) ) {

				$calc_diff -= intval($breaktime->duration_break);
			}
		}

		return ($calc_diff >= 180);
	}
}
