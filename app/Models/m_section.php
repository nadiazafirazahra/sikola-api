<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class m_section extends Model {

	protected $fillable =  [
        'id',
        'code',
        'name',
        'alias',
        'code_department',
        'npk',
        'npk_admin',
        'created_at',
        'updated_at'
    ];

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasDepartment()
    {
        return $this->hasOne('App\m_department', 'code', 'code_department');
    }

    // dev-2.2, Ferry, 20160907, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasManySubSection()
    {
        return $this->hasMany('App\m_sub_section', 'code_section', 'code');
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
                        'code_department'   =>$key[2],
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
