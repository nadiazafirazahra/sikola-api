<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class m_transport extends Model {

	protected $fillable = [
        'id',
        'code',
        'route',
        'kd_shift',
        'time_in',
        'time_out',
        'created_at',
        'updated_at'
    ];

	public static function array_to_db($array_data){
        $total=sizeof($array_data);
        if($total>0){
            try {
                foreach ($array_data as $value) {
                    $key=explode(';',$value);
                    self::create([
                        'code'     			=>$key[0],
                        'route'    	 		=>$key[1],
                        'time_in'    	 	=>$key[2],
                        'time_out'          =>$key[3],
                    ]);
                }
                return 1;
            } catch (Exception $e) {
                return 0;
            }
        }else{
            return 0;
        }
    }

    // dev-1.6.0, Ferry, 20160517, Get info transport yang didapat utk ditampilkan
    public function getRouteTransportInfoAttribute()
    {
        return $this->route. ($this->time_in == $this->time_out ? '' : ' ('.$this->time_in.' - '.$this->time_out.')');
    }

}
