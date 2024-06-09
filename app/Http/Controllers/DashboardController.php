<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserRequest;
use App\Models\User;
use App\Models\SupportMail;

class DashboardController extends Controller
{
    public function stat(){

        $total_requests =  UserRequest::count('id');
        $total_progress_requests =  UserRequest::where('status','in progress')->count('id');
        $total_done_requests =  UserRequest::where('status','done')->count('id');
        $total_users =  User::whereNot('user_role','1')->count('id');
        $total_tickets =  SupportMail::count('id');
        $total_pending_tickets =  SupportMail::where('status','On going')->count('id');

        return response()->json([
            'total_requests' => $total_requests,
            'total_progress_requests' => $total_progress_requests,
            'total_done_requests' => $total_done_requests,
            'total_users' => $total_users,
            'total_tickets' => $total_tickets,
            'total_pending_tickets' => $total_pending_tickets,
            'message' => 'Success'
        ],200);

    }
}
