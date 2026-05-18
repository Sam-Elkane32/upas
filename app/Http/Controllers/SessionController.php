<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionController extends Controller
{
    /**
     * Keep session alive and refresh CSRF token
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function keepAlive(Request $request)
    {
        // Touch the session to extend its lifetime
        $request->session()->regenerateToken();
        
        return response()->json([
            'success' => true,
            'csrf_token' => csrf_token(),
            'session_id' => Session::getId(),
        ]);
    }

    /**
     * Clear the welcome popup session flag
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearWelcomePopup(Request $request)
    {
        $request->session()->forget('show_welcome_popup');
        
        return response()->json([
            'success' => true,
        ]);
    }
}

