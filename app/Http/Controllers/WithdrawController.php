<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WithdrawRequest;
use App\Models\Withdraw;
use DB;
use App\Models\Transaction;

class WithdrawController extends Controller
{
    public function index()
    {
        $data = WithdrawRequest::with('user')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function approve(Request $req, $id)
    {
        $request = WithdrawRequest::findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Already processed'
            ], 400);
        }

        \DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------
            | STEP 1: Create Withdraw Record (ACTUAL PAYMENT)
            |--------------------------------------------------------------
            */
            Withdraw::create([
                'user_id' => $request->user_id,
                'withdraw_request_id' => $request->id,
                'amount' => $request->amount,
                'method' => $request->method,
                'transaction_reference' => $req->transaction_reference, // optional
                'paid_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------
            | STEP 2: Update Request → DIRECTLY PAID
            |--------------------------------------------------------------
            */
            $request->update([
                'status' => 'paid',
                'processed_at' => now(),
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Request approved & marked as paid'
            ]);

        } catch (\Exception $e) {

            \DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function reject(Request $req, $id)
    {
        $request = WithdrawRequest::findOrFail($id);

        $request->update([
            'status' => 'rejected',
            'admin_note' => $req->admin_note,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Request rejected'
        ]);
    }

    public function manualWithdraw(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:1',
            'method' => 'nullable|string',
            'transaction_reference' => 'nullable|string',
        ]);

        $userId = $request->user_id;

        /*
        |--------------------------------------------------------------
        | CALCULATE BALANCE
        |--------------------------------------------------------------
        */
        $totalEarnings = Transaction::where('user_id', $userId)
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->sum('amount') * 0.25;

        $totalWithdrawn = Withdraw::where('user_id', $userId)->sum('amount');

        $availableBalance = $totalEarnings - $totalWithdrawn;

        if ($request->amount > $availableBalance) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'available_balance' => $availableBalance
            ], 400);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------
            | CREATE WITHDRAW (PAID DIRECTLY)
            |--------------------------------------------------------------
            */
            $withdraw = Withdraw::create([
                'user_id' => $userId,
                'amount' => $request->amount,
                'method' => "Bank",
                'transaction_reference' => $request->transaction_reference,
                'paid_at' => now(),
            ]);

            /*
            |--------------------------------------------------------------
            | OPTIONAL: ALSO CREATE REQUEST RECORD (FOR HISTORY)
            |--------------------------------------------------------------
            */
            WithdrawRequest::create([
                'user_id' => $userId,
                'amount' => $request->amount,
                'method' => "Bank",
                'account_number' => null,
                'account_title' => null,
                'status' => 'paid',
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Manual withdraw processed',
                'data' => $withdraw
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }
}
