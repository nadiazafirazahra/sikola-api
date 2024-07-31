<?php

namespace App\Http\Controllers\Api;

use App\Models\t_quota_transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\TQuotaTransactionResource;

class TQuotaTransactionController extends Controller{

    public function index()
    {
        //get all quota transaction
        $t_quota_transactions = t_quota_transaction::latest()->paginate(20);

        //return collection of quota transaction as a resource
        return new TQuotaTransactionResource(true, 'List Data Quota Transaction', $t_quota_transactions);
    }

    public function store(Request $request)
    {
        //define validation rules
        $validator = Validator::make($request->all(), [
            'title'     => 'required',
            'content'   => 'required',
        ]);

        //check if validation fails
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        //create quota transaction
        $t_quota_transaction = t_quota_transaction::create([
            'title'     => $request->title,
            'content'   => $request->content,
        ]);

        //return response
        return new TQuotaTransactionResource(true, 'Data Quota Transaction Berhasil Ditambahkan!', $t_quota_transaction);
    }

    public function show($id)
    {
        $t_quota_transaction = t_quota_transaction::find($id);   //find quota transaction by ID

        return new TQuotaTransactionResource(true, 'Detail Data Quota Transaction!', $t_quota_transaction);   //return single quota transaction as a resource
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [  //define validation rules
            'title'     => 'required',
            'content'   => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);  //check if validation fails
        }

        //find quota transaction by ID
            $t_quota_transaction = t_quota_transaction::find($id);
            $t_quota_transaction->update([
                'title'     => $request->title,
                'content'   => $request->content,
            ]);

        return new TQuotaTransactionResource(true, 'Data Quota Transactiong Berhasil Diubah!', $t_quota_transaction); //return response
    }

    public function destroy($id)
    {
        $t_quota_transaction = t_quota_transaction::find($id); //find quota transaction by ID
        $t_quota_transaction->delete();  //delete quota transaction

        //return response
        return new TQuotaTransactionResource(true, 'Data Quota Transaction Berhasil Dihapus!', null);
    }
}
