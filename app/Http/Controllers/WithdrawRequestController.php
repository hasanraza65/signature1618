<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WithdrawRequest;
use App\Models\Transaction;
use App\Models\Withdraw;

class WithdrawRequestController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100', // set your min limit
          
            'account_number' => 'required|string',
            'account_title' => 'required|string',
        ]);

        $user = auth()->user();

        /*
        |--------------------------------------------------------------
        | OPTIONAL: Prevent multiple pending requests
        |--------------------------------------------------------------
        */
        $hasPending = WithdrawRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending request'
            ], 400);
        }

        /*
        |--------------------------------------------------------------
        | OPTIONAL: Check balance (you should replace with real logic)
        |--------------------------------------------------------------
        */
        $totalEarnings = Transaction::where('user_id', $user->id)->sum('amount') * 0.25;
        $totalWithdrawn = Withdraw::where('user_id', $user->id)->sum('amount');

        $availableBalance = $totalEarnings - $totalWithdrawn;

        if ($request->amount > $availableBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'available_balance' => $availableBalance
            ], 400);
        }

        $withdraw = WithdrawRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'method' => "Bank",
            'account_number' => $request->account_number,
            'account_title' => $request->account_title,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdraw request submitted',
            'data' => $withdraw
        ]);
    }

    public function myRequests()
    {
        $data = WithdrawRequest::where('user_id', auth()->id())
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
