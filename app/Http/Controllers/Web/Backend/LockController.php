<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class LockController extends Controller
{
    /**
     * Show the lock screen
     */
    public function showLockScreen()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Automatically lock the screen when visiting this page
        session(['screen_locked' => true, 'locked_at' => now()]);

        return view('backend.lockscreen');
    }

    /**
     * Lock the screen
     */
    public function lock(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        session(['screen_locked' => true, 'locked_at' => now()]);

        return response()->json([
            'success' => true,
            'redirect' => route('screen.lock.show')
        ]);
    }

    /**
     * Unlock the screen with password
     */
    public function unlock(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // Verify password
        if (!Hash::check($request->password, Auth::user()->password)) {
            return back()->withErrors(['password' => 'The password you entered is incorrect.']);
        }

        // Unlock screen
        session()->forget('screen_locked');
        session()->forget('locked_at');

        return redirect()->route('dashboard')->with('success', 'Screen unlocked successfully.');
    }
}
