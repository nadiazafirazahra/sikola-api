

namespace App\Models;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;// Add Entrust trait

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'npk',
        'nama',
        'email',
        'password',
        // 'status',                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                            => $request->role,
        'status_user',
        'remember_token',
        'created_at',
        'updated_at',
        'limit_mp',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token'
    ];

    // dev-2.0, Ferry, 20160823, Shortcut relationship asosiasi agar lebih elok Eloquentnya :)
    public function hasEmployee()
    {
        return $this->hasOne(
            'App\m_employee',
             'npk',
             'npk');
    }

    public static function search_sub_section($npk)
    {
        return self::select('m_employees.sub_section')
            ->join('m_employees', 'm_employees.npk', '=', 'users.npk')
            ->where('users.npk', $npk)
            ->get();
    }

    public function specialLimitHistories()
    {
        return $this->hasMany('App\m_spesial_limit_histories', 'user_id');
    }

    public function overRequestHistories()
    {
        return $this->hasMany('App\m_over_request_histories', 'user_id');
    }

    public function allowMpLog()
    {
        return $this->hasMany('App\t_approved_limit_spesial_log', 'allowed_by', 'npk');
    }
}
