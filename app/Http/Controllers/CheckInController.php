<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CheckIn;
use Illuminate\Http\Request;

class CheckInController extends Controller
{
    public function checkUser(Request $request)
    {
        $phone = $request->input('phone');

        $user = User::where('phone', $phone)->first();

        if (!$user) {
            return response()->json(['userExists' => false]);
        }

        $hasCheckIn = CheckIn::where('user_id', $user->id)->whereNull('check_out')->exists();

        return response()->json([
            'userExists' => true,
            'hasCheckIn' => $hasCheckIn
        ]);
    }

    public function newForm()
    {
        // Logic for new form creation
        return view('Process');
    }

    public function newFormPreFilled(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();
        // Pre-fill form with user data
        return view('new-form-pre-filled', compact('user'));
    }

    public function viewCheckIn(Request $request)
    {
        $phone = $request->input('phone');
        $user = User::where('phone', $phone)->first();
        $checkIns = CheckIn::where('user_id', $user->id)->whereNull('check_out')->with('pet')->get();
        return view('view-check-in', compact('checkIns'));
    }
}
