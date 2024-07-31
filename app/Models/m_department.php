<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class m_department extends Model
{
    use HasFactory;

	protected $fillable =  [
        'code',
        'name',
        'code_division',
        'npk'
    ];

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasDivision()
    {
        return $this->hasOne('App\m_division', 'code', 'code_division');
    }

    // dev-2.2, Ferry, 20160907, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasManySection()
    {
        return $this->hasMany('App\m_section', 'code_department', 'code');
    }

    // dev-2.2, Ferry, 20160908, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasManySubSection()
    {
        // hasManyThrough tidak bisa tanpa ada relation table menggunakan id sbg foreign-key nya
        // Ini modif sendiri :) Mengembalikan nilai semua sub section
        // return $this->hasManyThrough('App\m_sub_section', 'App\m_section', 'code_department', 'code_section', 'code');

        $section_codes = $this->hasMany('App\m_section', 'code_department', 'code')->lists('code');
        return m_sub_section::whereIn('code_section', $section_codes)->get();
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
                        'code_division'     =>$key[2],
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
