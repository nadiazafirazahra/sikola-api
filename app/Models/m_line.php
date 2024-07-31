<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class m_line extends Model {

	//
	protected $fillable = [
		'id',
		'line_code',
		'line_name',
		'line_alias',
		'section_id',
		'remember_token',
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
                        'line_code'     	=>$key[0],
                        'line_name'    	 	=>$key[1],
                        'line_alias'   	 	=>$key[2],
                        'sub_section_code'  =>$key[3],
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

}
