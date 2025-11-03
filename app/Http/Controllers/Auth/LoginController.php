<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Http\Requests\LoginRequest;

class LoginController extends Controller
{
    public function __invoke(LoginRequest $request)
    {
        $request->authenticate();

        $request->session()->regenerate();

        // THIS IS THE ONLY LINE YOU NEED
        return redirect()->intended(Auth::user()->isAdmin() ? '/admin' : '/dashboard');
    }
}
