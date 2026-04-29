<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class PartnerController extends Controller
{
    public function index()
    {

        $data = User::where('partner_status', '!=', 0)->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);

    }


    public function store(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        // CASE 1: user exists
        if ($user) {

            if ($user->partner_status != 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'This partner has already been added.'
                ], 422);
            }

            // update only partner_status
            $user->partner_status = 1;
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Partner status updated successfully.',
                'data' => $user
            ]);
        }

        // CASE 2: new user
        $user = User::create([
            'email' => $request->email,
            'name' => $request->name,
            'last_name' => $request->last_name,
            'password' => bcrypt($request->password),
            'contact_type' => 0,
            'partner_status' => 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'User created successfully.',
            'data' => $user
        ]);
    }

    public function show($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->name = $request->name ?? $user->name;
        $user->last_name = $request->last_name ?? $user->last_name;
        $user->email = $request->email ?? $user->email;

        if ($request->password) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ]);
    }
}
