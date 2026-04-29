<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Subscription;
use App\Models\PromoCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;
use App\Models\Withdraw;
use App\Models\WithdrawRequest;

class ReferralEarningController extends Controller
{
    public function index_bkp(Request $request)
    {
        $referrerId = $request->referrer_id;

        /*
        |------------------------------------------------------------------
        | STEP 1: GET REFERRED USERS
        |------------------------------------------------------------------
        */
        $usersQuery = User::with('referredBy')
            ->whereNotNull('referred_by');

        if ($referrerId) {
            $usersQuery->where('referred_by', $referrerId);
        }

        $users = $usersQuery->get();

        /*
        |------------------------------------------------------------------
        | STEP 2: TOTAL EARNINGS (25% COMMISSION)
        |------------------------------------------------------------------
        */
        $transactionSums = Transaction::selectRaw('user_id, SUM(amount) as total_amount')
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->groupBy('user_id')
            ->pluck('total_amount', 'user_id');

        /*
        |------------------------------------------------------------------
        | STEP 3: WITHDRAWN (PAID ONLY)
        |------------------------------------------------------------------
        */
        $withdrawPaid = Withdraw::selectRaw('user_id, SUM(amount) as total_paid')
            ->groupBy('user_id')
            ->pluck('total_paid', 'user_id');

        /*
        |------------------------------------------------------------------
        | STEP 4: PENDING (RESERVED BALANCE)
        |------------------------------------------------------------------
        */
        $withdrawPending = WithdrawRequest::selectRaw('user_id, SUM(amount) as total_pending')
            ->where('status', 'pending')
            ->groupBy('user_id')
            ->pluck('total_pending', 'user_id');

        /*
        |------------------------------------------------------------------
        | FINAL RESPONSE
        |------------------------------------------------------------------
        */
        $results = [];
        $grandTotalEarnings = 0;

        foreach ($users as $user) {

            $totalAmount = $transactionSums[$user->id] ?? 0;

            if ($totalAmount <= 0) {
                continue;
            }

            // Earnings (25%)
            $earning = $totalAmount * 0.25;
            $grandTotalEarnings += $earning;

            // Withdraws
            $totalWithdrawn = $withdrawPaid[$user->id] ?? 0;

            // Pending (locked balance)
            $totalPending = $withdrawPending[$user->id] ?? 0;

            // Available balance
            $availableBalance = $earning - ($totalWithdrawn + $totalPending);

            $referrer = $user->referredBy;

            $results[] = [
                // Referrer info
                'referrer_id' => $user->referred_by,
                'referrer_name' => $referrer ? ($referrer->name . ' ' . $referrer->last_name) : null,
                'referrer_email' => $referrer->email ?? null,

                // User info
                'referred_user_id' => $user->id,
                'referred_user_name' => $user->name . ' ' . $user->last_name,
                'referred_user_email' => $user->email,

                // Financials
                'total_earnings' => $earning,
                'total_withdrawn' => $totalWithdrawn,
                'total_pending_withdraw' => $totalPending,
                'available_balance' => $availableBalance,
            ];
        }

        return response()->json([
            'success' => true,
            'total_earnings_all_users' => $grandTotalEarnings,
            'count' => count($results),
            'data' => $results
        ]);
    }

public function index(Request $request)
{
    $referrerId = $request->referrer_id;

    /*
    |------------------------------------------------------------------
    | STEP 1: GET REFERRER USERS
    |------------------------------------------------------------------
    */
    $referrerIds = User::whereNotNull('referred_by')
        ->pluck('referred_by')
        ->unique();

    $usersQuery = User::with('referredBy')
        ->whereIn('id', $referrerIds);

    if ($referrerId) {
        $usersQuery->where('id', $referrerId);
    }

    $users = $usersQuery->get();

    /*
    |------------------------------------------------------------------
    | STEP 2: GROUP REFERRED USERS WITH THEIR DETAILS
    |------------------------------------------------------------------
    */
    $referralMap = User::whereNotNull('referred_by')
        ->get()
        ->groupBy('referred_by');

    $results = [];
    $grandTotal = 0;

    foreach ($users as $user) {
        $referredUsers = $referralMap[$user->id] ?? collect();
        
        if ($referredUsers->isEmpty()) {
            continue;
        }

        /*
        |--------------------------------------------------------------
        | STEP 3: CALCULATE EARNINGS FOR EACH REFERRED USER SEPARATELY
        |         USING THEIR OWN referred_at DATE
        |--------------------------------------------------------------
        */
        $totalEarning = 0;
        $referredUsersDetails = [];
        
        foreach ($referredUsers as $referredUser) {
            // Build query for this specific referred user
            $query = Transaction::where('user_id', $referredUser->id)
                ->where('amount', '>', 0);

            // Use the REFERRED USER'S referred_at date
            if ($referredUser->referred_at) {
                $query->whereDate('created_at', '>=', $referredUser->referred_at);
            }

            $userTotalAmount = $query->sum('amount');
            $userEarning = $userTotalAmount * 0.25;
            
            if ($userTotalAmount > 0) {
                $totalEarning += $userEarning;
            }

            // Add referred user details
            $referredUsersDetails[] = [
                'user_id' => $referredUser->id,
                'name' => $referredUser->name . ' ' . ($referredUser->last_name ?? ''),
                'email' => $referredUser->email,
                'referred_at' => $referredUser->referred_at,
                'total_amount' => $userTotalAmount,
                'earning' => $userEarning,
            ];
        }

        if ($totalEarning <= 0) {
            continue;
        }

        $grandTotal += $totalEarning;

        $results[] = [
            // Referrer details
            'referrer_id' => $user->id,
            'referrer_name' => $user->name . ' ' . ($user->last_name ?? ''),
            'referrer_email' => $user->email,
            
            // Summary
            'total_earning' => $totalEarning,
            'referred_users_count' => $referredUsers->count(),
            
            // Referred users details
            'referred_users' => $referredUsersDetails,
        ];
    }

    return response()->json([
        'success' => true,
        'grand_total_earnings' => $grandTotal,
        'count' => count($results),
        'data' => $results
    ]);
}

   public function show($id)
{
    /*
    |------------------------------------------------------------------
    | STEP 1: GET REFERRER USER
    |------------------------------------------------------------------
    */
    $user = User::findOrFail($id);

    /*
    |------------------------------------------------------------------
    | STEP 2: GET REFERRED USERS WITH THEIR DETAILS
    |------------------------------------------------------------------
    */
    $referredUsers = User::where('referred_by', $user->id)
        ->get(['id', 'name', 'last_name', 'email', 'referred_at']);

    if ($referredUsers->isEmpty()) {
        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name . ' ' . $user->last_name,
                'email' => $user->email,
            ],
            'total_earnings' => 0,
            'transaction_count' => 0,
            'transactions' => []
        ]);
    }

    $referredUserIds = $referredUsers->pluck('id')->toArray();
    
    // Create map of referred users with all their details
    $referredUsersMap = [];
    foreach ($referredUsers as $referredUser) {
        $referredUsersMap[$referredUser->id] = [
            'id' => $referredUser->id,
            'name' => trim($referredUser->name . ' ' . ($referredUser->last_name ?? '')),
            'email' => $referredUser->email,
            'referred_at' => $referredUser->referred_at,
        ];
    }

    /*
    |------------------------------------------------------------------
    | STEP 3: GET ALL TRANSACTIONS AND FILTER BY EACH USER'S referred_at
    |------------------------------------------------------------------
    */
    $allTransactions = Transaction::whereIn('user_id', $referredUserIds)
        ->whereNotNull('amount')
        ->where('amount', '>', 0)
        ->latest()
        ->get();

    // Filter transactions based on each referred user's referred_at
    $filteredTransactions = $allTransactions->filter(function ($transaction) use ($referredUsersMap) {
        $referredUserData = $referredUsersMap[$transaction->user_id] ?? null;
        
        if (!$referredUserData || !$referredUserData['referred_at']) {
            return true; // No referred_at date, include all transactions
        }
        
        return $transaction->created_at >= $referredUserData['referred_at'];
    });

    /*
    |------------------------------------------------------------------
    | STEP 4: BUILD RESPONSE
    |------------------------------------------------------------------
    */
    $results = [];
    $totalEarnings = 0;

    foreach ($filteredTransactions as $tx) {
        $earning = $tx->amount * 0.25;
        $totalEarnings += $earning;
        
        $referredUserData = $referredUsersMap[$tx->user_id] ?? null;

        $results[] = [
            'transaction_id' => $tx->id,
            'gateway_transaction_id' => $tx->transaction_id,
            'plan_id' => $tx->plan_id,
            'amount' => $tx->amount,
            'earning' => $earning,
            'date' => $tx->created_at->toDateTimeString(),
            
            // Referred user details
            'referred_user' => $referredUserData ? [
                'id' => $referredUserData['id'],
                'name' => $referredUserData['name'],
                'email' => $referredUserData['email'],
                'referred_at' => $referredUserData['referred_at'],
            ] : null,
        ];
    }

    /*
    |------------------------------------------------------------------
    | STEP 5: RESPONSE
    |------------------------------------------------------------------
    */
    return response()->json([
        'success' => true,

        // Referrer user info
        'user' => [
            'id' => $user->id,
            'name' => trim($user->name . ' ' . ($user->last_name ?? '')),
            'email' => $user->email,
        ],

        // summary
        'total_earnings' => $totalEarnings,
        'transaction_count' => count($results),

        // details
        'transactions' => $results
    ]);
}


    public function userEarnings_bkp()
    {
        $user = auth()->user();

        /*
        |------------------------------------------------------------------
        | STEP 1: USER TOTAL EARNINGS (25%)
        |------------------------------------------------------------------
        */
        $totalAmount = Transaction::where('user_id', $user->id)
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->sum('amount');

        $totalEarnings = $totalAmount * 0.25;

        /*
        |------------------------------------------------------------------
        | STEP 2: ALL TRANSACTIONS (EARNING SOURCE)
        |------------------------------------------------------------------
        */
        $transactions = Transaction::where('user_id', $user->id)
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->latest()
            ->get()
            ->map(function ($tx) {
                return [
                    'transaction_id' => $tx->id,
                    'gateway_transaction_id' => $tx->transaction_id,
                    'amount' => $tx->amount,
                    'earning' => $tx->amount * 0.25,
                    'plan_id' => $tx->plan_id,
                    'date' => $tx->created_at->toDateTimeString(),
                ];
            });

        /*
        |------------------------------------------------------------------
        | STEP 3: WITHDRAWN (PAID)
        |------------------------------------------------------------------
        */
        $totalWithdrawn = Withdraw::where('user_id', $user->id)->sum('amount');

        /*
        |------------------------------------------------------------------
        | STEP 4: PENDING (RESERVED BALANCE)
        |------------------------------------------------------------------
        */
        $totalPending = WithdrawRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('amount');

        /*
        |------------------------------------------------------------------
        | STEP 5: AVAILABLE BALANCE
        |------------------------------------------------------------------
        */
        $availableBalance = $totalEarnings - ($totalWithdrawn + $totalPending);

        return response()->json([
            'success' => true,

            // GLOBAL SUMMARY
            'total_earnings' => $totalEarnings,
            'total_withdrawn' => $totalWithdrawn,
            'total_pending_withdraw' => $totalPending,
            'available_balance' => $availableBalance,

            // DETAIL LIST
            'transactions' => $transactions,
        ]);
    }

    public function myEarnings()
    {
        $user = auth()->user();
    
        /*
        |------------------------------------------------------------------
        | STEP 1: GET REFERRED USERS WITH THEIR DETAILS
        |------------------------------------------------------------------
        */
        $referredUsers = User::where('referred_by', $user->id)
            ->get(['id', 'name', 'last_name', 'email', 'created_at', 'referred_at']);
    
        if ($referredUsers->isEmpty()) {
            return response()->json([
                'success' => true,
                'total_earning' => 0,
                'current_month_earning' => 0,
                'average_monthly_earning' => 0,
                'transactions' => []
            ]);
        }
    
        $referredUserIds = $referredUsers->pluck('id')->toArray();
    
        /*
        |------------------------------------------------------------------
        | STEP 2: CREATE MAP OF REFERRED USERS WITH ALL DETAILS
        |------------------------------------------------------------------
        */
        $referredUsersMap = [];
        $userCutoffDates = [];
        
        foreach ($referredUsers as $referredUser) {
            // Store full user details
            $referredUsersMap[$referredUser->id] = [
                'id' => $referredUser->id,
                'name' => trim($referredUser->name . ' ' . ($referredUser->last_name ?? '')),
                'email' => $referredUser->email,
                'referred_at' => $referredUser->referred_at,
                'created_at' => $referredUser->created_at,
            ];
            
            // Determine cutoff date for this user
            $cutoffDate = $referredUser->referred_at ?? $referredUser->created_at;
            $userCutoffDates[$referredUser->id] = $cutoffDate;
        }
    
        /*
        |------------------------------------------------------------------
        | STEP 3: FETCH AND FILTER TRANSACTIONS
        |------------------------------------------------------------------
        */
        $allTransactions = Transaction::whereIn('user_id', $referredUserIds)
            ->whereNotNull('amount')
            ->where('amount', '>', 0)
            ->latest()
            ->get();
    
        // Filter transactions based on cutoff date for each user
        $filteredTransactions = $allTransactions->filter(function ($transaction) use ($userCutoffDates) {
            $cutoffDate = $userCutoffDates[$transaction->user_id] ?? null;
            
            if (!$cutoffDate) {
                return true;
            }
            
            return $transaction->created_at >= $cutoffDate;
        });
    
        /*
        |------------------------------------------------------------------
        | STEP 4: FORMAT TRANSACTIONS WITH REFERRED USER DETAILS
        |------------------------------------------------------------------
        */
        $transactions = $filteredTransactions->map(function ($tx) use ($referredUsersMap) {
            $referredUser = $referredUsersMap[$tx->user_id] ?? null;
            
            return [
                'transaction_id' => $tx->id,
                'amount' => $tx->amount,
                'earning' => $tx->amount * 0.25,
                'date' => $tx->created_at->toDateTimeString(),
                
                // Referred user details
                'referred_user' => $referredUser ? [
                    'id' => $referredUser['id'],
                    'name' => $referredUser['name'],
                    'email' => $referredUser['email'],
                    'referred_at' => $referredUser['referred_at'],
                ] : null,
            ];
        })->values();
    
        /*
        |------------------------------------------------------------------
        | STEP 5: CALCULATE EARNINGS
        |------------------------------------------------------------------
        */
        $totalAmount = $filteredTransactions->sum('amount');
        $totalEarning = $totalAmount * 0.25;
    
        // Current month earnings
        $currentMonthAmount = $filteredTransactions
            ->filter(function ($tx) {
                return $tx->created_at->month === now()->month &&
                       $tx->created_at->year === now()->year;
            })
            ->sum('amount');
        
        $currentMonthEarning = $currentMonthAmount * 0.25;
    
        // Average monthly earning
        $firstTransactionDate = $filteredTransactions->min('created_at');
        
        $monthsActive = 1;
        if ($firstTransactionDate) {
            $monthsActive = max(1, now()->diffInMonths($firstTransactionDate) + 1);
        }
        
        $averageMonthlyEarning = $totalEarning / $monthsActive;
    
        /*
        |------------------------------------------------------------------
        | STEP 6: RESPONSE
        |------------------------------------------------------------------
        */
        return response()->json([
            'success' => true,
            'total_earning' => $totalEarning,
            'current_month_earning' => $currentMonthEarning,
            'average_monthly_earning' => round($averageMonthlyEarning, 2),
            'transactions' => $transactions
        ]);
    }

   public function updateRIB(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'rib' => 'nullable|string|max:255',
            'bic' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'rib_first_name' => 'nullable|string|max:255',
            'rib_last_name' => 'nullable|string|max:255',
        ]);

        $user->update([
            'rib' => $request->rib,
            'bic' => $request->bic,
            'iban' => $request->iban,
            'rib_first_name' => $request->rib_first_name,
            'rib_last_name' => $request->rib_last_name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bank details updated successfully',
            'data' => $user
        ]);
    }
}