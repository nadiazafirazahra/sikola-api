<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\m_employee;
use App\t_spkl_detail;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Config;
use Carbon\Carbon;
class WelcomeApiController extends Controller {
	/*
	|--------------------------------------------------------------------------
	| Welcome Controller
	|--------------------------------------------------------------------------
	|
	| This controller renders the "marketing page" for the application and
	| is configured to only allow guests. Like most of the other sample
	| controllers, you are free to modify or remove it as you desire.
	|
	*/

	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */

	/**
	 * Show the application welcome screen to the user.
	 *
	 * @return Response
	 */
	public function index()
	{
		return response()->json('welcome');
	}

}
