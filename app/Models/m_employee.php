<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class m_employee extends Model
{
    protected $fillable = [
        'npk', 'nama', 'line_code', 'sub_section', 'occupation', 'transport', 'status_emp', 'employment_status'
    ];

    public function t_spkl()
    {
        return $this->hasMany(t_spkl::class, 'npk', 'npk');
    }



	// protected $fillable =  [
	// 'npk',
	// 'nama',
    // 'line_code',
	// 'sub_section',
	// 'occupation',
	// 'transport',
    // 'status_emp',
    // 'employment_status',
    // //untuk blocking quota per mp
    // 'quota_used_1',
    // 'quota_remain_1',
    // 'quota_used_2',
    // 'quota_remain_2',
    // 'quota_used_3',
    // 'quota_remain_3',
    // 'quota_used_4',
    // 'quota_remain_4',
    // 'quota_used_5',
    // 'quota_remain_5',
    // 'quota_used_6',
    // 'quota_remain_6',
    // 'quota_used_7',
    // 'quota_remain_7',
    // 'quota_used_8',
    // 'quota_remain_8',
    // 'quota_used_9',
    // 'quota_remain_9',
    // 'quota_used_10',
    // 'quota_remain_10',
    // 'quota_used_11',
    // 'quota_remain_11',
    // 'quota_used_12',
    // 'quota_remain_12'
	// ];

    // dev-2.2, Ferry, 20160907, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasManySubSection()
    {
        return $this->hasMany('App\m_sub_section', 'code', 'sub_section');
    }

    // dev-2.2, Ferry, 20160911, conversion
    public function getQuotaRoundedAttribute($value) {
        return round($value / 60, 2);
    }

    // dev-2.2, Ferry, 20160907, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasDivision()
    {
        return $this->hasOne('App\m_division', 'code', 'sub_section');
    }

    // dev-2.2, Ferry, 20160907, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasSubSection()
    {
        return $this->hasOne('App\m_sub_section', 'code', 'sub_section');
    }

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasDepartment()
    {
        return $this->hasOne('App\m_department', 'code', 'sub_section');
    }

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasSection()
    {
        return $this->hasOne('App\m_section', 'code', 'sub_section');
    }

    // hotfix-3.5.7, Handika, 20190911, other relation untuk mengatasi supervisor untuk banyak section
    public function hasManySection()
    {
        return $this->hasMany('App\m_section', 'npk', 'npk');
    }

	public static function array_to_db($array_data){
        $total=sizeof($array_data);
        if($total>0){
            try {
                foreach ($array_data as $value) {
                    $key=explode(';',$value);
                    self::create([
                        'npk'     			=>$key[0],
                        'nama'    	 		=>$key[1],
                        'sub_section'    	=>$key[2],
                        'occupation'    	=>$key[3],
                        'transport'    	 	=>$key[4],
                        'status_emp'            =>$key[5],
                        'employment_status' =>$key[6],
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

    // dev-1.8, Ferry, 20160630, Mendapatkan sub section dari table employee
    public static function getSubSection ($npk) {
        return self::where('m_employees.npk', $npk)->pluck('sub_section');
    }

    // dev-1.8, Ferry, 20160630, Mendapatkan section dari table employee
    public static function getSection ($npk) {
        return self::where('m_employees.npk', $npk)
                    ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                    ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                    ->pluck('m_sections.code');
    }

    // dev-1.8, Ferry, 20160630, Mendapatkan department dari table employee
    public static function getDepartment ($npk) {
        return self::where('m_employees.npk', $npk)
                    ->join('m_sub_sections', 'm_sub_sections.code', '=', 'sub_section' )
                    ->join('m_sections', 'm_sections.code', '=', 'm_sub_sections.code_section' )
                    ->join('m_departments', 'm_departments.code', '=', 'm_sections.code_department' )
                    ->pluck('m_departments.code');
    }

    public static function getnameemployee($npk)
    {
        $name = self::where('npk',$npk)
                        ->select('nama')
                        ->first();
        return $name;
    }
}
