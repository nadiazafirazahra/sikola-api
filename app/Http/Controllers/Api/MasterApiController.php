<?php

namespace App\Http\Controllers\Api;

use line;
use Excel;
use Config;
use Exception;
use App\CsvHelper;
use Carbon\Carbon;
use App\Models\User;
use App\Http\Requests;
use App\Models\m_section;
use App\Models\m_category;
use App\Models\m_division;
use App\Models\m_employee;
use App\Models\m_transport;
use App\Models\m_department;
use App\Models\m_occupation;
use Illuminate\Http\Request;
use App\Models\m_open_access;
use App\Models\m_sub_section;
use App\Models\m_quota_department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\MBreakOtResource;
use App\Models\m_over_request_histories;
use App\Http\Resources\MDirectorResource;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\Console\Input\Input;
use App\Http\Resources\MDepartmentsResource;
use App\Models\t_approved_limit_spesial_log;
use App\Models\m_spesial_limit_histories;
use App\Models\m_holiday;//dev-1.7 by andre, m_holiday
use App\Models\m_quota_add; //dev-1.6 by merio,quota used
use App\Models\m_quota_used; //dev-1.6 by andre,quota used
use App\Models\m_line; //dev-3.2.1 by Ario, add master line
use App\Models\m_quota_real; //dev-1.6 by andre,quota original
use App\Models\quota_add_itd; //dev-1.6 by merio,quota add itd
use App\Models\m_director; // v1.5.4 by Merio, 20160418, use director
use App\Models\m_quota_request; //hotfix-1.9.4, by Merio, quota request
use App\Models\m_spesial_limits; //dev-3.4.0, by Handika, quota spesial limit
use App\Models\m_break_ot; // hotfix-1.5.21, by Merio Aji, 20160525, add master break
use App\t_spkl_details; //dev-3.4.0, by Fahrul Sudarusman, 20171214, pengecekan di tabel t_detail_spkl
use App\Models\t_approved_limit_spesial; //dev-3.4.0, by Fahrul Sudarusman, 20171207, approved quota limit spesial


class MasterApiController extends Controller {

	/*
	|--------------------------------------------------------------------------
	| Master Controller
	|--------------------------------------------------------------------------
	|
	| v1.0 by Merio, 20160111, Mengatur semua terkait create, read, update, delete
	| untuk master data divisi, department, section, sub section, jabatan, transport,
	| category, karyawan.
	*/
	// ************* Master Data User Here **************** //

	//v1.0 by Merio, 20160202, method view user
	public function transport_view()
	{
		$check_transport = DB::select('select *,m_sub_sections.name as name_sub_section, m_sections.name as name_section,
			m_departments.name as name_department,
			m_employees.npk as npk_emp from t_spkl_details
			join m_employees on (m_employees.npk = t_spkl_details.npk)
			join m_sub_sections on (m_employees.sub_section = m_sub_sections.code)
			join m_sections on (m_sections.code = m_sub_sections.code_section)
			join m_departments on (m_departments.code = m_sections.code_department)
			join m_transports on (m_transports.code = m_employees.transport)
			where
			(t_spkl_details.status = "1" or t_spkl_details.status = "2" or t_spkl_details.status = "3"
			or t_spkl_details.status = "4")');
        $transport = new Collection($check_transport);
		return response()->json($transport);
	}

	public function user_view()
	{
		$user2 = Auth::user();
		if ($user2->role == "Supervisor") {
			$user        = User::select('*','users.id as id_user','users.npk as npk_user',
								'm_departments.alias as name_department','m_sections.alias as name_section')
								->join('m_employees','m_employees.npk','=','users.npk')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->where('users.role','=','Leader')
								->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
								->where('m_sections.npk','=',$user2->npk)
								->get();
			$check_limit = "";
		} elseif ($user2->role == "Ka Dept") {
			$user        = User::select('*','users.id as id_user','users.npk as npk_user',
								'm_departments.alias as name_department','m_sections.alias as name_section')
								->join('m_employees','m_employees.npk','=','users.npk')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
								->where('users.role','=','Leader')
								->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
								->where('m_departments.npk','=',$user2->npk)
								->get();
			$check_limit = m_open_access::where('npk_user','=',$user2->npk)
										->where('is_active','=','1')
										->get();
		} elseif ($user2->role == "GM") {
			if ($user2->npk == "000006") {
				$user        = User::select('*','users.id as id_user','users.npk as npk_user',
									'm_departments.alias as name_department','m_sections.alias as name_section')
									->join('m_employees','m_employees.npk','=','users.npk')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('users.role','=','Leader')
									->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
									->where('m_divisions.code','=',"PRD")
									->get();
			} else if ($user2->npk == "000007") {
				$user        = User::select('*','users.id as id_user','users.npk as npk_user',
									'm_departments.alias as name_department','m_sections.alias as name_section')
									->join('m_employees','m_employees.npk','=','users.npk')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('users.role','=','Leader')
									->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
									->where('m_divisions.code','=',"ENG")
									->get();
			} else if ($user2->npk == "000050") {
				$user        = User::select('*','users.id as id_user','users.npk as npk_user',
									'm_departments.alias as name_department','m_sections.alias as name_section')
									->join('m_employees','m_employees.npk','=','users.npk')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('users.role','=','Leader')
									->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
									->where('m_divisions.code','=',"ENG")
									->get();
			}else if ($user2->npk == "000014") {
				$user        = User::select('*','users.id as id_user','users.npk as npk_user',
									'm_departments.alias as name_department','m_sections.alias as name_section')
									->join('m_employees','m_employees.npk','=','users.npk')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('users.role','=','Leader')
									->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
									->where('m_divisions.code','=',"EPP")
									->get();
			}
			$check_limit = "";
		} elseif ($user2->role == "HR Admin") {
			$user        = User::select('*','users.id as id_user','users.npk as npk_user')
								->join('m_employees','m_employees.npk','=','users.npk')
								->where('m_employees.status_emp',1) //hotfix-3.1.1, by yudo, 20170427, karyawan yang masih aktif
								->get();
			$check_limit = "";
		}
		$m_employees = m_employee::whereNotIn('npk', function($q){
          							$q->select('npk')
            						->from('users');
      								})->get();

                                    $data = [
                                        'master_data' => 'user_view',
                                        'm_employees' => $m_employees,
                                        'user' => $user,
                                        'user2' => $user2,
                                        'check_limit' => $check_limit
                                    ];

                                      return response()->json($data);

	}
	//hotfix-1.5.10, by Merio Aji, 20160403, Add reset pwd
	public function user_reset_pwd($id)
    {
        $password = bcrypt('aiia');
        $user = User::findOrFail($id);
        $user->password = $password;
        $user->save();
        Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User password was successfully reset to "aiia"');
        return response()->json([
            'status' => 'success',
            'message' => 'User password was successfully reset to "aiia"',
            'user_id' => $user->id
          ]);
    }
    //hotfix-1.5.18, by Merio Aji, 20160403, Reset pwd user
	public function reset_pwd()
    {
        $user    	=Auth::user();
        $password 	= bcrypt('aiia');
        $user 		= User::findOrFail($user->id);
        $user->password = $password;
        $user->save();
        Session::flash('flash_type','alert-success');
        \session_save_path()::flash('flash_message','Your password was successfully reset to "aiia"');
        return response()->json([
            'message' => 'Your password was successfully reset',
            'user_id' => $user->id
          ]);
    }
	//v1.0 by Merio, 20160102, method create user
	public function user_create()
	{

        $input = Request::all();
        $role = $input['role'];

		if ($role == "HR Admin" || $role == "General Affair") {
			$npk 		= $input['username'];
			$username 	= $input['username'];
		} else {
			$npk 	= $input['npk'];
			$m_employee 	 = m_employee::where('npk', $npk)->get();
			foreach ($m_employee as $m_employee) {
				$username  = $m_employee->nama;
			}
		}
		$user 	   	     = new User;
		$user->npk 		 = $npk;
		$user->nama 	 = $username;
		$user->password  = bcrypt($input['password']);
		$user->email 	 = $input['email'];
		$user->role      = $input['role'];
		$user->ot_par    = "1";
		//hotfix-2.3.5, by Merio, 20161119, memberi nilai awal untuk limit ot late
		$user->limit_mp  = "0";
		//hotfix-2.2.9, by Merio, 20161026, memberi nilai awal status user saat pertama create
		$user->status_user = "1";
		$user->save();

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User '.$username.' was successfully created');
		return response()->json([
            'message' => 'User was successfully created',
            'user' => $user
        ]);
	}
	//v1.0 by Merio, 20160202, method delete user
	public function user_delete($id)
	{
	 	User::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User was successfully deleted');
        return response()->json([
            'message' => 'User was successfully deleted',
            'user' => $id
        ]);
	}
	//v1.0 by Merio, 20160202, method form update user
	public function user_update($id)
	{
	 	$user    	     = User::select('*','users.id as id_user')->where('id',$id)->get();
	 	$user_all        = User::select('*','users.id as id_user')->where('id',$id)->get();
		$m_employee_update     = m_employee::all();

        $data = [
            'user' => $user,
            'user_all' => $user_all,
            'm_employee_update' => $m_employee_update
        ];

        return response()->json($data);
	}

	//hotfix-2.3.5, by Merio, 20161118, menambahkan inputan limit saat open akses spkl terlambat
	public function user_ot_open_par($id) {
		$user        = Auth::user();
		$check_limit = m_open_access::where('npk_user','=',$user->npk)
									->where('is_active','=','1')
									->get();
		$m_employee = User::select('*','users.id as id_user')
							->join('m_employees','m_employees.npk','=','users.npk')
							->where('users.id',$id)
							->get();

                            $data = [
                                'check_limit' => $check_limit,
                                'm_employee' => $m_employee
                            ];

                            return response()->json($data);
	}

	//v1.0 by Merio, 20160102, method save update user
	public function user_ot_open()
	{
		$input 			= Request::all();
		$id_user 		= $input['id_user'];

		$jumlah_limit 	= $input['jumlah_limit'];
		$limit_mp 		= $input['limit_mp'];
		$total_limit 	= $limit_mp+$jumlah_limit;

		$user   = Auth::user();
		if ($user->role == "Ka Dept") {
			$check_limit = m_open_access::where('npk_user','=',$user->npk)
										->where('is_active','=','1')
										->get();
			foreach ($check_limit as $check_limit) {
				$limit 		= $check_limit->limit;
				$id_access 	= $check_limit->id;
			}
			if ($limit > 0) {
				if ($jumlah_limit <= $limit) {
					$user 	= User::findOrFail($id_user);
					$user->ot_par 		= "2";
					$user->limit_mp     = $total_limit;
					$user->save();

					$sisa_limit 			= $limit-$jumlah_limit;
					$kurang_limit 			= m_open_access::findOrFail($id_access);
					$kurang_limit->limit 	= $sisa_limit;
					$kurang_limit->save();
					Session::flash('flash_type','alert-success');
			        Session::flash('flash_message','Sukses, pembukaan akses pembuatan overtime terlambat berhasil, sisa limit anda '.$sisa_limit.'');
					return response()->json([
                        'status' => 'success',
                        'message' => 'pembukaan akses pembuatan overtime terlambat berhasil',
                        'sisa_limit' => $sisa_limit
                ]);
				} else {
					Session::flash('flash_type','alert-danger');
			        Session::flash('flash_message','Error, limit pembukaan akses overtime terlambat anda tidak mencukupi, silakan hubungi HR Personal Admin untuk menambah limit overtime terlambat');
					return response()->json([
                        'status' => 'Error',
                        'message' => 'limit pembukaan akses overtime terlambat anda tidak mencukupi, silakan hubungi HR Personal Admin untuk menambah limit overtime terlambat'
                    ]);
				}
			} else {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, limit pembukaan akses overtime terlambat anda tidak mencukupi, silakan hubungi HR Personal Admin untuk menambah limit overtime terlambat');
				return response()->json([
                    'status' => 'Error',
                    'message' => 'limit pembukaan akses overtime terlambat anda tidak mencukupi, silakan hubungi HR Personal Admin untuk menambah limit overtime terlambat'
                ]);
			}
		} else {
			$user 	= User::findOrFail($id_user);
			$user->ot_par 		= "2";
			$user->limit_mp     = $total_limit;
			$user->save();
			Session::flash('flash_type','alert-success');
		    Session::flash('flash_message','Sukses, pembukaan akses pembuatan overtime terlambat berhasil');
			return response()->json([
                'status' => 'success',
                'message' => 'pembukaan akses pembuatan overtime terlambat berhasil'
            ]);
		}
	}

	//hotfix-2.3.5, by Merio, 20161118, menambahkan inputan limit saat closed akses spkl terlambat
	public function user_ot_closed_par($id) {
		$user 		 = Auth::user();
		$check_limit = m_open_access::where('npk_user','=',$user->npk)
									->where('is_active','=','1')
									->get();
		$m_employee = User::select('*','users.id as id_user')
							->join('m_employees','m_employees.npk','=','users.npk')
							->where('users.id',$id)
							->get();

                            $data = [
                                'check_limit' => $check_limit,
                                'm_employee' => $m_employee
                            ];

                            return response()->json($data);
	}

	public function user_ot_closed()
	{
		$input          = Request::all();
		$id_user 		= $input['id_user'];

		$jumlah_limit 	= $input['limit'];
		$limit_mp 		= $input['limit_mp'];
		$total_limit 	= $limit_mp-$jumlah_limit;

		$user   = Auth::user();
		if ($user->role == "Ka Dept") {
			$check_limit = m_open_access::where('npk_user','=',$user->npk)
										->where('is_active','=','1')
										->get();
			foreach ($check_limit as $check_limit) {
				$limit 		= $check_limit->limit;
				$id_access 	= $check_limit->id;
			}
			if ($jumlah_limit <= $limit_mp) {
				$user 	= User::findOrFail($id_user);
				if ($total_limit == 0 ) {
					$user->ot_par 		= "1";
				}
				$user->limit_mp 		= $total_limit;
				$user->save();

				$sisa_limit 			= $limit+$jumlah_limit;
				$kurang_limit 			= m_open_access::findOrFail($id_access);
				$kurang_limit->limit 	= $sisa_limit;
				$kurang_limit->save();

				Session::flash('flash_type','alert-success');
		        Session::flash('flash_message','Sukses, penutupan akses pembuatan overtime terlambat berhasil');
				return response()->json([
                    'status' => 'success',
                    'message' => 'penutupan akses pembuatan overtime terlambat berhasil']);
			} else {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, anda tidak bisa mengurangi limit melebihi limit dari karyawan tersebut');
				return response()->json([
                    'status' => 'Error',
                    'message' => 'anda tidak bisa mengurangi limit melebihi limit dari karyawan tersebut'
                ]);
			}
		} else {
			if ($jumlah_limit <= $limit_mp) {
				$user 	= User::findOrFail($id_user);
				if ($total_limit == 0 ) {
					$user->ot_par 		= "1";
				}
				$user->limit_mp 		= $total_limit;
				$user->save();
				Session::flash('flash_type','alert-success');
		        Session::flash('flash_message','Sukses, penutupan akses pembuatan overtime terlambat berhasil');
				return response()->json([
                    'status' => 'success',
                    'message' => 'penutupan akses pembuatan overtime terlambat berhasil'
                ]);
			} else {
				Session::flash('flash_type','alert-danger');
		        Session::flash('flash_message','Error, anda tidak bisa mengurangi limit melebihi limit dari karyawan tersebut');
				return response()->json([
                    'status' => 'Error',
                    'message' => 'anda tidak bisa mengurangi limit melebihi limit dari karyawan tersebut']);
			}
		}
	}

	public function user_update_save()
	{
		$input = Request::all();
		$id 	= $input['id'];
		$npk 	= $input['npk'];
		$user 			= User::findOrFail($id);
		$user->npk 		= $npk;
		$user->email	= $input['email'];
		$user->role 	= $input['role'];
		$user->nama 	= $input['username'];
		$user->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User '.$npk.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'User '.$npk.' was successfully updated']);
	}
	public function user_save_edit_password()
	{

        $input = Request::all();
            $pwd1=$input['password1'];
            $pwd2=$input['password2'];
            $pwd3=$input['password3'];
            $pwd4=bcrypt($pwd1);
            $pwd5=bcrypt($pwd2);
            $pwd6=bcrypt($pwd3);
            $user =Auth::user();
          	if ($pwd1 == NULL or $pwd2 == NULL or $pwd3 == NULL){
          		Session::flash('flash_type','alert-danger');
        		Session::flash('flash_message','Error, there columns that you have not fill');
                return response()->json([
                    'status' => 'Error',
                    'message' => 'There are empty fields']);
          	} else {
            	if (Hash::check($pwd1, $user->password)){
            		if ($pwd2 == $pwd3) {
        				$user->password=$pwd6;
        				$user->save;
        				Session::flash('flash_type','alert-success');
 				        Session::flash('flash_message','Password was successfully updated');
        				return response()->json([
                            'status' => 'success',
                            'message' => 'Password updated successfully']) ;
		            } else {
        		    	Session::flash('flash_type','alert-danger');
        				Session::flash('flash_message','Error, your administrator password incorrect');
                        return response()->json([
                            'status' => 'Error',
                            'message' => 'your administrator password incorrect']);
            		}
            	} else {
            		Session::flash('flash_type','alert-danger');
        			Session::flash('flash_message','Error, your new password combination do not match');
                    return response()->json([
                        'status' => 'Error',
                        'message' => 'your new password combination do not match']);
            	}
        	}
	}

	//v1.5.19 by Merio, 20160518, method non active user
	public function user_non_active($id)
	{
	 	$user 			 	= User::findOrFail($id);
		$user->status_user	= "2";
		$user->save();
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User was successfully non active');
        return response()->json([
            'status' => 'success',
            'message' => 'User successfully non active']);
	}
	//v1.5.19 by Merio, 20160518, method active user
	public function user_active($id)
	{
	 	$user 			 	= User::findOrFail($id);
		$user->status_user	= "1";
		$user->save();
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','User was successfully active');
        return response()->json([
            'status' => 'success',
            'message' => 'User successfully activated']);
	}

	public function user_profile()
	{
		$user 		= Auth::user();
		if ($user->role == "Leader") {
			$profile 	= m_employee::select('*','m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('users','m_employees.npk','=','users.npk')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('m_employees.npk',$user->npk)
									->groupby('m_employees.npk')
									->get();
		} else if ($user->role == "Supervisor") {
			$profile 	= m_employee::select('*','m_employees.npk as npk_emp',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('users','m_employees.npk','=','users.npk')
									->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('m_employees.npk',$user->npk)
									->groupby('m_employees.npk')
									->get();
		} else if ($user->role == "Ka Dept") {
			$profile 	= m_employee::select('*','m_employees.npk as npk_emp',
									'm_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('users','m_employees.npk','=','users.npk')
									->leftjoin('m_departments','m_departments.code','=','m_employees.sub_section')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
									->where('m_employees.npk',$user->npk)
									->groupby('m_employees.npk')
									->get();
		} else if ($user->role == "GM") {
			$profile 	= m_employee::select('*','m_employees.npk as npk_emp',
									'm_divisions.name as division')
									->leftjoin('users','m_employees.npk','=','users.npk')
									->leftjoin('m_divisions','m_divisions.code','=','m_employees.sub_section')
									->where('m_employees.npk',$user->npk)
									->groupby('m_employees.npk')
									->get();
		}
		return response()->json($profile);
	}

	public function member_sub_section()
	{
		$user 		= Auth::user();
		$check_sub_section = m_employee::where('npk',$user->npk)->get();
		foreach ($check_sub_section as $check_sub_section) {
			$sub_section = $check_sub_section->sub_section;
		}
		$m_employee = m_employee::select('*','m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
								'm_sections.name as section','m_departments.name as department',
								'm_divisions.name as division','m_employees.occupation as occupation_emp')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->leftJoin('m_departments','m_departments.code','=','m_sections.code_department')
								->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
								->where('m_employees.status_emp','1')
								->where('m_employees.sub_section',$sub_section)
								->get();
		//hotfix-2.0.5, by Merio, 20160830, query untuk melihat approval
		$check_status = m_sub_section::select('m_sections.npk as npk_section','m_departments.npk as npk_department',
										'm_divisions.npk as npk_division')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->join('m_divisions','m_divisions.code','=','m_departments.code_division')
										->where('m_sub_sections.code',$sub_section)
										->get();

                                        $data = [
                                            'employee' => $m_employee,
                                            'approval_status' => $check_status
                                        ];

                                        return response()->json($data);
	}

	public function member_department() {
		$user 		= Auth::user();

		if ($user->role == 'Leader') {
			$check_department = m_employee::select('m_departments.code as code_department')
											->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk',$user->npk)
											->get();
			foreach ($check_department as $check_department) {
				$code_department = $check_department->code_department;
			}
			$m_employee = m_employee::select('*','m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
	        						->where('m_employees.status_emp','=','1')
									->where('m_departments.code',$code_department)
									->get();
		} else if ($user->role == 'Supervisor') {
			$check_department = m_employee::select('m_departments.code as code_department')
											->leftjoin('m_sections','m_sections.code','=','m_employees.sub_section')
											->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_employees.npk',$user->npk)
											->get();
			foreach ($check_department as $check_department) {
				$code_department = $check_department->code_department;
			}
			$m_employee = m_employee::select('*','m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
	        						->where('m_employees.status_emp','=','1')
									->where('m_departments.code',$code_department)
									->get();
		} else if ($user->role == 'Ka Dept') {
			$check_department = m_employee::select('m_departments.code as code_department')
											->leftjoin('m_departments','m_departments.code','=','m_employees.sub_section')
											->where('m_employees.npk',$user->npk)
											->get();
			foreach ($check_department as $check_department) {
				$code_department = $check_department->code_department;
			}

			$m_employee = m_employee::select('*','m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
	        						->where('m_employees.status_emp','=','1')
									->where('m_departments.code',$code_department)
									->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
									->get();
		} else if ($user->role == 'GM') {
			$check_division = m_employee::join('m_divisions','m_divisions.code','=','m_employees.sub_section')
										->where('m_employees.npk','=',$user->npk)
										->first();
			$m_employee = m_employee::select('m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division','m_employees.occupation','m_employees.nama')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
	        						->where('m_employees.status_emp','=','1')
									->where('m_divisions.code',$check_division->code)
									->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
									->get();
		} else if ($user->role == 'HR Admin') {
			$m_employee = m_employee::select('m_employees.npk as npk_emp','m_sub_sections.name as sub_section',
									'm_sections.name as section','m_departments.name as department',
									'm_divisions.name as division','m_employees.occupation','m_employees.nama')
									->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
									->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
									->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
									->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
	        						->where('m_employees.status_emp','=','1')
	        						->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                		})
									->get();
		}
		return response()->json($m_employee);
	}

	// ************* Master Data Employee Here **************** //

	//v1.0 by Merio, 20160102, method view employee
	public function m_employee_view()
	{
		$m_employee_all =
			m_employee::select('m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport','m_sections.alias as name_section',
				 				'm_sub_sections.name as name_sub_section', 'm_departments.name as name_department',
				 				'm_divisions.name as name_division', 'm_employees.npk as employee_npk',
				 				'm_employees.*')
						->leftJoin('m_occupations','m_occupations.code','=','m_employees.occupation')
						->leftJoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftJoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftJoin('m_departments','m_departments.code','=','m_sections.code_department')
						->leftJoin('m_divisions','m_divisions.code','=','m_departments.code_division')
						->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
						->whereIn ('m_employees.occupation', ['OPR', 'LDR'])
						->orderBy('m_employees.npk');

		$m_employee_spv =
			m_employee::select('m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport','m_sections.name as name_section',
				 				DB::raw('"-empty-" as name_sub_section'), 'm_departments.name as name_department',
				 				'm_divisions.name as name_division','m_employees.npk as employee_npk',
				 				'm_employees.*')
						->leftJoin('m_occupations','m_occupations.code','=','m_employees.occupation')
						->leftJoin('m_sections','m_sections.code','=','m_employees.sub_section')
						->leftJoin('m_departments','m_departments.code','=','m_sections.code_department')
						->leftJoin('m_divisions','m_divisions.code','=','m_departments.code_division')
						->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
						->where('m_employees.occupation', 'SPV')
						->orderBy('m_employees.npk');

		$m_employee_kdp =
			m_employee::select('m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport', DB::raw('"-empty-" as name_section'),
				 				DB::raw('"-empty-" as name_sub_section'), 'm_departments.name as name_department',
				 				'm_divisions.name as name_division','m_employees.npk as employee_npk',
				 				'm_employees.*')
						->leftJoin('m_occupations','m_occupations.code','=','m_employees.occupation')
						->leftJoin('m_departments','m_departments.code','=','m_employees.sub_section')
						->leftJoin('m_divisions','m_divisions.code','=','m_departments.code_division')
						->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
						->where('m_employees.occupation','KDP')
						->orderBy('m_employees.npk');

		$m_employee_gmr =
			m_employee::select('m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport', DB::raw('"-empty-" as name_section'),
				 				DB::raw('"-empty-" as name_sub_section'), DB::raw('"-empty-" as name_department'),
				 				'm_divisions.name as name_division','m_employees.npk as employee_npk',
				 				'm_employees.*')
						->leftJoin('m_occupations','m_occupations.code','=','m_employees.occupation')
						->leftJoin('m_divisions','m_divisions.code','=','m_employees.sub_section')
						->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
						->where('m_employees.occupation','GMR')
						->orderBy('m_employees.npk');

		$m_employee_all = $m_employee_all->unionAll($m_employee_spv->getQuery());
		$m_employee_all = $m_employee_all->unionAll($m_employee_kdp->getQuery());
		$m_employee_all = $m_employee_all->unionAll($m_employee_gmr->getQuery());
		$m_employee = $m_employee_all->get();

		$m_sub_section 	= m_sub_section::all();
		$m_section 		= m_section::all();
		$m_department 	= m_department::all();
		$m_division 	= m_division::all();
		$m_occupation 	= m_occupation::all();

        $data = [
            'employees' => $m_employee,
            'sub_sections' => $m_sub_section,
            'sections' => $m_section,
            'departments' => $m_department,
            'divisions' => $m_division,
            'occupations' => $m_occupation,
        ];

        return response()->json($data);
	}
	//v1.0 by Merio, 20160102, method create employee
	public function m_employee_create()
	{
		$input                              = Request::all();
		$m_employee 	   					= new m_employee;
		$nama 								= $input['nama'];
		$m_employee->npk 		 			= $input['npk'];
		$m_employee->nama 		 			= $nama;
		$m_employee->status_emp	 			= 1;
		$m_employee->occupation	 			= $input['occupation'];
		$m_employee->employment_status	 	= $input['employment_status'];
		if ($input['occupation'] == "LDR" || $input['occupation'] == "OPR") {
			$m_employee->sub_section = $input['sub_section'];
		} else if ($input['occupation'] == "SPV") {
			$m_employee->sub_section = $input['section'];
		} else if ($input['occupation'] == "KDP") {
			$m_employee->sub_section = $input['department'];
		} else if ($input['occupation'] == "GMR") {
			$m_employee->sub_section = $input['division'];
		}
		$m_employee->transport	 			= $input['transport'];

		//hotfix-3.1.3, by Yudo, 20170518, quota 0 ketika add employee baru
		$m_employee->quota_used_1 			= 0;
		$m_employee->quota_used_2 			= 0;
		$m_employee->quota_used_3 			= 0;
		$m_employee->quota_used_4 			= 0;
		$m_employee->quota_used_5 			= 0;
		$m_employee->quota_used_6 			= 0;
		$m_employee->quota_used_7 			= 0;
		$m_employee->quota_used_8 			= 0;
		$m_employee->quota_used_9 			= 0;
		$m_employee->quota_used_10 			= 0;
		$m_employee->quota_used_11 			= 0;
		$m_employee->quota_used_12 			= 0;

		$m_employee->quota_remain_1 		= 0;
		$m_employee->quota_remain_2 		= 0;
		$m_employee->quota_remain_3 		= 0;
		$m_employee->quota_remain_4 		= 0;
		$m_employee->quota_remain_5 		= 0;
		$m_employee->quota_remain_6 		= 0;
		$m_employee->quota_remain_7 		= 0;
		$m_employee->quota_remain_8 		= 0;
		$m_employee->quota_remain_9 		= 0;
		$m_employee->quota_remain_10 		= 0;
		$m_employee->quota_remain_11 		= 0;
		$m_employee->quota_remain_12 		= 0;

		$m_employee->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee '.$nama.' was successfully created');

        $data = [
            'message' => 'Employee created successfully',
            'employee' => $m_employee,
        ];

        return response()->json($data);
	}
	//v1.0 by Merio, 20160102, method delete employee
	public function m_employee_delete($id)
	{
	 	m_employee::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee was successfully deleted');
        return response()->json([
            'status' =>  'success',
            'message' => 'Employee deleted successfully',
        ]);
	}
	//v1.0 by Merio, 20160102, method form update employee
	public function m_employee_update($id)
	{
	 	$m_employee = m_employee::where('id','=',$id)->get();
	 	foreach ($m_employee as $m_employees) {
	 		$occupation_emp = $m_employees->occupation;
	 	}
	 	if ($occupation_emp == "OPR" || $occupation_emp == "LDR") {
       		$m_employee_update = m_employee::select('*','m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport','m_sections.name as name_section','m_sub_sections.name as name_sub_section',
				 				'm_departments.name as name_department','m_divisions.name as name_division','m_employees.npk as employee_npk')
								->join('m_occupations','m_occupations.code','=','m_employees.occupation')
								->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
								->where ( function ($q) {
			                			$q->where('m_employees.occupation','OPR')
			                    		->orWhere('m_employees.occupation','LDR');
			                			})
								->where('m_employees.id',$id)
								->get();
		}
		else if ($occupation_emp == "SPV") {
			$m_employee_update = m_employee::select('*','m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport','m_sections.name as name_section',
				 				'm_departments.name as name_department','m_divisions.name as name_division','m_employees.npk as employee_npk')
								->join('m_occupations','m_occupations.code','=','m_employees.occupation')
								->join('m_sections','m_sections.code','=','m_employees.sub_section')
								->join('m_departments','m_departments.code','=','m_sections.code_department')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
								->where('m_employees.occupation','=','SPV')
								->where('m_employees.id',$id)
								->get();
		}
		else if ($occupation_emp == "KDP") {
			$m_employee_update = m_employee::select('*','m_employees.id as employee_id','m_occupations.name as name_occupation',
				 				'm_transports.route as route_transport',
				 				'm_departments.name as name_department','m_divisions.name as name_division','m_employees.npk as employee_npk')
								->join('m_occupations','m_occupations.code','=','m_employees.occupation')
								->join('m_departments','m_departments.code','=','m_employees.sub_section')
								->join('m_divisions','m_divisions.code','=','m_departments.code_division')
								->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
								->where('m_employees.occupation','=','KDP')
								->where('m_employees.id',$id)
								->get();
		}
		else if ($occupation_emp == "GMR") {
			$m_employee_update = m_employee::select('*','m_employees.id as employee_id',
							'm_occupations.name as name_occupation','m_transports.route as route_transport',
							'm_divisions.name as name_division','m_employees.npk as employee_npk')
							->leftjoin('m_occupations','m_occupations.code','=','m_employees.occupation')
							->leftjoin('m_divisions','m_divisions.code','=','m_employees.sub_section')
							->leftJoin('m_transports','m_transports.code','=','m_employees.transport')
							->where('m_employees.occupation','=','GMR')
							->where('m_employees.id',$id)
							->get();
		}

		$m_sub_section_update 	= m_sub_section::all();
		$m_section_update 		= m_section::all();
		$m_department_update 	= m_department::all();
		$m_division_update 		= m_division::all();
		$m_occupation_update 	= m_occupation::all();

        $data = [
            'message' => 'Employee updated successfully',
            'employee' => $m_employee,
        ];

        return response()->json($data);
	}
	//v1.5.19 by Merio, 20160518, method non active employee
	public function m_employee_non_active($id)
	{
	 	$m_employee 			 = m_employee::findOrFail($id);
		$m_employee->status_emp	 = "2";
		$m_employee->save();
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee was successfully non active');
        return response()->json([
            'status' => 'success',
            'message' => 'Employee status updated successfully',
            'employee' => $m_employee,
        ]);
	}
	//v1.5.19 by Merio, 20160518, method active employee
	public function m_employee_active($id)
	{
	 	$m_employee 			 = m_employee::findOrFail($id);
		$m_employee->status_emp	 = "1";
		$m_employee->save();
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee was successfully active');
        return response()->json([
            'status' => 'success',
            'message' => 'Employee status updated successfully',
            'employee' => $m_employee,
    ]);
	}
	//v1.0 by Merio, 20160102, method save update employee
	public function m_employee_update_save()
	{
		$input = Request::all();
		$id 	= $input['id'];
		$nama 	= $input['nama'];
		$m_employee 			 			= m_employee::findOrFail($id);
		$m_employee->npk 		 			= $input['npk'];
		$m_employee->nama 					= $nama;
		$m_employee->occupation	 			= $input['occupation'];
		$m_employee->employment_status	 	= $input['employment_status'];
		if ($input['occupation'] == "LDR" || $input['occupation'] == "OPR") {
			$m_employee->sub_section = $input['sub_section'];
		} else if ($input['occupation'] == "SPV") {
			$m_employee->sub_section = $input['section'];
		} else if ($input['occupation'] == "KDP") {
			$m_employee->sub_section = $input['department'];
		} else if ($input['occupation'] == "GMR") {
			$m_employee->sub_section = $input['division'];
		}
		$m_employee->transport 	 = $input['transport'];
		$m_employee->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Employee '.$nama.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'employee' => $m_employee,
        ]);
	}

	//v1.0 by Merio, 20151230, method import employee
	public function m_employee_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_employee::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
            return response()->json([
                'status' => 'success',
                'message' => 'successfully saved',
            ]);
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		return response()->json([
            'status' => 'Error',
            'message' => 'Failed to save data',
        ]);
      }
	}
	// ************* Master Data Divisi Here **************** //

	//v1.0 by Merio, 20151230, method view divisi
	public function m_division_view()
	{
		$m_division = m_division::select('*','m_divisions.id as id_division','m_divisions.code as code_division',
										'm_divisions.name as name_division','m_directors.name as name_director')
									->leftJoin('m_directors','m_directors.code','=','m_divisions.code_director')
									->orderBy('m_divisions.name')->get();

                                    if ($m_division->isEmpty()) {
                                        return response()->json([
                                            'message' => 'No divisions found',
                                        ], 404);
                                    }

                                    return response()->json($m_division);
	}
	//v1.0 by Merio, 20151230, method create divisi
	public function m_division_create()
	{
		$input              = Request::all();
		$m_division 	   	= new m_division;
		$name 				= $input['name'];
		$m_division->code 	= $input['code'];
		$m_division->name 	= $name;
		$m_division->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Division '.$name.' was successfully created');

            return response()->json([
                'status' => 'success',
                'message' => 'Division created successfully',
                'division' => $m_division,
            ]);
	}
	//v1.0 by Merio, 20151230, method import divisi
	public function m_division_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_division::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => ($result == 1) ? 'successfully saved' : 'No data update',
            'result' => $result,
        ]);
	}
	//v1.0 by Merio, 20151230, method delete divisi
	public function m_division_delete($id)
	{
	 	m_division::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Division was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Division was successfully deleted']);
	}
	//v1.0 by Merio, 20151230, method form update divisi
	public function m_division_update($id)
	{
	 	$m_division 	= m_division::where('id', $id)->get();
	 	$m_division_all = m_division::select('*','m_divisions.id as id_division','m_divisions.code as code_division',
										'm_divisions.name as name_division','m_directors.name as name_director')
									->leftJoin('m_directors','m_directors.code','=','m_divisions.code_director')
									->orderBy('m_divisions.name')->get();

                                    $data = [
                                        'message' => 'Data divisions updated successfully',
                                        'divisions' => $m_division_all,
                                    ];

                                    return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method save update divisi
	public function m_division_update_save()
	{
		$input = Request::all();
		$id 	= $input['id'];
		$name 	= $input['name'];
		$m_division 		= m_division::findOrFail($id);
		$m_division->code 	= $input['code'];
		$m_division->name 	= $name;
		$m_division->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Division '.$name.' was successfully updated');

        $data = [
            'message' => 'Division updated successfully',
            'division' => $m_division,
        ];

		return response()->json($data);
	}


	// ************* Master Data Director Here **************** //

	//v1.5.4 by Merio, 20160418, method view director
	public function m_director_view()
	{
		$m_director = m_director::orderBy('name')->get();
        return response()->json($m_director);
	}
	//v1.5.4 by Merio, 20160418, method create director
	public function m_director_create()
	{
		$input              = Request::all();
		$m_director 	   	= new m_director;
		$name 				= $input['name'];
		$m_director->code 	= $input['code'];
		$m_director->name 	= $name;
		$m_director->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Director '.$name.' was successfully created');
		return response()->json([
            'status' => 'success',
            'message' => 'Director created successfully',
            'director' => $m_director,
        ]);
	}
	//v1.5.4 by Merio, 20160418, method for import director from excel
	public function m_director_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_division::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully Saved',
            ]);
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
            return response()->json([
                'status' => 'Error',
                'message' => 'No data update',
            ]);
	}
}
	//v1.5.4 by Merio, 20160418, method for delete director
	public function m_director_delete($id)
	{
	 	m_director::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Director was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Director was successfully deleted',
        ]);
	}
	//v1.5.4 by Merio, 20160418, method for show update form director
	public function m_director_update($id)
	{
	 	$m_director 	= m_director::where('id', $id)->get();
	 	$m_director_all = m_director::orderBy('name')->get();

        $data = [
            'director' => $m_director,
            'all_directors' => $m_director_all,
        ];

         return response()->json($data);
	}
	//v1.5.4 by Merio, 20160418, method for save update director
	public function m_director_update_save()
	{
		$input  = Request::all();
		$id 	= $input['id'];
		$name 	= $input['name'];
		$m_director 		= m_director::findOrFail($id);
		$m_director->code 	= $input['code'];
		$m_director->name 	= $name;
		$m_director->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Director '.$name.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Director '.$name.' was successfully updated',
            'director' => $m_director,
        ]);
	}

	// ************* Master Data Department Here **************** //

	//v1.0 by Merio, 20151230, method view department
	public function m_department_view()
	{
		$m_department = m_department::select('*','m_departments.id as id_department',
									'm_departments.code as code_department','m_departments.name as name_department',
									'm_divisions.name as name_division','m_departments.alias as alias_department')
									->join('m_divisions','m_departments.code_division','=','m_divisions.code')
									->orderBy('m_departments.name')->get();
		$m_division = m_division::orderBy('name')->get();

        $data = [
            'departments' => $m_department,
            'divisions' => $m_division,
        ];

        return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method create department
	public function m_department_create()
	{
		$input              = Request::all();
		$name_department 	= $input['name'];
		$m_department 		= new m_department;
		$m_department->code				= $input['code'];
		$m_department->name 			= $name_department;
		$m_department->code_division	= $input['code_division'];
		$m_department->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Department '.$name_department.' was successfully created');
		return response()->json([
            'status' => 'success',
            'message' => 'Department '.$name_department.' was successfully created',
            'department' => $m_department,
        ]);
	}
	//v1.0 by Merio, 20151230, method import divisi
	public function m_department_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_department::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data updated',
        ]);
	}
	//v1.0 by Merio, 20151230, method delete department
	public function m_department_delete($id)
	{
	 	m_department::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Department was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Department was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20151230, method form update department
	public function m_department_update($id)
	{
	 	$m_department 		= m_department::where('id', $id)->get();
	 	$m_department_all 	= m_department::select('*','m_departments.id as id_department',
									'm_departments.code as code_department','m_departments.name as name_department',
									'm_divisions.name as name_division','m_departments.alias as alias_department')
									->join('m_divisions','m_departments.code_division','=','m_divisions.code')
									->get();
	 	$m_division = m_division::all();

        $data  = [
            'department' => $m_department,
            'departments' => $m_department_all,
            'divisions' => $m_division,
        ];

         return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method save update department
	public function m_department_update_save()
	{
		$input = Request::all();
		$id_department 		= $input['id'];
		$name_department 	= $input['name'];
		$m_department 		= m_department::findOrFail($id_department);
		$m_department->code 			= $input['code'];
		$m_department->name 			= $name_department;
		$m_department->code_division 	= $input['code_division'];
		$m_department->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Department '.$name_department.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Department '.$name_department.' was successfully updated',
            'department' => $m_department,
        ]);
	}

	// ************* Master Data Section Here **************** //

	//v1.0 by Merio, 20151230, method view section
	public function m_section_view()
	{
	 	$leaders 		= user::where('role', 'Leader')->get();
		$m_section 		= m_section::select('*','m_sections.id as id_section','m_sections.code as code_section',
						'm_sections.name as name_section','m_departments.name as name_department','m_sections.alias as alias_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')->get();
		$m_department 	= m_department::all();

        $data = [
            'leaders' => $leaders,
            'sections' => $m_section,
            'departments' => $m_department,
        ];

        return response()->json($data);
	}

	//v1.0 by Merio, 20151230, method import section
	public function m_section_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_section::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
        return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data updated',
        ]);
	}
	//v1.0 by Merio, 20151230, method create section
	public function m_section_create()
	{
		$input          = Request::all();
		$name_section 	= $input['name'];
		$m_section 		= new m_section;
		$m_section->code	= $input['code'];
		$m_section->name 	= $name_section;
		$m_section->alias 	= $name_section;
		$m_section->code_department	= $input['code_department'];
		if ($input['npk_admin'] == "") {
			$m_section->npk_admin = null;
		} else {
			$m_section->npk_admin = $input['npk_admin'];
		}
		$m_section->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Section '.$name_section.' was successfully created');
        return response()->json([
            'status' => 'success',
            'message' => 'Section '.$name_section.' was successfully created',
            'section' => $m_section,
        ]);
	}
	//v1.0 by Merio, 20151230, method delete section
	public function m_section_delete($id)
	{
	 	m_section::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Section was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Section was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20151230, method form update section
	public function m_section_update($id)
	{
	 	$m_section 		= m_section::where('id', $id)->get();
	 	$leaders 		= User::where('role', 'Leader')->get();
	 	$m_section_all	= m_section::select('*','m_sections.id as id_section','m_sections.code as code_section',
						'm_sections.name as name_section','m_departments.name as name_department','m_sections.alias as alias_section')
						->join('m_departments','m_departments.code','=','m_sections.code_department')->get();
	 	$m_department = m_department::all();

        $data = [
            'm_section' => $m_section,
            'leaders' => $leaders,
            'm_section_all' => $m_section_all,
            'm_department' => $m_department,
        ];

         return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method save update section
	public function m_section_update_save()
	{
		$input          = Request::all();
		$id_section 	= $input['id'];
		$name_section 	= $input['name'];
		$m_section = m_section::findOrFail($id_section);
		$m_section->code  =	$input['code'];
		$m_section->name  = $name_section;
		$m_section->alias = $name_section;
		if ($input['npk_admin'] == "") {
			$m_section->npk_admin = null;
		} else {
			$m_section->npk_admin = $input['npk_admin'];
		}
		$m_section->code_department = $input['code_department'];
		$m_section->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Section '.$name_section.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Section '.$name_section.' was successfully updated',
            'section' => $m_section, // Optional: If you want to return the updated section object
        ]);
	}

	// ************* Master Data Sub Section Here **************** //

	//v1.0 by Merio, 20151230, method view sub section
	public function m_sub_section_view()
	{
		$m_sub_section = m_sub_section::select('*','m_sub_sections.id as id_sub_section',
					'm_sub_sections.code as code_sub_section','m_sub_sections.name as name_sub_section',
					'm_sections.name as name_section','m_sub_sections.alias as alias_sub_section')
					->join('m_sections','m_sections.code','=','m_sub_sections.code_section')->get();
		$m_section = m_section::all();
        $data = [
            'sub_sections' => $m_sub_section,
            'sections' => $m_section,
        ];

        return response()->json($data);
	}

	public function m_line_view() //dev-3.2.1 view m_line
	{
		$m_line = m_line::select('*','m_lines.id as line_id','m_lines.sub_section_code as line_sub_section_code', 'm_sub_sections.name as line_sub_section_name')
					->join('m_sub_sections','m_sub_sections.code','=','m_lines.sub_section_code')
					->join('m_sections','m_sections.code','=','m_sub_sections.code_section')->get();
		$m_sub_section = m_sub_section::all();
		$m_section = m_section::all();

        $data = [
            'lines' => $m_line,
            'sub_sections' => $m_sub_section,
            'sections' => $m_section,
        ];

        return response()->json($data);
	}

	public function m_line_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_line::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data updated',
        ]);
	}

	//v1.0 by Merio, 20151230, method import sub section
	public function m_sub_section_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_sub_section::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data updated',
        ]);
	}
	//v1.0 by Merio, 20151230, method create sub section
	public function m_sub_section_create()
	{
		$input              = Request::all();
		$name_sub_section 	= $input['name'];
		$m_sub_section 		= new m_sub_section;
		$m_sub_section->code			= $input['code'];
		$m_sub_section->name			= $name_sub_section;
		$m_sub_section->code_section	= $input['code_section'];
		$m_sub_section->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sub Section '.$name_sub_section.' was successfully created');
		return response()->json([
            'status' => 'success',
            'message' => 'Sub Section '.$name_sub_section.' was successfully created',
            'sub_section' => $m_sub_section, // Optional: If you want to return the created sub-section object
        ]);
	}
	//v1.0 by Merio, 20151230, method delete sub section
	public function m_sub_section_delete($id)
	{
	 	m_sub_section::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sub Section was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Sub Section was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20151230, method form update sub section
	public function m_sub_section_update($id)
	{
	 	$m_sub_section 		= m_sub_section::where('id', $id)->get();
	 	$m_sub_section_all 	= m_sub_section::select('*','m_sub_sections.id as id_sub_section',
							'm_sub_sections.code as code_sub_section','m_sub_sections.name as name_sub_section',
							'm_sections.name as name_section','m_sub_sections.alias as alias_sub_section')
							->join('m_sections','m_sections.code','=','m_sub_sections.code_section')->get();
		$m_section = m_section::all();

        $data = [
            'sub_section' => $m_sub_section,
            'section' => $m_section,];

        return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method save update sub section
	public function m_sub_section_update_save()
	{
		$input              = Request::all();
		$id_sub_section 	= $input['id'];
		$name_sub_section 	= $input['name'];
		$m_sub_section 		= m_sub_section::findOrFail($id_sub_section);
		$m_sub_section->code			= $input['code'];
		$m_sub_section->name 			= $name_sub_section;
		$m_sub_section->code_section 	= $input['code_section'];
		$m_sub_section->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sub Section '.$name_sub_section.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Sub Section '.$name_sub_section.' was successfully updated',
            'sub_section' => $m_sub_section, // Optional: If you want to return the updated sub-section object
        ]);
	}

	//added by Ario Rizki P, 20170811
	public function m_line_create()
	{
		$input          = Request::all();
		$name_line   	= $input['name'];
		$m_line 		= new m_line;
		$m_line->line_code			= $input['code'];
		$m_line->line_name			= $name_line;
		$m_line->sub_section_code	= $input['code_sub_section'];
		$m_line->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Line '.$name_line.' was successfully created');
		return response()->json([
            'status' => 'success',
            'message' => 'Line '.$name_line.' was successfully created',
            'line' => $m_line,
        ]);
	}

	public function m_line_update($id)
	{
	 	$m_line 		= m_line::where('id', $id)->get();
	 	$m_line_all 	= m_line::select('m_lines.id as id_line',
							'm_lines.line_code as line_code','m_lines.line_name as line_name',
							'm_sub_sections.name as sub_section_name','m_lines.line_alias as line_alias')
							->join('m_sub_sections','m_sub_sections.code','=','m_lines.sub_section_code')->get();
		$m_sub_section = m_sub_section::all();

        $data = [
            'line' => $m_line,
            'lines' => $m_line_all,
            'sub_sections' => $m_sub_section,
        ];

        return response()->json($data);
	}

	public function m_line_update_save() //ganti jadi line dulu
	{
        $input      = Request::all();
		$id_line 	= $input['id'];
		$name_line 	= $input['name'];
		$m_lines	= m_line::findOrFail($id_line);
		$m_lines->line_code			= $input['code'];
		$m_lines->line_name 		= $name_line;
		$m_lines->sub_section_code 	= $input['code_sub_section'];
		$m_lines->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Line '.$name_line.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Line '.$name_line.' was successfully updated',
            'line' => $m_lines,
        ]);
	}

	public function m_line_delete($id)
	{
	 	m_line::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Line was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Line was successfully deleted',
        ]);
	}
	// ************* Master Data Category Here **************** //

	//v1.0 by Merio, 20160102, method view category
	public function m_category_view()
	{
		$m_category = m_category::all();
        return response()->json($m_category);
	}
	//v1.0 by Merio, 20151230, method import category
	public function m_category_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_category::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully Saved',
            ]);
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data update',
        ]);
	}
	//v1.0 by Merio, 20160102, method create category
	public function m_category_create()
	{
		$input 			   	= Request::all();
		$m_category 	   	= new m_category;
		$name_category 		= $input['name'];
		$m_category->code 	= $input['code'];
		$m_category->name 	= $name_category;
		$m_category->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Category '.$name_category.' was successfully created');
        return response()->json([
            'status' => 'success',
            'message' => 'Category '.$m_category->name.' was successfully created',
            'category' => $m_category,
        ]);
	}
	//v1.0 by Merio, 20160102, method delete category
	public function m_category_delete($id)
	{
	 	m_category::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Category was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Category was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20151230, method form update category
	public function m_category_update($id)
	{
	 	$m_category 	= m_category::where('id', $id)->get();
	 	$m_category_all = m_category::all();

        $data = [
            'category' => $m_category,
            'all_category_all' => $m_category_all,
        ];

         return response()->json($data);
	}
	//v1.0 by Merio, 20151230, method save update category
	public function m_category_update_save()
	{
		$input 			= Request::all();
		$id 			= $input['id'];
		$name_category	= $input['name'];
		$m_category 	= m_category::findOrFail($id);
		$m_category->code = $input['code'];
		$m_category->name = $name_category;
		$m_category->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Category '.$name_category.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Category '.$name_category.' was successfully updated',
            'category' => $m_category,
        ]);
	}

	// ************* Master Data Jabatan Here **************** //

	//v1.0 by Merio, 20160104, method view occupation
	public function m_occupation_view()
	{
		$m_occupation = m_occupation::all();
        return response()->json([
            'occupations' => $m_occupation,
        ]);
	}
	//v1.0 by Merio, 20151230, method import sub section
	public function m_occupation_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_occupation::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully Saved',
            ]);
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'status' => 'Error',
            'message' => $result == 1 ? 'Successfully Saved' : 'No data update',
        ]);
	}
	//v1.0 by Merio, 20160104, method create occupation
	public function m_occupation_create()
	{
		$input              = Request::all();
		$m_occupation 	   	= new m_occupation;
		$name_occupation 	= $input['name'];
		$m_occupation->code 	= $input['code'];
		$m_occupation->name 	= $name_occupation;
		$m_occupation->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Occupation '.$name_occupation.' was successfully created');
		return response()->json([
            'status' => 'success',
            'message' => 'Occupation '.$name_occupation.' was successfully created',
            'occupation' => $m_occupation,
        ]);
	}
	//v1.0 by Merio, 20160104, method delete occupation
	public function m_occupation_delete($id)
	{
	 	m_occupation::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Occupation was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Occupation was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20160104, method form update occupation
	public function m_occupation_update($id)
	{
	 	$m_occupation 		= m_occupation::where('id', $id)->get();
	 	$m_occupation_all 	= m_occupation::all();

        $data = [
            'occupation' => $m_occupation,
            'occupations' => $m_occupation_all,
        ];

         return response()->json($data);
	}
	//v1.0 by Merio, 20160104, method save update occupation
	public function m_occupation_update_save()
	{
		$input              = Request::all();
		$id 				= $input['id'];
		$name_occupation	= $input['name'];
		$m_occupation 		= m_occupation::findOrFail($id);
		$m_occupation->code = $input['code'];
		$m_occupation->name = $name_occupation;
		$m_occupation->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Occupation '.$name_occupation.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Occupation '.$name_occupation.' was successfully updated',
            'occupation' => $m_occupation,
        ]);
	}

	// ************* Master Data Transport Here **************** //

	//v1.0 by Merio, 20160107, method view transport
	public function m_transport_view()
	{
		$m_transport = m_transport::all();
        return response()->json([
            'transport' => $m_transport,
        ]);
	}
	//v1.0 by Merio, 20151230, method import transport
	public function m_transport_import(Request $request)
	{
        $file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_transport::array_to_db($array_data);
		if ($result == 1) {
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Successfully Saved');
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully Saved',
            ]);
		} else {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message','No data update');
		}
		return response()->json([
            'message' => $result == 1 ? 'Successfully Saved' : 'No data update',
        ]);
	}
	//v1.0 by Merio, 20160107, method create transport
	public function m_transport_create()
	{
		$input              = Request::all();
		$m_transport	   	= new m_transport;
		$route_transport 	= $input['route'];
		$m_transport->code 	= $input['code'];
		$m_transport->route = $route_transport;
		$m_transport->time_in	= $input['time_in'];
		$m_transport->time_out	= $input['time_out'];
		$m_transport->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Transport route '.$route_transport.' was successfully created');
        return response()->json([
            'status' => 'success',
            'message' => 'Transport route '.$route_transport.' was successfully created',
            'transport' => $m_transport,
        ]);
	}
	//v1.0 by Merio, 20160107, method delete transport
	public function m_transport_delete($id)
	{
	 	m_transport::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Transport route was successfully deleted');
        return response()->json([
            'status' => 'success',
            'message' => 'Transport route was successfully deleted',
        ]);
	}
	//v1.0 by Merio, 20160107, method form update transport
	public function m_transport_update($id)
	{
	 	$m_transport 		= m_transport::where('id', $id)->get();
	 	$m_transport_all 	= m_transport::all();

        $data = [
            'transport' => $m_transport,
                'all_transports' => $m_transport_all,
        ];

         return response()->json($data);
	}
	//v1.0 by Merio, 20160107, method save update transport
	public function m_transport_update_save()
	{
		$input              = Request::all();
		$id 				= $input['id'];
		$route_transport	= $input['route'];
		$m_transport 		= m_transport::findOrFail($id);
		$m_transport->code	= $input['code'];
		$m_transport->route = $route_transport;
		$m_transport->time_in	= $input['time_in'];
		$m_transport->time_out	= $input['time_out'];
		$m_transport->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Transport route '.$route_transport.' was successfully updated');
		return response()->json([
            'status' => 'success',
            'message' => 'Transport route '.$route_transport.' was successfully updated',
        ]);
	}


	// ************* Master Data Break Overtime Here **************** // hotfix-1.5.21, by Merio Aji, 20160525, master break overtime

	public function m_break_ot_view()
	{
		$m_break_ot = m_break_ot::orderBy('day_break','DESC')->get();
        return response()->json($m_break_ot);
	}

	public function m_break_ot_create()
	{
		$input                          = Request::all();
		$m_break_ot 	   				= new m_break_ot;
		$m_break_ot->day_break 			= $input['day_break'];
		$m_break_ot->start_break 		= $input['start_break'];
		$m_break_ot->end_break 			= $input['end_break'];
		$m_break_ot->duration_break 	= $input['duration_break'];
		$m_break_ot->status_break 		= "1";
		$m_break_ot->save();

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, Data Break Overtime berhasil ditambahkan');
		return response()->json([
            'status' => 'success',
            'message' => 'Data Break Overtime berhasil ditambahkan',
            'data' => $m_break_ot,
        ]);
	}

	public function m_break_ot_delete($id)
	{
	 	m_break_ot::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, Data Break Overtime berhasil dihapus');
        return response()->json([
            'status' => 'success',
            'message' => 'Data Break Overtime berhasil dihapus',
            'deleted_id' => $id,
        ]);
	}

	public function m_break_ot_update($id)
	{
	 	$m_break_ot 	= m_break_ot::where('id', $id)->get();
	 	$m_break_ot_all = m_break_ot::all();

        $data = [
            'm_break_ot' => $m_break_ot,
            'm_break_ot_all' => $m_break_ot_all,
        ];

         return response()->json($data);
	}



	public function m_break_ot_update_save()
	{
		$input                      = Request::all();
		$id 						= $input['id'];
		$m_break_ot 				= m_break_ot::findOrFail($id);
		$m_break_ot->day_break 		= $input['day_break'];
		$m_break_ot->start_break 	= $input['start_break'];
		$m_break_ot->end_break 		= $input['end_break'];
		$m_break_ot->duration_break 		= $input['duration_break'];
		$m_break_ot->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, Data Break Overtime berhasil diubah');
		return response()->json([
            'status' => 'success',
            'message' => 'Data break Overtime berhasil diubah',
            'm_break_ot' => $m_break_ot,
        ]);
	}

  	//dev 1.6 by andre, 20160602 CRUD quota real
	public function m_quota_real_view()
	{
		$m_quota_real = m_quota_real::select('*','m_quota_reals.id as id_quota_real')
									->leftjoin('m_departments','m_departments.code','=','m_quota_reals.department')
									->get();
		$m_department = m_department::all();

        $data = [
            'm_quota_real' => $m_quota_real,
            'm_department' => $m_department,
        ];

        return response()->json($data);
	}

	public function m_quota_real_create()
	{
		$input                              = Request::all();
		$m_quota_real 	   					= new m_quota_real;
		$m_quota_real->department 			= $input['department'];
		$m_quota_real->quota_plan 			= $input['quota_plan']*60;
		$m_quota_real->quota_approve 		= $input['quota_approve']*60;
		$m_quota_real->month 				= $input['month'];
		$m_quota_real->fyear 				= $input['fyear'];
		$m_quota_real->employment_status 	= $input['employment_status'];
		$m_quota_real->occupation 			= $input['occupation'];
		$m_quota_real->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota real was successfully created');
		return response()->json([
			'status' => 'success',
            'message' => 'master quota real was successfully created',
            'data' => $m_quota_real,
        ]);
	}

	public function m_quota_real_delete($id)
	{
	 	m_quota_real::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota original was successfully deleted');
        return response()->json([
			'status' => 'success',
            'message' => 'master quota original was successfully deleted',
        ]);
	}

	public function m_quota_real_update($id)
	{
	 	$m_quota_real 	    = m_quota_real::where('id', $id)->get();
	 	$m_quota_real_all   = m_quota_real::all();
	 	$m_occupation   = m_occupation::all();

		$data = [
			'quota_real' => $m_quota_real,
            'quota_real_all' => $m_quota_real_all,
            'occupation' => $m_occupation,
		];

         return response()->json($data);
	}

	public function m_quota_real_update_save()
	{
		$input                              = Request::all();
		$id 								= $input['id'];
		$m_quota_real 	   					= m_quota_real::findOrFail($id);
		$m_quota_real->department 			= $input['department'];
		$m_quota_real->quota_approve 		= $input['quota_approve'];
		$m_quota_real->month 				= $input['month'];
		$m_quota_real->fyear 				= $input['fyear'];
		$m_quota_real->employment_status 	= $input['employment_status'];
		$m_quota_real->occupation 			= $input['occupation'];
		$m_quota_real->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota real was successfully updated');
		return response()->json([
			'status' => 'success',
            'message' => 'master quota real was successfully updated',
            'data' => $m_quota_real // Optionally, return the updated data
        ]);
	}

	public function m_quota_original_add()
	{
		$quota_add_itd 	= quota_add_itd::leftjoin('m_quota_reals','m_quota_reals.id','=','quota_add_itds.id_quota_real')
										->get();
		$quota_real 	= m_quota_real::all();

		$data = [
			'quota_add_itd' => $quota_add_itd,
            'quota_real' => $quota_real
		];

        return response()->json($data);
	}

	public function m_quota_used_view()
	{
		$m_quota_used = m_quota_used::select('*','m_quota_useds.id as id_quota_used')
									->leftjoin('m_departments','m_departments.code','=','m_quota_useds.department')
									->get();
		$m_department = m_department::all();

		$data = [
			'm_quota_used' => $m_quota_used,
            'm_department' => $m_department
		];

        return response()->json($data);
	}

    public function m_quota_used_create()
	{
		$input                              = Request::all();
		$m_quota_used 	   					= new m_quota_used;
		$m_quota_used->department 			= $input['department'];
		$m_quota_used->quota_plan 			= $input['quota_plan']*60;
		$m_quota_used->quota_approve 		= $input['quota_approve']*60;
		$m_quota_used->month 				= $input['month'];
		$m_quota_used->fyear 				= $input['fyear'];
		$m_quota_used->employment_status 	= $input['employment_status'];
		$m_quota_used->occupation 			= $input['occupation'];
		$m_quota_used->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota used was successfully created');
		return response()->json([
			'status' => 'success',
            'message' => 'master quota used was successfully created'
        ]);
	}

	public function m_quota_used_delete($id)
	{
	 	m_quota_used::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota used was successfully deleted');
        return response()->json([
			'status' => 'success',
            'message' => 'master quota used was successfully deleted'
        ]);
	}

	public function m_quota_used_update($id)
	{
	 	$m_quota_used 	    = m_quota_used::where('id', $id)->get();
	 	$m_quota_used_all   = m_quota_used::all();

		$data = [
			'm_quota_used' => $m_quota_used,
            'm_quota_used_all' => $m_quota_used_all
		];

         return response()->json($data);
    }

	public function m_quota_used_update_save()
	{
        $input                              = Request::all();
		$m_quota_used 	   					= m_quota_used::findOrFail();
		$m_quota_used->department 			= $input['department'];
		$m_quota_used->quota_plan 			= $input['quota_plan'];
		$m_quota_used->quota_approve 		= $input['quota_approve'];
		$m_quota_used->quota_remain 		= $input['quota_remain'];
		$m_quota_used->month 				= $input['month'];
		$m_quota_used->fyear 				= $input['fyear'];
		$m_quota_used->employment_status 	= $input['employment_status'];
		$m_quota_used->occupation 			= $input['occupation'];
		$m_quota_used->save();
		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, master quota used was successfully created');
		return response()->json([
			'status' => 'success',
			'message' => 'master quota used was successfully create',
            'm_quota_used' => $m_quota_used
        ]);
	}

	public function quota_mp_spv()
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $user   = Auth::user();
        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sections.alias as name_section',
        				'm_sub_sections.alias as name_sub_section','m_departments.alias as name_department')
						->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->where ( function ($q) {
                			$q->where('m_employees.occupation','OPR')
                    		->orWhere('m_employees.occupation','LDR');
                			})
						->where('m_sections.npk',$user->npk)
						->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
						->get();
		$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sections.alias as name_section',
        				'm_sub_sections.alias as name_sub_section','m_departments.alias as name_department')
						->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->where ( function ($q) {
                			$q->where('m_employees.occupation','OPR')
                    		->orWhere('m_employees.occupation','LDR');
                			})
						->where('m_sections.npk',$user->npk)
						->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
						->get();

						$data = [
							'month' => $month,
                            'year' => $year,
                            'user' => $user,
                            'user2' => $user2,
                            'user3' => $user3
						];

                        return response()->json($data);
    }

    public function quota_mp_kadept()
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $user   = Auth::user();
        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
        				'm_sections.alias as name_section','m_departments.alias as name_department')
        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->where ( function ($q) {
                			$q->where('m_employees.occupation','OPR')
                    		->orWhere('m_employees.occupation','LDR');
                			})
						->where('m_departments.npk','=',$user->npk)
						->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
						->get();
		$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
        				'm_sections.alias as name_section','m_departments.alias as name_department')
        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->where ( function ($q) {
                			$q->where('m_employees.occupation','OPR')
                    		->orWhere('m_employees.occupation','LDR');
                			})
						->where('m_departments.npk','=',$user->npk)
						->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
						->get();

						$data = [
							'month' => $month,
                            'year' => $year,
                            'user' => $user,
                            'user2' => $user2,
                            'user3' => $user3,
						];

                        return response()->json($data);
    }

    public function quota_mp_gm()
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $user   = Auth::user();
        if ($user->npk == '000007') {
        	$user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where (function($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.code','=','ENG')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where (function($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.code','=','ENG')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
        } else if ($user->npk == '000006') {
        	$user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.code','=','PRD')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.code','=','PRD')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
        } else {
	        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.npk','=',$user->npk)
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_divisions.npk','=',$user->npk)
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
		}

		$data = [
			'month' => $month,
            'year' => $year,
            'user' => $user,
            'user2' => $user2,
            'user3' => $user3,
		];

		return response()->json($data);
	}
    public function quota_mp_filter(Request $request)
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');
		$department = $request->department;
		$section = $request->section;
        $user   = Auth::user();
        if ($user->npk == '000007') {
        	$user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where (function($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_departments.code','=',$request->department ? $request->department :'')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where (function($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_departments.code','=',$request->department ? $request->department :'')
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
        } else if ($user->npk == '000006') {
        	$user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
								});

			if ($department) {
				$user2 = $user2->where('m_departments.code','=',$department);
			};
			if ($section) {
				$user2 = $user2->where('m_sections.code','=',$section);
			};
			$user2 = $user2->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
								});

			if ($department) {
				$user3 = $user3->where('m_departments.code','=',$department);
			};
			if ($section) {
				$user3 = $user3->where('m_sections.code','=',$section);
			};
			$user3 = $user3->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
        } else {
	        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
								});
				if ($section) {
					$user2 = $user2->where('m_sections.code','=',$section);
				};
				$user2 = $user2->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
								->get();
			$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			});
				if ($section) {
					$user3 = $user3->where('m_sections.code','=',$section);
				};
				$user3 = $user3->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
								->get();
		}

		$data = [
			'month' => $month,
            'year' => $year,
            'user' => $user,
            'user2' => $user2,
            'user3' => $user3,
		];

		return response()->json($data);
    }

    public function quota_mp_hr()
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $user   = Auth::user();
        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
		$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
		$m_department = m_department::all();

		$data = [
			'month' => $month,
            'year' => $year,
            'user' => $user,
            'user2' => $user2,
            'user3' => $user3,
            'm_department' => $m_department,
		];

        return response()->json($data);
    }

    public function quota_mp_hr_proses()
    {
        $month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

        $input = Request::all();
        $department = $input['m_department'];
        $user   = Auth::user();
        $user2  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_departments.code','=',$department)
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
		$user3  = m_employee::select('*','m_employees.npk as npk_user','m_sub_sections.alias as name_sub_section',
							'm_sections.alias as name_section','m_departments.alias as name_department')
	        				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
							->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
							->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
							->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
							->where ( function ($q) {
	                			$q->where('m_employees.occupation','OPR')
	                    		->orWhere('m_employees.occupation','LDR');
	                			})
							->where('m_departments.code','=',$department)
							->where('status_emp','<>','2')		// hotfix-3.1.2, Ferry,20170510, karyawan resign hidden
							->get();
		$m_department = m_department::all();

		$data = [
			'month' => $month,
            'year' => $year,
            'user' => $user,
            'user2' => $user2,
            'user3' => $user3,
            'm_department' => $m_department,
		];
		return response()->json($data);
    }

    public function quota_approve_kadept_add()
    {
        $user   =Auth::user();
        $quota_add = m_quota_add::select('*','m_quota_adds.id as id_quota_add')
        						->join('m_quota_useds','m_quota_useds.id','=','m_quota_adds.id_quota')
        						->where('m_quota_adds.npk_kadept','=',$user->npk)
        						->orderBy('m_quota_adds.id','=','DESC')->get();
        if ($user->role == "Ka Dept") {
			return response()->json([
                'status' => 'success',
                'data' => $quota_add
            ]);
		} else {
            return response()->json([
                'error' => 'Unauthorized',
		]);
    }
}

    public function quota_approve_kadept_save()
    {
        $user   = Auth::user();
        $input  = Request::all();
        $month  = Carbon::now()->format('m');
		$year  	= Carbon::now()->format('Y');
        $check_emp = m_employee::select('*','m_departments.code as code_department')
       							->join('m_departments','m_departments.code','=','m_employees.sub_section')
        						->where('m_employees.npk','=',$user->npk)->get();
        foreach ($check_emp as $check_emp) {
        	$code_department = $check_emp->code_department;
        }
        $check_quota = m_quota_used::where('department','=',$code_department)
        							->where('employment_status','=',$input['employment_status'])
        							->where('occupation','=',$input['occupation'])
        							->where('fyear','=',$year)
        							->where('month','=',$month)
        							->get();
        foreach ($check_quota as $check_quota) {
        	$id_quota_used 	= $check_quota->id;
        	$quota_plan 	= $check_quota->quota_plan;
        }
        $quota_add 						= new m_quota_add;
        $quota_add->reason_kadept 		= $input['keterangan'];
        $quota_add->status 				= "1";
        $quota_add->date_kadept 		= Carbon::now()->format('Y-m-d H:i:s');
        $quota_add->npk_kadept 			= $user->npk;
        $quota_add->quota_kadept 		= $input['quota_request']*60;
        $quota_add->department 			= $code_department;
       	$quota_add->id_quota 			= $id_quota_used;
        $quota_add->save();
        Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, quota request successfully input');
		return response()->json([
			'status' => 'success',
            'message' => 'Quota request successfully approved and saved.',
            'quota_add' => $quota_add,
        ]);
    }

    public function quota_approve_kadept_delete($id)
	{
	 	m_quota_add::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, quota request was successfully deleted');
        return response()->json([
			'status' => 'success',
            'message' => 'Quota request successfully deleted.',
        ]);
	}

	public function quota_approve_gm_view()
    {
        $user   	= Auth::user();
        $m_emp 		= m_employee::where('npk','=',$user->npk)->get();
        foreach ($m_emp as $m_emp) {
        	$sub_section = $m_emp->sub_section;
        }
        $quota_add 	= m_quota_add::select('*','m_quota_adds.id as id_quota_add')
        						->join('m_quota_useds','m_quota_useds.id','=','m_quota_adds.id_quota')
        						->where('m_quota_adds.status','=','1')
        						->orderBy('m_quota_adds.id','=','DESC')
        						->get();
        if ($user->role == "GM") {
			return response()->json([
                'quota_add' => $quota_add,
                'sub_section' => $sub_section,
            ]);
        } else {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
		}
    }

    public function quota_approve_gm_action_view($id)
    {
        $user   	= Auth::user();
        $quota_add 			= m_quota_add::where('id','=',$id)->get();
        $quota_add_view 	= m_quota_add::select('*','m_quota_adds.id as id_quota_add')
        							->join('m_quota_useds','m_quota_useds.id','=','m_quota_adds.id_quota')
	        						->where('m_quota_adds.status','=','1')
	        						->where('m_quota_adds.id','=',$id)
	        						->orderBy('m_quota_adds.id','=','DESC')
	        						->get();
        $quota_add_check 	= m_quota_add::where('id','=',$id)->get();
        foreach ($quota_add_check as $quota_add_check) {
        	$id_quota_used = $quota_add_check->id_quota;
        }
        $quota_used = m_quota_used::where('id','=',$id_quota_used)->get();
        foreach ($quota_used as $quota_used) {
        	$quota_limit = $quota_used->quota_plan;
        }
        if ($user->role == "GM") {
            return response()->json([
                'quota_add' => $quota_add,
                'quota_add_view' => $quota_add_view,
                'quota_limit' => $quota_limit, // Assuming you want to return quota limit as well
            ]);
        } else {
            return response()->json([
                'message' => 'Unauthorized access',
            ], 403);
		}
    }

	public function quota_approve_gm_action_reject($id)
    {
        $user   			= Auth::user();
        $quota_add 			= m_quota_add::findOrFail($id);
        $quota_add->status  = "3";
        $quota_add->save();

        Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, quota request successfully reject');
		return response()->json([
			'status' => 'success',
            'message' => 'Quota request successfully rejected',
        ]);
    }

	public function quota_approve_gm_action_save()
    {
        $user   = Auth::user();
        $input = Request::all();

        $quota_add 						= m_quota_add::findOrFail($input['id']);

        $check_quota = m_quota_used::where('id','=',$quota_add->id_quota)->get();
        foreach ($check_quota as $check_quota) {
        	$quota_plan = $check_quota->quota_plan;
        	$quota_approve = $check_quota->quota_approve;
        }
        $quota_plan_fix = $quota_plan-($input['quota_gm']*60);
        $quota_approve_fix = $quota_approve+($input['quota_gm']*60);

        if ($quota_plan_fix < 0) {
        	Session::flash('flash_type','alert-danger');
	        Session::flash('flash_message','Error, quota plan department sudah habis, silakan ulangi proses');
			return response()->json('quota/approve/gm/view');
        } else {
	        $quota_add->reason_gm 			= $input['reason_gm'];
	        $quota_add->status 				= "2";
	        $quota_add->date_gm 			= Carbon::now()->format('Y-m-d H:i:s');
	        $quota_add->npk_gm 				= $user->npk;
	        $quota_add->quota_gm 			= $input['quota_gm']*60;
	        $quota_add->save();

	        $update_quota = m_quota_used::findOrFail($quota_add->id_quota);
	        $update_quota->quota_plan = $quota_plan_fix;
	        $update_quota->quota_approve = $quota_approve_fix;
	        $update_quota->save();

	        Session::flash('flash_type','alert-success');
	        Session::flash('flash_message','Success, quota request successfully approved');
			return response()->json([
				'status' => 'successfully',
                'message' => 'Quota request successfully approved',
            ]);
		}
    }

    public function m_holiday_view(){
		$m_holiday = m_holiday::select('*','m_holidays.id as id_holiday')->orderBy('id')->get();
        return response()->json($m_holiday);
    }

    public function m_holiday_create(){
    	$user   = Auth::user();
        $input  = Request::all();
        $date 	= $input['date_holiday'];
        $day    = date('N',strtotime($date));
   		if ($day == "6" || $day == "7") {
   			$type = "1";
   		} else {
   			$type = "2";
   		}
        $m_holiday 						= new m_holiday;
        $m_holiday->date_holiday 		= $input['date_holiday'];
        $m_holiday->note_holiday 		= $input['note'];
        $m_holiday->type_holiday 		= $type;
        $m_holiday->npk_admin 			= $user->npk;
        $m_holiday->save();
        Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Success, holiday date successfully input');
		return response()->json([
			'status' => 'success',
            'message' => 'Holiday date successfully added.'
        ]);
    }
    public function m_holiday_delete($id){
    	m_holiday::destroy($id);
	 	Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Holiday was successfully deleted');
        return response()->json([
			'status' => 'success',
            'message' => 'Holiday successfully deleted.'
        ]);
    }

    public function holiday_upload(Request $request) {

		$i = 1;
		$r = 1;
		try {

			DB::beginTransaction();
	{
            $file = $request->file('file');

			$data = array();
			$file->move('../file/', $file->getClientoriginalName());
			$extension = Request::file('file')->getClientoriginalExtension();
			$fileName  = $file->getClientoriginalName();

			$rows = Request::load('file/'.$fileName)->get();

			foreach ($rows as $row) {

				$date   = date('d-m-Y', strtotime($row['date_holiday']));

				if ($date == "01-01-1970") {
					Session::flash('flash_type','alert-danger');
					Session::flash('flash_message','Baris-'.$r.' problem ===> Tanggal '. $row['date_holiday'] .' tidak sesuai, mohon di periksa dan upload ulang');
    				$truncate = m_holiday::truncate();
					return redirect()->back();
				}
				$r++;
			}

    		$truncate = m_holiday::truncate();


			foreach ($rows as $row) {

					$store = new m_holiday;
					$store->date_holiday 			= $row['date_holiday'];
					$store->note_holiday 			= $row['note_holiday'];
					$store->save();
					DB::commit();

				$i++;

			}

			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Sukses, hari libur berhasil di import');
			return response()->json([
				'status' => 'success',
				'message' => 'hari ini libur berhasil di import'
			]);
		}
    } catch (Exception $e) {
        DB::rollback();
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message', 'Baris-'.$i.' problem ===> '.$e->getMessage());
			return response()->json([
				'status' => 'Error',
				'message' => 'Baris-'.$i.' problem ===> '.$e->getMessage()
            ]);
                // hotfix-3.1.3, Ferry, kembali ke menu import
		}
	}


    //hotfix-1.9.4, by Merio, 20160818, quota request management
	public function quota_request_view()
	{
		$user   = Auth::user();
		$npk 	= $user->npk;
		$check_out = m_quota_request::where('status','=','1')
									->where('requester','=',$user->npk)
									->first();
		//hotfix-3.0.2, 20170124, by Merio, kalau ada quota request yang belum di generate akan di redirect ke halaman quota/request/temp
		if ($check_out) {
			return response()->json('quota/request/temp');
		} else {
			$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp', 'm_quota_departments.quota_plan as quota_plan_in_hours',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'))
											->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
											->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
											->join('m_quota_departments','m_departments.code','=','m_quota_departments.code_department')
											->where('m_quota_requests.requester',$user->npk)
											->where('m_quota_requests.status',2)
											->whereRaw('m_quota_requests.month = m_quota_departments.month')
											->whereRaw('m_quota_requests.year = m_quota_departments.year')
											->groupby('id_transaction')
											->get();

			// hotfix-3.1.2, Ferry, Query untuk quota before and after
			$quota_gm = new m_quota_request;
			$is_history = false;

			$par = 1;

			$data = [
				 'm_quota_request' => $m_quota_request,
                'quota_gm' => $quota_gm,
                'is_history' => $is_history,
			];
            return response()->json($data);
	    }
	}

	//hotfix-1.9.11, 20160824, by Merio, method untuk history approve quota request di dept head
	public function quota_request_history_approve()
	{
		$user   = Auth::user();
		//hotfixe-3.1.1 by yudo 20170504, tampilan untuk history approve (3)
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp', 'm_quota_departments.quota_plan as quota_plan_in_hours',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'))
											->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
											->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
											->join('m_quota_departments','m_departments.code','=','m_quota_departments.code_department')
											->where('m_quota_requests.requester',$user->npk)
											->where('m_quota_requests.status',3)
											->whereRaw('m_quota_requests.month = m_quota_departments.month')
											->whereRaw('m_quota_requests.year = m_quota_departments.year')
											->groupby('id_transaction')
											->get();

		// hotfix-3.1.2, Ferry, 20170510, Query untuk quota before and after
		$quota_gm = new m_quota_request;
		$is_history = true;

		$par = 2;

		$data = [
			'm_quota_request' => $m_quota_request,
            'quota_gm' => $quota_gm,
            'is_history' => $is_history,
            'par' => $par
		];
        return response()->json($data);
	}

	//hotfix-1.9.11, 20160824, by Merio, method untuk history rejected quota request di dept head
	public function quota_request_history_rejected()
	{
		$user   = Auth::user();
		//hotfixe-3.1.1 by yudo 20170504, tampilan untuk reject (-1)
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp', 'm_quota_departments.quota_plan as quota_plan_in_hours',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'))
											->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
											->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
											->join('m_quota_departments','m_departments.code','=','m_quota_departments.code_department')
											->where('m_quota_requests.requester',$user->npk)
											->where('m_quota_requests.status',-1)
											->whereRaw('m_quota_requests.month = m_quota_departments.month')
											->whereRaw('m_quota_requests.year = m_quota_departments.year')
											->groupby('id_transaction')
											->get();

		$is_history = true;
		$par = 3;

		$data = [
			 'm_quota_request' => $m_quota_request,
            'is_history' => $is_history,
            'par' => $par
		];
        return response()->json($data);
	}

	public function quota_request_temp()
	{

			$user   	 = Auth::user();
			$m_quota_request_all 	= m_quota_request::whereNull('status')->get();

			foreach ($m_quota_request_all as $m_quota_request_all) {
			$id 			  = $m_quota_request_all->id;
			$npk 			  = sprintf("%06s",$m_quota_request_all->npk);

			$update_request = m_employee::select('m_departments.npk as npk_dept_head','m_departments.code as code_department',
										'm_divisions.code as code_division')
										->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
										->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
										->join('m_departments','m_departments.code','=','m_sections.code_department')
										->join('m_divisions','m_divisions.code','=','m_departments.code_division')
										->where('m_employees.npk','=',$npk)
										->get();
			foreach ($update_request as $update_request) {
				$npk_dept_head 		= $update_request->npk_dept_head;
				$code_department 	= $update_request->code_department;
				$code_division 		= $update_request->code_division;
			}

			if ($code_division == 'MKT' || $code_division == 'ADM') {
				$npk_gm = '';
			} else {
				$check_gm = m_employee::where('sub_section','=',$code_division)->get();
				foreach ($check_gm as $check_gm) {
					$npk_gm = $check_gm->npk;
				}
			}
			$update_quota_request 					= m_quota_request::findOrFail($id);
			$update_quota_request->npk 				= $npk;
			$update_quota_request->requester 		= $npk_dept_head;
			$update_quota_request->approval 		= $npk_gm;
			$update_quota_request->department_code 	= $code_department;
			$update_quota_request->status 			= '1';
			$update_quota_request->save();
			}

			$m_quota_request = m_quota_request::select('*',
												'm_departments.name as department_name',
												'm_sections.alias as section_name',
												'm_sub_sections.alias as sub_section_name',
												'm_quota_requests.npk as npk_mp')
											->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
											->join('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
											->join('m_sections','m_sections.code','=','m_sub_sections.code_section')
											->join('m_departments','m_departments.code','=','m_sections.code_department')
											->where('m_quota_requests.requester',$user->npk)
											->where('m_quota_requests.status',1)
											->get();
			$m_quota_total = m_quota_request::select(DB::raw('sum(m_quota_requests.quota) as jml_quota'),
												DB::raw('count(m_quota_requests.npk) as jml_mp'))
											// ->join('m_employees','m_employees.npk','=','m_quota_requests.npk') //ini tidak terpakai query nya
											// ->join('m_departments','m_departments.code','=','m_quota_requests.department_code') // ini tidak terpakai query nya
											->where('m_quota_requests.requester',$user->npk)
											->where('m_quota_requests.status',1)
											->get();

			$getDate 	= m_quota_request::where('status',1)
										->where('requester', $user->npk)->first();

			$quota_request = new m_quota_request(); //inisialisasi memanggil kelas m_quota_request
			$quota_before  = $quota_request->getGMQuotaBefore($user->npk, $getDate->year, $getDate->month);
			$quota_after   = $quota_request->getGMQuotaAfterGenerate($user->npk, $getDate->year, $getDate->month);
			$jml_mp        = $quota_request->getTotalMp($user->npk, $getDate->year, $getDate->month);
			$jml_quota     = $quota_request->getTotalQuotaUpload($user->npk, $getDate->year, $getDate->month);

			$data = [
				'm_quota_request' => $m_quota_request,
                'm_quota_total' => $m_quota_total,
                'quota_before' => $quota_before,
                'quota_after' => $quota_after,
                'jml_mp' => $jml_mp,
                'jml_quota' => $jml_quota,
			];
			return response()->json($data);
		}

	public function quota_request_import(Request $request)
	{
		$file = $request->file('file');
        $table = $request->input('table');
		$array_data = CsvHelper::csv_to_array($file);
		$result     = m_quota_request::array_to_db($array_data);

		// hotfix-3.5.2, Ferry, 20180914, Result diperkaya
		if ($result['code'] == 1) {
			Session::flash('flash_type','alert-success');
		}
		else {
			Session::flash('flash_type','alert-danger');
		}
		Session::flash('flash_message', $result['msg']);

		return response()->json([
            'status' => 'success',
            'message' => $result['msg']
        ]);

        return response()->json([
            'status' => 'error',
            'message' => 'Error occurred while importing data: ' . $e->getMessage()
        ]);
	}


	public function quota_request_generate() {

		try {	// hotfix-3.1.2, Ferry, 20170516, Skema database commit-rollback

			$user    	= Auth::user();
			$input      = Request::all();
			$keterangan = $input['keterangan'];

			// hotfix-3.1.4, Ferry, 201700527, Initialization and Optimizing..
			$m_quota_requests = m_quota_request::where('requester', $user->npk)
												->where('status', 1)
												->get();

			$gm = $m_quota_requests[0]->approval;

			$year 			= $m_quota_requests->first()->year;
			$month 			= $m_quota_requests->first()->month;
			$request_date 	= Carbon::now()->format('Y-m-d H:i:s');

			// hotfix-3.1.2, Ferry, 20170516, diubah ke eloquent, algoritma sebelumnya dihapus
	    	$code_department 	= $user->hasEmployee->hasDepartment->code;
	        $code_division 		= $user->hasEmployee->hasDepartment->code_division;

	        // hotfix-3.1.2, Ferry, 20170516, diubah ke static function getNewSpklId, algoritma sebelumnya dihapus
			$id_transaction = $m_quota_requests->first()->getNewSpklId($code_department);

			//hotfix-3.0.2, by Merio, 20170126, Jika divisi Marketing dan Administrasi maka ketika request quota langsung approve dan langsung di tambahkan ke employeenya


			// hotfix-3.5.6, Ali, History over request
			$overKey = 'over_users_'.auth()->user()->id;

			DB::beginTransaction();
			if (Cache::has($overKey)) {
				$overUsers = Cache::get($overKey);
				auth()->user()->m_over_request_histories()->create([
					'details' => json_encode($overUsers),
					'approved_by' => $gm,
					'quota_at_that_time' => Cache::get('gm_limits_'.auth()->user()->id),
					'request_transaction_code' => $id_transaction,
				]);

				Cache::forget($overKey);
	    		Cache::forget('gm_limits_'.auth()->user()->id);
			}
			DB::commit();

			$class_quota_department = new m_quota_department();

			if ($code_division == 'ADM' || $code_division == 'MKT') {

				// start DB transcact
	            DB::beginTransaction();

				$status = '3';
				$approve_date = Carbon::now()->format('Y-m-d H:i:s');
				$req_before = $m_quota_requests->first()->getGMQuotaBefore($user->npk, $year, (int) $month);
				$totalRequest = 0;
				foreach ($m_quota_requests as $m_quota_request) {
					// hotfix-3.1.2, Ferry, 20170516, diubah ke eloquent, algoritma sebelumnya dihapus
					$quotaInMinute = $m_quota_request->quota*60;

					$m_quota_request->id_transaction	= $id_transaction;
					$m_quota_request->status 			= $status;
					$m_quota_request->request_date 		= $request_date;
					$m_quota_request->approve_date 		= $approve_date;
					$m_quota_request->quota 			= $quotaInMinute;
					$m_quota_request->quota_before		= $req_before;
					$m_quota_request->quota_after_detail = $m_quota_request->quota;	// hotfix-3.1.4
					$m_quota_request->keterangan 		= $keterangan;

					$update_emp = $m_quota_request->hasEmployee;
					$m_quota_request->quota_before_detail = $update_emp->{"quota_remain_".$m_quota_request->month};	// hotfix-3.1.4
					$update_emp->{"quota_remain_".$m_quota_request->month} = $m_quota_request->quota;

					$m_quota_request->save();	// hotfix-3.1.4, Ferry, 20170524, Moved here
					$update_emp->save();

					$totalRequest += $quotaInMinute;
				}

				$quotaDept = m_quota_department::where('code_department', $code_department)
					->where('month', $month)
					->where('year', $year)
					->first();

				$quotaDept->quota_used = $totalRequest;
				$quotaDept->update();

				$req_after = $m_quota_requests->first()->getGMQuotaAfter($user->npk, $year, (int) $month);
				m_quota_request::where('id_transaction', $id_transaction)->update(['quota_after' =>	$req_after]);

				// commit transact
	            DB::commit();

				Session::flash('flash_type','alert-success');
				Session::flash('flash_message','Sukses, Quota request berhasil di generate dan ditambahkan ke MP yang bersangkutan, ID Transaksi anda '.$id_transaction.'');
				return response()->json('quota/request/view');
			} elseif ($code_division == 'ENG' || $code_division == 'PRD' || $code_division == 'EPP') {
				//hotfix-3.0.2, by Merio, 20170126, Jika divisi Engineering dan Production harus approval ke GM walaupun over quota atau under

				$approve_date = Carbon::now()->format('Y-m-d H:i:s');
				$m_quota_request = $m_quota_requests->first();

				$req_month = $m_quota_request->month;
				$req_years = $m_quota_request->year;

				$quota_before = $m_quota_request->getGMQuotaBefore($user->npk, $req_years, $req_month);
				$quota_after  = $m_quota_request->getGMQuotaAfter($user->npk, $req_years, $req_month);
				$quota_after_generate = $m_quota_request->getGMQuotaAfterGenerate($user->npk, $req_years, $req_month);

				$quota_hrd    = $class_quota_department->getQuotaHRD($user->npk, $req_years, $req_month);

				$quota_before == null ? 0 : $quota_before;
				$quota_after  == null ? 0 : $quota_after;
				$quota_after_generate	== null ? 0 : $quota_after_generate;
				$quota_hrd    == null ? 0 : $quota_hrd;

				//hotfix-3.1.6, by yudo, 2017
				if($quota_hrd == 0){

					Session::flash('flash_type','alert-danger');
					Session::flash('flash_message', 'Budget Quota Dept. anda belum di upload oleh HRD, silahkan hubungi HRD.');
					return response()->json ('quota/request/temp');
				}

				//jika jumlah quota yang di ajukan lebih besar dari HRD divisi ENG dan PRODUKSI, maka harus approve GM
				if( ($quota_after_generate > $quota_hrd) || ($code_division == 'PRD')) {
					$status = '2';

					// hotfix-3.1.2, Ferry, 20170516, Optimizing ...pakai $req_month dan $m_quota_request yg sdh terdefinisi

					//tambahan pak gesang
					if($code_division == 'PRD' && ($quota_before >= $quota_after_generate && $quota_before != 0 )){ //revisi tidak perlu masuk ke hrd

						return $this->auto_approve($m_quota_requests, $id_transaction, $keterangan);
					}

					elseif($code_division == 'ENG' && $quota_hrd >= $quota_after_generate){

						return $this->auto_approve($m_quota_requests, $id_transaction, $keterangan);
					}
					else{
						// start DB transcact
			            DB::beginTransaction();

						// $req_before = $m_quota_requests->first()->getGMQuotaBefore($user->npk, $year, (int) $month);
						foreach ($m_quota_requests as $m_quota_request) {
							$quotaInMinute = $m_quota_request->quota*60;
							$m_quota_request->id_transaction	= $id_transaction;
							$m_quota_request->status 			= $status;
							$m_quota_request->request_date 		= $request_date;
							$m_quota_request->quota 			= $quotaInMinute;
							$m_quota_request->quota_before		= $quota_before;
							$m_quota_request->quota_after_detail = $m_quota_request->quota;	// hotfix-3.1.4
							$m_quota_request->keterangan 		= $keterangan;

							// hotfix-3.1.4, Ferry, 20170524, save quota history in detail
							$update_emp = $m_quota_request->hasEmployee;
							$m_quota_request->quota_before_detail = $update_emp->{"quota_remain_".$m_quota_request->month};	// hotfix-3.1.4

							$m_quota_request->save();	// hotfix-3.1.4, Ferry, 20170524, Moved here
						}
						$req_after = $m_quota_requests->first()->getGMQuotaAfter($user->npk, $year, (int) $month);
						m_quota_request::where('id_transaction', $id_transaction)->update(['quota_after' =>	$req_after]);

						// commit transact
	            		DB::commit();

						Session::flash('flash_type','alert-success');
						Session::flash('flash_message','Sukses, Quota request berhasil di generate, ID Transaksi anda '.$id_transaction.', silakan report ke GM anda untuk approval quota request');

						return response()->json([
							'status' => 'success',
							'message' => ' Quota request berhasil di generate, ID Transaksi anda '.$id_transaction.', silakan report ke GM anda untuk approval quota request'
						]);
					}
				} else {
					//khusus DIVISI Engineering < quota yang di kasih HRD
					return $this->auto_approve($m_quota_requests, $id_transaction, $keterangan);
				}
			}
		}
		catch (\Exception $e) {
	        // rollback transact
	        DB::rollback();

	        // throw new \Exception($e->getMessage(), 1);
	        Session::flash('flash_type','alert-danger');
			Session::flash('flash_message', $e->getMessage());
			return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while generating quota request: ' . $e->getMessage()
            ]);
		}
	}

	//hotfix-3.1.2, by yudo., 20170515, auto approve untuk
	public function auto_approve($m_quota_requests, $id_transaction, $keterangan){

		try {

			DB::beginTransaction();

			$user   = Auth::user();
			$status = '3';

			$gm = $m_quota_requests[0]->approval;

			$request_date = Carbon::now()->format('Y-m-d');
			$approve_date = Carbon::now()->format('Y-m-d');

			// hotfix-3.1.2, Ferry, 20170516, dapatkan month dan quota before
			$req_month = $m_quota_requests->first()->month;
			$req_year = $m_quota_requests->first()->year;
			$code_department = $m_quota_requests->first()->department_code;
			$req_before = $m_quota_requests->first()->getGMQuotaBefore($user->npk, $req_year, $req_month);
			$totalRequest = 0;
			foreach ($m_quota_requests as $m_quota_request) {
				$quotaInMinute = $m_quota_request->quota*60;
				$m_quota_request->id_transaction 	= $id_transaction;
				$m_quota_request->status 			= $status;
				$m_quota_request->request_date 		= $request_date;
				$m_quota_request->approve_date 		= $approve_date;
				$m_quota_request->quota 			= $quotaInMinute;
				$m_quota_request->quota_before		= $req_before;
				$m_quota_request->quota_after_detail = $m_quota_request->quota;	// hotfix-3.1.4
				$m_quota_request->keterangan 		= $keterangan;

				// hotfix-3.1.2, Ferry, 20170516, Optimizing ..check employee pakai eloquent
				$update_emp = $m_quota_request->hasEmployee;
				$m_quota_request->quota_before_detail = $update_emp->{"quota_remain_".$m_quota_request->month};	// hotfix-3.1.4
				$update_emp->{"quota_remain_".$m_quota_request->month} = $m_quota_request->quota;

				$m_quota_request->save();	// hotfix-3.1.4, Ferry, 20170524, Moved here
				$update_emp->save();

				$totalRequest += $quotaInMinute;
			}

			$quotaDept = m_quota_department::where('code_department', $code_department)
					->where('month', $req_month)
					->where('year', $req_year)
					->first();

			$quotaDept->quota_used = $totalRequest;
			$quotaDept->update();

			$overKey = 'over_users_'.auth()->user()->id;

			if (Cache::has($overKey)) {
				$overUsers = Cache::get($overKey);
				auth()->user()->m_over_request_histories()->create([
					'details' => json_encode($overUsers),
					'approved_by' => $gm,
					'quota_at_that_time' => Cache::get('gm_limits_'.auth()->user()->id),
					'request_transaction_code' => $id_transaction,
				]);

				Cache::forget($overKey);
	    		Cache::forget('gm_limits_'.auth()->user()->id);
			}

			DB::commit();

			$req_after = $m_quota_requests->first()->getGMQuotaAfter($user->npk, $req_year, $req_month);
					m_quota_request::where('id_transaction', $id_transaction)->update(['quota_after' =>	$req_after]);


			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Sukses, Quota request berhasil di generate, ID Transaksi anda '.$id_transaction.'');
			return response()->json([
				'status' => 'success',
				'message' => 'Quota request berhasil di generate, ID Transaksi anda '.$id_transaction.''
			]);
		}
		catch (Exception $e) {

			DB::rollback();
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message', $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error occurred while auto-approving quota request: ' . $e->getMessage()
            ]);
		}
	}

	public function quota_request_gm_view()
	{
		$user   = Auth::user();
		$variabel = 'm_quota_requests.npk';
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp', 'm_quota_departments.quota_plan as quota_plan_in_hours',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'),
											'm_quota_requests.month as month_req','m_quota_requests.year as year_dept')
										->leftjoin('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->leftjoin('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.approval',$user->npk)
										->where('m_quota_requests.status',2)
										->where('m_quota_departments.month', DB::raw('m_quota_requests.month')) //hotfix-3.1.1, by yudo quota nya perbulan
										->where('m_quota_departments.year', DB::raw('m_quota_requests.year')) //hotfix-3.5.3, by Handika quota nya perbulan & tahun
										->groupby('id_transaction')->get();

		$overQuota = m_over_request_histories::get()
			->groupBy('request_transaction_code');

		$specialLimit = m_spesial_limits::where('npk', $user->npk)->first();

		// hotfix-3.1.2, Ferry, Query untuk quota before and after
		$quota_gm = new m_quota_request;
		$is_history = false;

		$par = 1;

		$data = [
			'status' => 'success',
            'data' => $m_quota_request,
            'over_quota_history' => $overQuota,
            'special_limits' => $specialLimit,
		];
        return response()->json($data);


        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch GM quota requests. Please try again later.'
        ]);
	}

	public function quota_request_hr_view()
	{
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'),
										'm_quota_requests.month as month_req','m_quota_departments.year as year_dept')
										->leftjoin('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->leftjoin('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.status',2)
										->groupby('id_transaction')->get();
		$par = 1;
        return response()->json([
            'status' => 'success',
            'data' => $m_quota_request,
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch HR quota requests. Please try again later.'
        ]);
	}

	public function quota_request_gm_history_approve()
	{
		$user   = Auth::user();

		//hotfix-3.1.1 by yudo, 20170504 tampilan approval gm
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp', 'm_quota_departments.quota_plan as quota_plan_in_hours',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'),
											'm_quota_requests.month as month_req','m_quota_requests.year as year_dept')
										->leftjoin('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->leftjoin('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.approval',$user->npk)
										->where('m_quota_requests.status',3)
										->where('m_quota_departments.month', DB::raw('m_quota_requests.month')) //hotfix-3.1.1, by yudo quota nya perbulan
										->groupby('id_transaction')->get();

		$specialLimit = m_spesial_limits::where('npk', $user->npk)->first();

		// hotfix-3.1.2, Ferry, Query untuk quota before and after
		$quota_gm = new m_quota_request;
		$is_history = true;

        $par = 2;
        return response()->json([
            'status' => 'success',
            'data' => $m_quota_request,
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch GM approved quota requests. Please try again later.'
        ]);
	}

	public function quota_request_hr_history_approve()
	{
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'),
										'm_quota_requests.month as month_req','m_quota_departments.year as year_dept')
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.status',3)
										->groupby('id_transaction')->get();
        $par = 2;
        return response()->json([
            'status' => 'success',
            'data' => $m_quota_request,
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch HR approved quota requests. Please try again later.'
        ]);
	}

	public function quota_request_gm_history_rejected()
	{
		$user   = Auth::user();
		//hotfix-3.1.1 by yudo, 20170504 tampilan approval gm
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
											,'m_quota_requests.npk as npk_mp',
											DB::raw('sum(m_quota_requests.quota) as jml_quota'),
											DB::raw('count(m_quota_requests.npk) as jml_mp'),
											'm_quota_requests.month as month_req','m_quota_requests.year as year_dept')
										->leftjoin('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->leftjoin('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.approval',$user->npk)
										->where('m_quota_requests.status',-1)
										->where('m_quota_departments.month', DB::raw('m_quota_requests.month')) //hotfix-3.1.1, by yudo quota nya perbulan
										->groupby('id_transaction')->get();

		$specialLimit = m_spesial_limits::where('npk', $user->npk)->first();

		$is_history = true;
        $par = 3;
        return response()->json([
            'status' => 'success',
            'data' => $m_quota_request,
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to fetch GM rejected quota requests. Please try again later.'
        ]);
	}

	public function quota_request_hr_history_rejected()
	{
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'),
										'm_quota_requests.month as month_req','m_quota_departments.year as year_dept')
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->leftjoin('m_quota_departments','m_quota_departments.code_department','=','m_quota_requests.department_code')
										->where('m_quota_requests.status',-1)
										->groupby('id_transaction')->get();
        $par = 3;

		$data = [
			'm_quota_request' => $m_quota_request,
            'par' => $par,
		];
        return response()->json($data);
	}

	public function quota_request_gm_reject($id)
	{
		$user   = Auth::user();
		$reject_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('approval',$user->npk)
										->where('m_quota_requests.status',2)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '-1';
			$update_transaction->reject_date 	= $reject_date;
			$update_transaction->save();
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' berhasil di reject');
        return response()->json([
            'status' => 'success',
            'message' => 'Quota request dengan id transaksi '.$id.' berhasil di rejected'
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to reject quota request. Please try again later.'
        ]);
	}

	public function quota_request_hr_reject($id)
	{
		$user   = Auth::user();
		$reject_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('m_quota_requests.status',3)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '-1';
			$update_transaction->reject_date 	= $reject_date;
			$update_transaction->save();
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' berhasil di reject');
        return response()->json([
            'status' => 'success',
            'message' => 'Quota request dengan transaksi '.$id.' berhasil di rejected'
        ]);
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to reject quota request. Please try again later.'
        ]);
	}

	public function quota_request_gm_approve($id)
	{
		try {	// hotfix-3.1.2, Ferry, 20170513, Skema database commit-rollback

			// start DB transcact
            DB::beginTransaction();

			$user   = Auth::user();
			$approve_date 	= Carbon::now()->format('Y-m-d H:i:s');
			$m_quota_requests = m_quota_request::where('id_transaction',$id)
				->where('approval',$user->npk)
				->where('m_quota_requests.status', 2)->get();

			$code_department = $m_quota_requests->first()->department_code;
			$req_month = $m_quota_requests->first()->month;
			$req_year = $m_quota_requests->first()->year;

			$totalRequest = 0;
			// hotfix-3.1.2, Ferry, 20170513, Algoritma di optimize ....
			foreach ($m_quota_requests as $m_quota_request) {
				$m_quota_request->status 		= '3';
				$m_quota_request->approve_date 	= $approve_date;

				$update_emp = m_employee::where('npk', $m_quota_request->npk)->first();

				$m_quota_request->quota_before	= $m_quota_request->getGMQuotaBefore($m_quota_request->requester, $m_quota_request->year, $m_quota_request->month);
				$m_quota_request->quota_after	= $m_quota_request->getGMQuotaAfter($m_quota_request->requester, $m_quota_request->year, $m_quota_request->month);
				$update_emp->{"quota_remain_".$m_quota_request->month} = $m_quota_request->quota;

				$m_quota_request->save();
				$update_emp->save();

				$totalRequest += $m_quota_request->quota;
			}

			$quotaDept = m_quota_department::where('code_department', $code_department)
					->where('month', $req_month)
					->where('year', $req_year)
					->first();

			$quotaDept->quota_used = $totalRequest;
			$quotaDept->update();

			m_over_request_histories::where('request_transaction_code', $id)->update([]);
			// commit transact
            DB::commit();
			Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' berhasil di approve');
            return response()->json([
				'status' => 'success',
				'message' => 'Quota request dengan id transaksi '.$id.' berhasil di approved.'
			]);
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollback();
            Log::error('Error approving quota request: ' . $e->getMessage());

            // Flash error message
            Session::flash('flash_type', 'alert-danger');
            Session::flash('flash_message', 'Gagal menyetujui quota request. Silakan coba lagi.');

            return response()->json([
				'status' => 'Error',
				'message' => 'Gagal menyetujui quota request. Silakan coba lagi.'
			]);

	        // rollback transact
	        // DB::rollback();

	        throw new \Exception($e->getMessage(), 1);
		}
	}

	public function quota_request_hr_approve($id)
	{
		$user   = Auth::user();

		$approve_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('m_quota_requests.status',3)->get();
		foreach ($m_quota_request as $m_quota_request) {
			if ($m_quota_request->month == '1') {
				$par = "quota_remain_1";
			} else if ($m_quota_request->month == '2') {
				$par = "quota_remain_2";
			} else if ($m_quota_request->month == '3') {
				$par = "quota_remain_3";
			} else if ($m_quota_request->month == '4') {
				$par = "quota_remain_4";
			} else if ($m_quota_request->month == '5') {
				$par = "quota_remain_5";
			} else if ($m_quota_request->month == '6') {
				$par = "quota_remain_6";
			} else if ($m_quota_request->month == '7') {
				$par = "quota_remain_7";
			} else if ($m_quota_request->month == '8') {
				$par = "quota_remain_8";
			} else if ($m_quota_request->month == '9') {
				$par = "quota_remain_9";
			} else if ($m_quota_request->month == '10') {
				$par = "quota_remain_10";
			} else if ($m_quota_request->month == '11') {
				$par = "quota_remain_11";
			} else if ($m_quota_request->month == '12') {
				$par = "quota_remain_12";
			}

			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '4';
			$update_transaction->approve_date 	= $approve_date;
			$update_transaction->save();

			$check_employee = m_employee::where('npk',$m_quota_request->npk)->get();
			foreach ($check_employee as $check_employee) {
				$id_emp = $check_employee->id;
			}

			$update_emp = m_employee::findOrFail($id_emp);
			$update_emp->$par = $m_quota_request->quota;
			$update_emp->save();
		}

		$code_department = m_quota_request::where('id_transaction',$id)
							->where('m_quota_requests.status',4)->get();

		foreach ($code_department as $code_department) {
			$dept_code = $code_department->department_code;
			$month_req = $code_department->month;
			if ($code_department->month == '1') {
				$par2 = "quota_remain_1";
			} else if ($code_department->month == '2') {
				$par2 = "quota_remain_2";
			} else if ($code_department->month == '3') {
				$par2 = "quota_remain_3";
			} else if ($code_department->month == '4') {
				$par2 = "quota_remain_4";
			} else if ($code_department->month == '5') {
				$par2 = "quota_remain_5";
			} else if ($code_department->month == '6') {
				$par2 = "quota_remain_6";
			} else if ($code_department->month == '7') {
				$par2 = "quota_remain_7";
			} else if ($code_department->month == '8') {
				$par2 = "quota_remain_8";
			} else if ($code_department->month == '9') {
				$par2 = "quota_remain_9";
			} else if ($code_department->month == '10') {
				$par2 = "quota_remain_10";
			} else if ($code_department->month == '11') {
				$par2 = "quota_remain_11";
			} else if ($code_department->month == '12') {
				$par2 = "quota_remain_12";
			}
		}

		$year 	= Carbon::now()->format('Y');

		$query 	= DB::select('select sum('.$par2.') as sum from m_employees
			join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
			join m_sections on (m_sections.code = m_sub_sections.code_section)
			join m_departments on (m_departments.code = m_sections.code_department)
			where m_departments.code = "'.$dept_code.'" and
			m_employees.occupation in ("OPR","LDR") and m_employees.status_emp = "1" ');
        $result 	= new Collection($query);
        foreach ($result as $result) {
        	$sum = $result->sum;
        }
        $total = $sum/60;

        $query2 	= DB::select('
        select * from m_quota_departments where month = "'.$month_req.'" and year = "'.$year.'" and
        code_department = "'.$dept_code.'"
        ');
        $result2 	= new Collection($query2);

        foreach ($result2 as $result2) {
        	$id_req = $result2->id;
        }

        $update_quota_dept = m_quota_department::findOrFail($id_req);
        $update_quota_dept->quota_used = $total;
        $update_quota_dept->save();

		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' berhasil di approve');
        return response()->json([
			'status' => 'success',
			'message' => 'Quota request dengan id transaksi '.$id.'  berhasil di approved.']);

        // Flash error message
        Session::flash('flash_type', 'alert-danger');
        Session::flash('flash_message', 'Gagal menyetujui quota request. Silakan coba lagi.');

        return response()->json([
			'status' => 'error',
			'message' => 'Gagal menyetujui quota request. Silakan coba lagi.'
	]);
    }


	public function quota_request_gm_detail($id)
	{
		$user   = Auth::user();
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp','m_quota_requests.npk as npk_request')
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.approval',$user->npk)
										->where('m_quota_requests.id_transaction',$id)
										->get();
		$m_quota_all = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'))
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.approval',$user->npk)
										->where('m_quota_requests.id_transaction',$id)
										->groupby('id_transaction')->get();
		$status = m_quota_request::where('approval',$user->npk)
										->where('id_transaction',$id)
										->groupby('id_transaction')->get();

										$data = [
											 'quota_request_details' => $m_quota_request,
                                            'aggregate_quota_info' => $m_quota_all,
                                            'status' => $status,
										];
                                        return response()->json($data);

                                        return response()->json([
											'status' => 'Error',
                                            'message' => 'Failed to fetch quota request details. Please try again later.',
                                        ]);
	}

	public function quota_request_hr_detail($id)
	{
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp','m_quota_requests.npk as npk_request')
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.id_transaction',$id)
										->get();
		$m_quota_all = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'))
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.id_transaction',$id)
										->groupby('id_transaction')->get();
		$status = m_quota_request::where('id_transaction',$id)->groupby('id_transaction')->get();

		$data = [
			'quota_request_details' => $m_quota_request,
            'aggregate_quota_info' => $m_quota_all,
            'status' => $status,
		];
        return response()->json($data);
        return response()->json([
			'status' => 'Error',
            'message' => 'Failed to fetch HR quota request details. Please try again later.',
        ]);
	}

	public function quota_request_gm_reject_detail($id,$id2)
	{
		$user   = Auth::user();
		$reject_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('npk',$id2)
										->where('approval',$user->npk)
										->where('m_quota_requests.status',2)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '-1';
			$update_transaction->reject_date 	= $reject_date;
			$update_transaction->save();
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' dan npk '.$id2.' berhasil di reject');
        return response()->json([
			'status' => 'success',
            'message' => 'quota request dengan id transaksi ' . $id . ' dan npk ' . $id2 . ' berhasil di rejected'
        ]);
        return response()->json([
			'status' => 'Error',
            'message' => 'Gagal menolak quota request. Silakan coba lagi nanti.'
        ]);
	}

	public function quota_request_hr_reject_detail($id,$id2)
	{
		$user   = Auth::user();
		$reject_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('npk',$id2)
										->where('m_quota_requests.status',3)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '-1';
			$update_transaction->reject_date 	= $reject_date;
			$update_transaction->save();
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' dan npk '.$id2.' berhasil di reject');
        return response()->json([
            'status' => 'success',
            'message' => 'quota request dengan id transaksi ' . $id . ' dan npk ' . $id2 . ' berhasil di rejected'
        ]);
        return response()->json([
            'status' => 'Error',
            'message' => 'Gagal menolak quota request HR. Silakan coba lagi nanti.'
        ]);
	}

	public function quota_request_gm_approve_detail($id,$id2)
	{
		$user   = Auth::user();
		$approve_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('npk',$id2)
										->where('approval',$user->npk)
										->where('m_quota_requests.status',2)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '3';
			$update_transaction->approve_date 	= $approve_date;
			$update_transaction->save();

			if ($m_quota_request->month == '1') {
				$par = "quota_remain_1";
			} else if ($m_quota_request->month == '2') {
				$par = "quota_remain_2";
			} else if ($m_quota_request->month == '3') {
				$par = "quota_remain_3";
			} else if ($m_quota_request->month == '4') {
				$par = "quota_remain_4";
			} else if ($m_quota_request->month == '5') {
				$par = "quota_remain_5";
			} else if ($m_quota_request->month == '6') {
				$par = "quota_remain_6";
			} else if ($m_quota_request->month == '7') {
				$par = "quota_remain_7";
			} else if ($m_quota_request->month == '8') {
				$par = "quota_remain_8";
			} else if ($m_quota_request->month == '9') {
				$par = "quota_remain_9";
			} else if ($m_quota_request->month == '10') {
				$par = "quota_remain_10";
			} else if ($m_quota_request->month == '11') {
				$par = "quota_remain_11";
			} else if ($m_quota_request->month == '12') {
				$par = "quota_remain_12";
			}

			$check_employee = m_employee::where('npk',$m_quota_request->npk)->get();
			foreach ($check_employee as $check_employee) {
				$id_emp = $check_employee->id;
			}

			$update_emp = m_employee::findOrFail($id_emp);
			$update_emp->$par = $m_quota_request->quota;
			$update_emp->save();
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' dan npk '.$id2.' berhasil di approve');
        return response()->json([
			'status' => 'success',
            'message' => 'quota request dengan id transaksi ' . $id . ' dan npk ' . $id2 . ' berhasil di approved'
        ]);
        return response()->json([
			'status' => 'Error',
            'message' => 'Gagal menyetujui quota request GM. Silakan coba lagi nanti.'
        ]);
	}

	public function quota_request_hr_approve_detail($id,$id2)
	{
		$user   = Auth::user();
		$approve_date 	= Carbon::now()->format('Y-m-d H:i:s');
		$m_quota_request = m_quota_request::where('id_transaction',$id)
										->where('npk',$id2)
										->where('m_quota_requests.status',3)->get();
		foreach ($m_quota_request as $m_quota_request) {
			$id_quota = $m_quota_request->id;
			$update_transaction 				= m_quota_request::findOrFail($id_quota);
			$update_transaction->status 		= '4';
			$update_transaction->approve_date 	= $approve_date;
			$update_transaction->save();

			if ($m_quota_request->month == '1') {
				$par = "quota_remain_1";
			} else if ($m_quota_request->month == '2') {
				$par = "quota_remain_2";
			} else if ($m_quota_request->month == '3') {
				$par = "quota_remain_3";
			} else if ($m_quota_request->month == '4') {
				$par = "quota_remain_4";
			} else if ($m_quota_request->month == '5') {
				$par = "quota_remain_5";
			} else if ($m_quota_request->month == '6') {
				$par = "quota_remain_6";
			} else if ($m_quota_request->month == '7') {
				$par = "quota_remain_7";
			} else if ($m_quota_request->month == '8') {
				$par = "quota_remain_8";
			} else if ($m_quota_request->month == '9') {
				$par = "quota_remain_9";
			} else if ($m_quota_request->month == '10') {
				$par = "quota_remain_10";
			} else if ($m_quota_request->month == '11') {
				$par = "quota_remain_11";
			} else if ($m_quota_request->month == '12') {
				$par = "quota_remain_12";
			}

			$check_employee = m_employee::where('npk',$m_quota_request->npk)->get();
			foreach ($check_employee as $check_employee) {
				$id_emp = $check_employee->id;
			}

			$update_emp = m_employee::findOrFail($id_emp);
			$update_emp->$par = $m_quota_request->quota;
			$update_emp->save();
		}
		$code_department = m_quota_request::where('id_transaction',$id)
							->where('m_quota_requests.status',4)->get();

		foreach ($code_department as $code_department) {
			$dept_code = $code_department->department_code;
			$month_req = $code_department->month;
			if ($code_department->month == '1') {
				$par2 = "quota_remain_1";
			} else if ($code_department->month == '2') {
				$par2 = "quota_remain_2";
			} else if ($code_department->month == '3') {
				$par2 = "quota_remain_3";
			} else if ($code_department->month == '4') {
				$par2 = "quota_remain_4";
			} else if ($code_department->month == '5') {
				$par2 = "quota_remain_5";
			} else if ($code_department->month == '6') {
				$par2 = "quota_remain_6";
			} else if ($code_department->month == '7') {
				$par2 = "quota_remain_7";
			} else if ($code_department->month == '8') {
				$par2 = "quota_remain_8";
			} else if ($code_department->month == '9') {
				$par2 = "quota_remain_9";
			} else if ($code_department->month == '10') {
				$par2 = "quota_remain_10";
			} else if ($code_department->month == '11') {
				$par2 = "quota_remain_11";
			} else if ($code_department->month == '12') {
				$par2 = "quota_remain_12";
			}
		}

		$year 	= Carbon::now()->format('Y');

		$query 	= DB::select('select sum('.$par2.') as sum from m_employees
			join m_sub_sections on (m_sub_sections.code = m_employees.sub_section)
			join m_sections on (m_sections.code = m_sub_sections.code_section)
			join m_departments on (m_departments.code = m_sections.code_department)
			where m_departments.code = "'.$dept_code.'" and
			m_employees.occupation in ("OPR","LDR") and m_employees.status_emp = "1" ');
        $result 	= new Collection($query);
        foreach ($result as $result) {
        	$sum = $result->sum;
        }
        $total = $sum/60;

        $query2 	= DB::select('
        select * from m_quota_departments where month = "'.$month_req.'" and year = "'.$year.'" and
        code_department = "'.$dept_code.'"
        ');
        $result2 	= new Collection($query2);

        foreach ($result2 as $result2) {
        	$id_req = $result2->id;
        }

        $update_quota_dept = m_quota_department::findOrFail($id_req);
        $update_quota_dept->quota_used = $total;
        $update_quota_dept->save();

		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' dan npk '.$id2.' berhasil di approve');
        return response()->json([
			'status' => 'success',
            'message' => 'quota request dengan id transaksi ' . $id . ' dan npk ' . $id2 . ' berhasil di approved'
        ]);
        return response()->json([
			'status' => 'Error',
            'message' => 'Gagal menyetujui quota request HR. Silakan coba lagi nanti.'
        ]);
    }

	public function quota_request_detail($id)
	{
		$user   = Auth::user();
		$m_quota_request = m_quota_request::select('*','m_departments.name as department_name', 'm_quota_requests.status as status_for_humans',
													'm_quota_requests.npk as npk_mp','m_quota_requests.npk as npk_request')
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.requester',$user->npk)
										->where('m_quota_requests.id_transaction',$id)
										->get();
		$m_quota_all = m_quota_request::select('*','m_departments.name as department_name'
										,'m_quota_requests.npk as npk_mp',
										DB::raw('sum(m_quota_requests.quota) as jml_quota'),
										DB::raw('count(m_quota_requests.npk) as jml_mp'))
										->join('m_employees','m_employees.npk','=','m_quota_requests.npk')
										->join('m_departments','m_departments.code','=','m_quota_requests.department_code')
										->where('m_quota_requests.requester',$user->npk)
										->where('m_quota_requests.id_transaction',$id)
										->groupby('id_transaction')->get();

										$data= [
											'quota_request_details' => $m_quota_request,
                                            'aggregated_details' => $m_quota_all
										];
                                        return response()->json($data);
                                        return response()->json([
											'status' => 'Error',
                                            'message' => 'Gagal mengambil detail quota request. Silakan coba lagi nanti.'
                                        ]);
	}

	public function quota_request_delete($id)
	{
		$user   = Auth::user();
		$m_quota_request = m_quota_request::where('m_quota_requests.requester',$user->npk)
										->where('m_quota_requests.id_transaction',$id)
										->get();

		$user->m_over_request_histories()->where('request_transaction_code', $id)->delete();

		foreach ($m_quota_request as $m_quota_request) {
			m_quota_request::destroy($m_quota_request->id);
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request dengan id transaksi '.$id.' berhasil dihapus');
        return response()->json([
			'status' => 'success',
            'message' => 'quota request dengan id transaksi ' . $id . ' berhasil dihapus'
        ]);
    }

	public function quota_request_cancel($id)
	{
		$user   = Auth::user();
		$m_quota_request = m_quota_request::where('m_quota_requests.requester',$user->npk)
										->where('m_quota_requests.npk',$id)
										->where('m_quota_requests.status',1)
										->get();
		foreach ($m_quota_request as $m_quota_request) {
			m_quota_request::destroy($m_quota_request->id);
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request berhasil dihapus');
        return response()->json([
			'status' => 'success',
            'message' => 'Sukses, quota request berhasil dihapus'
        ]);
        return response()->json([
			'status' => 'Error',
            'message' => 'Gagal membatalkan quota request. Silakan coba lagi nanti.'
        ]);
	}

	//dev-3.0, by Merio, 20170103, fungsi untuk menghapus semua request penambahan quota
	public function quota_request_destroy_all()
	{
		$user   = Auth::user();
		$m_quota_request = m_quota_request::where('m_quota_requests.requester',$user->npk)
										->where('m_quota_requests.status',1)
										->get();
		foreach ($m_quota_request as $m_quota_request) {
			m_quota_request::destroy($m_quota_request->id);
		}
		Session::flash('flash_type','alert-success');
		Session::flash('flash_message','Sukses, quota request berhasil dihapus');
        return response()->json([
			'status' => 'success',
            'message' => 'quota request berhasil dihapus'
        ]);
	}

	// dev-2.2, Ferry, 20160911
	public function quota_mp_daily_gm()
    {
    	// dev-2.2, Ferry, 20160911, Optimize Quota Mp daily yg relatif lambat
    	$user = Auth::user();
    	$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day = 31;
    	$month_name 	= Carbon::now()->format('Y-m');
        $data = [
            'last_day_real' => $last_day_real,
            'last_day' => $last_day,
            'month_name' => $month_name
        ];
        return response()->json($data);
    }

    public function quota_mp_daily_hr()
    {
    	$user = Auth::user();
    	$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day = 31;
    	$month_name 	= Carbon::now()->format('Y-m');
		$data = [
            'last_day_real' => $last_day_real,
            'last_day' => $last_day,
            'month_name' => $month_name
        ];
        return response()->json($data);
    }

	public function quota_mp_daily_kadept()
    {
    	// dev-2.2, Ferry, 20160911, Optimize Quota Mp daily yg relatif lambat
    	$user = Auth::user();
    	$user2 			= Auth::user();
    	$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day 		= 31;
    	$month_name 	= Carbon::now()->format('Y-m');
    	$role_omc_kadept	= User::select('m_departments.name as name_department')
								->leftjoin('m_employees','m_employees.npk','=','users.npk')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
								->where('users.npk', $user2->npk)
								->first();
		$roletest = $role_omc_kadept->name_department;
		// return $role_omc_kadept;
		$data = [
            'last_day_real' => $last_day_real,
            'last_day' => $last_day,
            'month_name' => $month_name,
            'role_omc_kadept' => $role_omc_kadept
        ];
        return response()->json($data);
    }

    public function quota_mp_daily_spv()
    {
    	// dev-2.2, Ferry, 20160911, Optimize Quota Mp daily yg relatif lambat
    	$user = Auth::user();
    	$user2 			= Auth::user();
    	$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day = 31;
    	$month_name 	= Carbon::now()->format('Y-m');
    	$role_omc_spv	= User::select('m_departments.name as name_department')
								->leftjoin('m_employees','m_employees.npk','=','users.npk')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
								->where('users.npk', $user2->npk)
								->first();
		$roletest = $role_omc_spv->name_department;
		$data = [
            'last_day_real' => $last_day_real,
            'last_day' => $last_day,
            'month_name' => $month_name,
            'role_omc_spv' => $role_omc_spv
        ];
        return response()->json($data);
    }

    public function quota_mp_daily_ldr()
    {
    	// dev-2.2, Ferry, 20160911, Optimize Quota Mp daily yg relatif lambat
    	$user = Auth::user();
    	$user2 			= Auth::user();
    	$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day = 31;
    	$month_name 	= Carbon::now()->format('Y-m');
    	$role_omc_ldr	= User::select('m_departments.name as name_department')
								->leftjoin('m_employees','m_employees.npk','=','users.npk')
								->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
								->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
								->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
								->where('users.npk', $user2->npk)
								->first();
		$roletest = $role_omc_ldr->name_department;
		$data = [
            'last_day_real' => $last_day_real,
            'last_day' => $last_day,
            'month_name' => $month_name,
            'role_omc_ldr' => $role_omc_ldr,
        ];
        return response()->json($data);
    }

    //hotfix-3.1.1 by yudo, 20170427, upload quota dari template excel
    public function store_upload() {

    	$user    	   	   = Auth::user();
		$npk_login         = $user->{'npk'};

		$cek_request       = m_quota_request::select('requester')
							->where('requester','=',$npk_login)
							->where('status',2)
							->first();

		if ($cek_request) {
			Session::flash('flash_type','alert-danger');
			Session::flash('flash_message', 'Error, Anda masih memiliki pendingan request kuota yang belum di approvel GM');
            return response()->json('quota/request/view');
		} else {
			$i = 1;	// hotfix-3.1.3, Ferry, 20170519, $i untuk deteksi row yg bermasalah
			try {	// hotfix-3.1.3, Ferry, 20170519, try {} diposisikan paling atas

				DB::beginTransaction();

				$file = Request::file('file');

				$data = array();
				$file->move('../file/', $file->getClientoriginalName());
				$extension = Request::file('file')->getClientoriginalExtension();
				$fileName  = $file->getClientoriginalName();
				$rows = Request::load('file/'.$fileName)->get();


				// hotfix-3.5.2, Ferry, 20180914. Get info dept dari boss department code
				$department = m_department::whereNpk(Auth::user()->npk)->first();
				$boss_dept = $department->code;
				// dd($department);

				// hotfix-3.5.6, Ali, 20190906. Validasi dengan special limit GM
				$division = $department->hasDivision;
				$specialLimitHour = (int) $division->specialLimit->quota_limit / 60;

				$overUsers = [];

				foreach ($rows as $row) {
					$npk_rep = str_replace("'", "", $row['npk']);
					$npk = str_replace(' ', '', $npk_rep);
					$npk = sprintf("%06s", $npk);

					if($npk != '' && $npk != '000000' && $npk != null ){

						// hotfix-3.1.3, Ferry, 20170519, tambahkan verifikasi dgn quota yg sudah terpakai
						// Hanya boleh upload quota request >= quota used
						$employee = m_employee::where('npk', $npk)
												->where('status_emp', '1')
												->first();

						// hotfix-3.1.3, Ferry, 20170519, jika npk tidak ada maka error
						if (! $employee) {
							throw new \Exception('data npk : ('.$npk.') tidak ditemukan atau resign', 1);
						}
						else {
							if ($row['quota']*60 < $employee->{'quota_used_'.$row['month']}) {
								throw new \Exception('data npk : ('.$npk.') quota yang di request '.$row['quota'].' jam, tidak boleh kurang dari penggunaan saat ini '.
														round($employee->{'quota_used_'.$row['month']}/60, 2). ' jam', 1);
							}
							elseif ($boss_dept != $employee->hasSubSection->hasSection->hasDepartment->code) {

								// hotfix-3.5.2, Ferry, 20180914
								throw new \Exception('data npk : ('.$npk.') bukan dalam struktur departemen terkait. mohon dikeluarkan dari list.', 1);
							}
						}

						// hotfix-3.1.3, Ferry, 20170519, tata indentasi coding...

						// hotfix-3.5.1, Ferry, 20180329, fixing import excel data duplikasi
						$store = m_quota_request::where('npk', $npk)
									->where('month', $row['month'])
									->where('year', $row['year'])
									->whereNull('status')
									->first();

						// get last approval request
						$lastRequest = m_quota_request::where('npk', $npk)
									->where('month', $row['month'])
									->where('year', $row['year'])
									->where('status', '3')
									->orderBy('request_date', 'desc')
									->first();

						if (! $store) {
							$store = new m_quota_request;
						}

						$store->npk 			= $npk;
						$store->quota 			= $row['quota'];
						$store->month 			= $row['month'];
						$store->year 			= $row['year'];
						$store->save();

						// handle jika belum ada request sebelumnya
						$lastQuota = 0;

						if ($lastRequest) {
							$lastQuota = (int)$lastRequest->quota / 60;
						}

						if ($lastQuota < $store->quota) {
							if ($store->quota > $specialLimitHour) {
								$overUsers[] = [
									'id' => $store->id,
									'npk' => $store->npk,
									'quota' => $store->quota,
									'over' => $store->quota - $specialLimitHour
								];
							}
						}

						DB::commit();
					}

					$i++;

				}

				Cache::forever('over_users_'.auth()->user()->id, $overUsers);
				Cache::forever('gm_limits_'.auth()->user()->id, $specialLimitHour);

				Session::flash('flash_type','alert-success');
				Session::flash('flash_message','Sukses, quota request berhasil di import! selanjutnya tulis alasan dan silakan generate');
				return response()->json ('quota/request/temp');
			}
			catch(\Exception $e){
				DB::rollback();
				Session::flash('flash_type','alert-danger');
				Session::flash('flash_message', 'Baris-'.$i.' problem ===> '.$e->getMessage());
				return response()->json([
					'status' => 'Error',
                    'message' => 'Failed to process uploaded quota request. Please check the file format and try again.'
                ]);	// hotfix-3.1.3, Ferry, kembali ke menu import
			}
    	}
	}

	public function quota_spesial_limit() //dev-3.4.0, Fahrul Sudarusman, 20171206
	{
		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$gm = Auth::user();

		$special_limit = m_spesial_limits::whereNpk($gm->npk)->first();

		if ($gm->role == "GM") {
			$div_code = $gm->hasEmployee->hasDivision->code;
		}

		$employees = m_employee::select('*',
					'm_employees.npk as npk_user',
					'm_sub_sections.alias as name_sub_section',
					'm_sections.alias as name_section',
					'm_departments.alias as name_department')
    				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
					->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
					->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
					->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
					->where ( function ($q) {
            			$q->where('m_employees.occupation','OPR')
                		->orWhere('m_employees.occupation','LDR');
            			});




		if ($gm->role == "GM") {
			$employees = $employees->where('m_divisions.code',$div_code);
		}

		$employees = $employees->get();

		$user_approved = t_approved_limit_spesial::select('npk')->get();

		return response()->json([
            'employees' => $employees,
            'special_limit' => $special_limit,
            'user_approved' => $user_approved,
        ]);
	}

	public function edit_parameter() //dev-3.4.0, Fahrul Sudarusman, 20171206
	{
		$gm   				= Auth::user();
        $input              = Request::all();
		$cek_npk 			= user::select('npk')->whereNpk($gm->npk)->first();
		$limit  			= m_spesial_limits::whereNpk($gm->npk)->first();

		$quotaweekdayUpdate = $input['quota_limit_weekday'] ? $input['quota_limit_weekday']*60 : $limit->quota_limit_weekday ;
		$quotaholidayUpdate = $input['quota_limit_holiday'] ? $input['quota_limit_holiday']*60 : $limit->quota_limit_holiday ;

		if ($gm->role == "GM") {
			$limitHrd  			= m_spesial_limits::whereNpk('administrator')->first();
			if ($input['quota_limit_weekday'] && $limitHrd->quota_limit_weekday < $quotaweekdayUpdate ) {
				Session::flash('flash_type','alert-danger');
	        	Session::flash('flash_message','Limit Weekday yang anda input lebih besar dari limit dari HRD, limit HRD sebesar '. $limitHrd->quota_limit_weekday/60 .' Jam' );
	        	return response()->json()->back();
			}

			if ($input['quota_limit_holiday'] && $limitHrd->quota_limit_holiday < $quotaholidayUpdate ) {
				Session::flash('flash_type','alert-danger');
	        	Session::flash('flash_message','Limit Holiday yang anda input lebih besar dari limit dari HRD, limit HRD sebesar '. $limitHrd->quota_limit_holiday/60 .' Jam' );
	        	return response()->json()->back();
			}

		}

		// update history
		$gm->m_spesial_limit_histories()->create([
			'quota_before_update_weekday' => $limit->quota_limit_weekday,
			'quota_before_update_holiday' => $limit->quota_limit_holiday,
			'quota_after_update_weekday' => $quotaweekdayUpdate,
			'quota_after_update_holiday' => $quotaholidayUpdate,
			'ip_address' => $_SERVER['REMOTE_ADDR']
		]);

		$limit->quota_limit_weekday	= $quotaweekdayUpdate;
		$limit->quota_limit_holiday	= $quotaholidayUpdate;
        $limit->save();

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Parameter Berhasil Di Set');
        return response()->json([
			'status' => 'success',
			'message' => 'Parameter successfully updated']);
	}

	public function cancel_approved_spesial_limit($npk_user) //dev-3.4.0, Fahrul Sudarusman, 20171207
	{
		$npk = t_approved_limit_spesial::where('t_approved_limit_spesials.npk', $npk_user)->delete();

		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$gm = Auth::user();

		$special_limit = m_spesial_limits::whereNpk($gm->npk)->pluck('quota_limit');

		$div_code = $gm->hasEmployee->hasDivision->code;

		// log
		$log = new t_approved_limit_spesial_log;
		$log->npk_allowed = $npk_user;
		$log->allowed_by = $gm->npk;
		$log->date = date('Y-m-d');
		$log->status = 0;
		$log->save();

		$employees = m_employee::select('*',
					'm_employees.npk as npk_user',
					'm_sub_sections.alias as name_sub_section',
					'm_sections.alias as name_section',
					'm_departments.alias as name_department')
    				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
					->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
					->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
					->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
					->where ( function ($q) {
            			$q->where('m_employees.occupation','OPR')
                		->orWhere('m_employees.occupation','LDR');
            			})
					->where('m_divisions.code',$div_code)
					// ->where('quota_used_'.$month, '>', $special_limit-24)
					->get();

		$user_approved = t_approved_limit_spesial::select('npk')->get();

		Session::flash('flash_type','alert-success');
        Session::flash('flash_message','Sukses, NPK '.$npk_user.' berhasil dicabut ijin membuat SPKL melebihi quota limit');
        return response()->json([
            'status' => 'Success',
			'message' => 'NPK '.$npk_user.' berhasil dicabut ijin membuat SPKL melebihi quota limit',
            'employees' => $employees
        ]);
	}

	public function approved_spesial_limit($npk_user) //dev-3.4.0, Fahrul Sudarusman, 20171207
	{
		$npk = t_approved_limit_spesial::where('t_approved_limit_spesials.npk', $npk_user)->delete();

		$input                  = Request::all();
		$approved 				= new t_approved_limit_spesial;
		$approved->npk 	 		= $npk_user;
        $approved->save();



		$month 	= Carbon::now()->format('n');
        $year 	= Carbon::now()->format('Y');

		$gm = Auth::user();

		// log
		$log = new t_approved_limit_spesial_log;
		$log->npk_allowed = $npk_user;
		$log->allowed_by = $gm->npk;
		$log->date = date('Y-m-d');
		$log->status = 1;
		$log->save();

		$special_limit = m_spesial_limits::whereNpk($gm->npk)->pluck('quota_limit');

		$div_code = $gm->hasEmployee->hasDivision->code;

			$employees = m_employee::select('*',
						'm_employees.npk as npk_user',
						'm_sub_sections.alias as name_sub_section',
						'm_sections.alias as name_section',
						'm_departments.alias as name_department')
	    				->leftjoin('m_sub_sections','m_sub_sections.code','=','m_employees.sub_section')
						->leftjoin('m_sections','m_sections.code','=','m_sub_sections.code_section')
						->leftjoin('m_departments','m_departments.code','=','m_sections.code_department')
						->leftjoin('m_divisions','m_divisions.code','=','m_departments.code_division')
						->where ( function ($q) {
	            			$q->where('m_employees.occupation','OPR')
	                		->orWhere('m_employees.occupation','LDR');
	            			})
						->where('m_divisions.code',$div_code)
						// ->where('quota_used_'.$month, '>', $special_limit-24)
						->get();

			$user_approved = t_approved_limit_spesial::select('npk')->get();

			Session::flash('flash_type','alert-success');
	        Session::flash('flash_message','Sukses, NPK '.$npk_user.' berhasil diijinkan membuat SPKL melebihi Quota Limit Spesial');
            return response()->json([
                'status' => 'Success',
				'message' => 'NPK '.$npk_user.' berhasil diijinkan membuat SPKL melebihi Quota Limit Spesial',
                'employees' => $employees
            ]);
	}

	public function upload_line() {

  		$i = 1;
    	try {

	    	DB::beginTransaction();

	  //   	$m_employee_update 	= m_employee::where('line_code','<>','')
	  //   						->get();
			// $m_employee_update->line_code = '';
			// $m_employee_update->save();
			DB::table('m_employees')
            ->where('line_code', '<>','')
            ->update(['line_code' => '']);

    		$file = Request::file('file');

       		$data = array();
	    	$file->move('../file/', $file->getClientoriginalName());
	    	$extension = Request::file('file')->getClientoriginalExtension();
	    	$fileName  = $file->getClientoriginalName();

      		$rows = Request::load('file/'.$fileName)->get();

	    	foreach ($rows as $rows) {

	    		$npk_rep = str_replace("'", "", $rows['npk']);
	    		$npk = str_replace(' ', '', $npk_rep);
	    		$npk = sprintf("%06s", $npk);
	    		$emp=m_employee::where('npk',$npk)->first();
	    		if($npk != '' && $npk != '000000' && $npk != null ){


	    			$employee = m_employee::select('npk')
	    									->where('npk', $npk)
	    									->where('status_emp', '1')
	    									->first();


	    			if (! $employee) {
	    				throw new \Exception('npk : ('.$npk.') tidak ditemukan atau resign', 1);
	    			}
	    			else {

	    			}
	    		$emp->line_code=$rows['line_code'];
	    		$emp->save();
	    		}

	    		$i++;

	    	}

	    	//DB::table('m_employees')->update($data);
	    	 DB::commit();

	    	Session::flash('flash_type','alert-success');
			Session::flash('flash_message','Sukses, quota request berhasil di import!');
    		return response()->json([
				'status' => 'success',
				'message' => 'Quota request berhasil di import'
			]);
    	}
    	catch(\Exception $e){
    		DB::rollback();
    		Session::flash('flash_type','alert-danger');
			Session::flash('flash_message', 'Baris-'.$i.' problem ===> '.$e->getMessage());
			return response()->json([
				'status' => 'Error',
				'message' => $e->getMessage()
			]);
    	}

	}

	public function spesial_limit_history()
	{
		$user = auth()->user();

		return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
	}


	public function mp_allowed_history()
	{
		$user = auth()->user();

		return response()->json([
            'status' => 'success',
            'user' => $user
        ]);
	}

    public function quota_section_daily()
	{
		$last_day_real 	= Carbon::now()->daysInMonth;
    	$last_day = 31;
    	$month_name 	= Carbon::now()->format('Y-m');

		$data = [
			'last_day_real' => $last_day_real,
			'last_day' => $last_day,
			'month_name' => $month_name
		];
        return response()->json($data);
	}
}






// ********************************************************** CRUD ********************************************************** //

// ********************************************************** CRUD ********************************************************** //

    // public function index()
    // {
    //     //get all posts
    //     $users = User::latest()->paginate(5);

    //     //return collection of posts as a resource
    //     return new UserResource(true, 'List Data Users', $users);
    // }

    // /**
    //  * store
    //  *
    //  * @param  mixed $request
    //  * @return void
    //  */
    // public function store(Request $request)
    // {
    //     //define validation rules
    //     $validator = Validator::make($request->all(), [
    //         'title'     => 'required',
    //         'content'   => 'required',
    //     ]);

    //     //check if validation fails
    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);
    //     }

    //     //create post
    //     $user = User::create([
    //         // 'image'     => $image->hashName(),
    //         'title'     => $request->title,
    //         'content'   => $request->content,
    //     ]);

    //     //return response
    //     return new UserResource(true, 'Data User Berhasil Ditambahkan!', $user);
    // }

    // /**
    //  * show
    //  *
    //  * @param  mixed $id
    //  * @return void
    //  */
    // public function show($id)
    // {
    //     $user = User::find($id);        //find post by ID

    //     return new UserResource(true, 'Detail Data User!', $user);        //return single post as a resource
    // }

    // /**
    //  * update
    //  *
    //  * @param  mixed $request
    //  * @param  mixed $id
    //  * @return void
    //  */
    // public function update(Request $request, $id)
    // {
    //     $validator = Validator::make($request->all(), [  //define validation rules
    //         'title'     => 'required',
    //         'content'   => 'required',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json($validator->errors(), 422);  //check if validation fails
    //     }

    //     //find post by ID
    //     $user = User::find($id);
    //     {
    //         $user->update([
    //             'title'     => $request->title,
    //             'content'   => $request->content,
    //         ]);
    //     }

    //     return new UserResource(true, 'Data User Berhasil Diubah!', $user); //return response
    // }

    // /**
    //  * destroy
    //  *
    //  * @param  mixed $id
    //  * @return void
    //  */
    // public function destroy($id)
    // {
    //     $user = User::find($id); //find post by ID
    //     $user->delete();  //delete post

    //     //return response
    //     return new UserResource(true, 'Data User Berhasil Dihapus!', null);
    // }

// //************************************************************************************************ */

//     public function indexBreakOt()
//     {
//         //get all posts
//         $m_break_ots = m_break_ot::latest()->paginate(5);

//         //return collection of posts as a resource
//         return new MBreakOtResource(true, 'List Data Break Ot', $m_break_ots);
//     }

//     public function storeBreakOt(Request $request)
//     {
//         //define validation rules
//         $validator = Validator::make($request->all(), [
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         //check if validation fails
//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);
//         }

//         //create post
//         $m_break_ots = m_break_ot::create([
//             'title'     => $request->title,
//             'content'   => $request->content,
//         ]);

//         //return response
//         return new MBreakOtResource(true, 'Data Break Ot Berhasil Ditambahkan!', $m_break_ots);
//     }

//     public function showBreakOt($id)
//     {
//         $m_break_ots = m_break_ot::find($id);        //find post by ID

//         return new MBreakOtResource(true, 'Detail Data Break Ot!', $m_break_ots);        //return single post as a resource
//     }

//     public function updateBreakOt(Request $request, $id)
//     {
//         $validator = Validator::make($request->all(), [  //define validation rules
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);  //check if validation fails
//         }

//         //find post by ID
//             $m_break_ots = m_break_ot::find($id);
//             $m_break_ots->update([
//                 'title'     => $request->title,
//                 'content'   => $request->content,
//             ]);

//         return new MBreakOtResource(true, 'Data Break Ot Berhasil Diubah!', $m_break_ots); //return response
//     }

//     public function destroyBreakOt($id)
//     {
//         $m_break_ots = m_break_ot::find($id); //find post by ID
//         $m_break_ots->delete();  //delete post

//         //return response
//         return new MBreakOtResource(true, 'Data Break Ot Berhasil Dihapus!', null);
//     }

    //************************************************************************************************ */

//     public function indexDepartment()
//     {
//         //get all posts
//         $m_departments = m_department::latest()->paginate(5);

//         //return collection of posts as a resource
//         return new MDepartmentsResource(true, 'List Data Department', $m_departments);
//     }

//     public function storeDepartment(Request $request)
//     {
//         //define validation rules
//         $validator = Validator::make($request->all(), [
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         //check if validation fails
//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);
//         }

//         //create post
//         $m_departments = m_department::create([
//             'title'     => $request->title,
//             'content'   => $request->content,
//         ]);

//         //return response
//         return new MDepartmentsResource(true, 'Data Department Berhasil Ditambahkan!', $m_departments);
//     }

//     public function showDepartment($id)
//     {
//         $m_departments = m_department::find($id);        //find post by ID

//         return new MDepartmentsResource(true, 'Detail Data Department!', $m_departments);        //return single post as a resource
//     }

//     public function updateDepartment(Request $request, $id)
//     {
//         $validator = Validator::make($request->all(), [  //define validation rules
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);  //check if validation fails
//         }

//         //find post by ID
//             $m_departments = m_department::find($id);
//             $m_departments->update([
//                 'title'     => $request->title,
//                 'content'   => $request->content,
//             ]);

//         return new MDepartmentsResource(true, 'Data Department Berhasil Diubah!', $m_departments); //return response
//     }

//     public function destroyDepartment($id)
//     {
//         $m_departments = m_department::find($id); //find post by ID
//         $m_departments->delete();  //delete post

//         //return response
//         return new MDepartmentsResource(true, 'Data Department Berhasil Dihapus!', null);
//     }

//     //************************************************************************************************ */

//     public function indexDirector()
//     {
//         //get all posts
//         $m_directors = m_director::latest()->paginate(5);

//         //return collection of posts as a resource
//         return new MDirectorResource(true, 'List Data Director', $m_directors);
//     }

//     public function storeDirector(Request $request)
//     {
//         //define validation rules
//         $validator = Validator::make($request->all(), [
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         //check if validation fails
//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);
//         }

//         //create post
//         $m_directors = m_director::create([
//             'title'     => $request->title,
//             'content'   => $request->content,
//         ]);

//         //return response
//         return new MDirectorResource(true, 'Data Director Berhasil Ditambahkan!', $m_directors);
//     }

//     public function showDirector($id)
//     {
//         $m_directors = m_director::find($id);        //find post by ID

//         return new MDirectorResource(true, 'Detail Data Director!', $m_directors);        //return single post as a resource
//     }

//     public function updateDirector(Request $request, $id)
//     {
//         $validator = Validator::make($request->all(), [  //define validation rules
//             'title'     => 'required',
//             'content'   => 'required',
//         ]);

//         if ($validator->fails()) {
//             return response()->json($validator->errors(), 422);  //check if validation fails
//         }

//         //find post by ID
//             $m_directors = m_director::find($id);
//             $m_directors->update([
//                 'title'     => $request->title,
//                 'content'   => $request->content,
//             ]);

//         return new MDirectorResource(true, 'Data Director Berhasil Diubah!', $m_directors); //return response
//     }

//     public function destroyDirector($id)
//     {
//         $m_directors = m_director::find($id); //find post by ID
//         $m_directors->delete();  //delete post

//         //return response
//         return new MDirectorResource(true, 'Data Director Berhasil Dihapus!', null);
//     }
// }
