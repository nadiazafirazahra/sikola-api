<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class m_sub_section extends Model {

	protected $fillable =  [
    'id',
    'code',
    'name',
    'alias',
    'code_section',
    'npk',
    'created_at',
    'updated_at'
];

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasSection()
    {
        return $this->hasOne('App\m_section', 'code', 'code_section');
    }

	public static function array_to_db($array_data){
        $total=sizeof($array_data);
        if($total>0){
            try {
                foreach ($array_data as $value) {
                    $key=explode(';',$value);
                    self::create([
                        'code'     			=>$key[0],
                        'name'    	 		=>$key[1],
                        'code_section'   =>$key[2],
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
