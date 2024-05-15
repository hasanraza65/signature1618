<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use Auth;

class TransactionController extends Controller
{
    public function index(){

        $data = Transaction::with(['plan','userDetail'])
        ->where('user_id',Auth::user()->id)
        ->orderBy('id','desc')
        ->get();

        return response()->json([
            'data' => $data,
            'message' => 'Success'
        ],200);
        

    }
}
