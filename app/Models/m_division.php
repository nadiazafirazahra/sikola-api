<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

class m_division extends Model {

	protected $fillable =  [
        'id',
        'code',
        'name',
        'npk',
        'created_at',
        'updated_at',
        'code_director'
    ];

	public static function array_to_db($array_data){
        $total=sizeof($array_data);
        if($total>0){
            try {
                foreach ($array_data as $value) {
                    $key=explode(';',$value);
                    self::create([
                        'code'     =>$key[0],
                        'name'     =>$key[1],
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

    // has many department
    public function hasManyDepartment()
    {
        return $this->hasMany('App\m_department', 'code_division', 'code');
    }

    // dev-2.2, Ferry, 20160911, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasManySubSection()
    {
        // hasManyThrough tidak bisa tanpa ada relation table menggunakan id sbg foreign-key nya
        // Ini modif sendiri :) Mengembalikan nilai semua sub section
        // return $this->hasManyThrough('App\m_sub_section', 'App\m_section', 'code_department', 'code_section', 'code');

        $department_codes = $this->hasMany('App\m_department', 'code_division', 'code')->lists('code');
        $section_codes = m_section::whereIn('code_department', $department_codes)->lists('code');
        return m_sub_section::whereIn('code_section', $section_codes)->get();
    }

    public function specialLimit()
    {
        return $this->hasOne('App\m_spesial_limits', 'sub_section', 'code');
    }

}
