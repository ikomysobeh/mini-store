<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Support\Facades\Auth;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {// Inertia request → return Inertia redirect (JS will handle the navigation)
        if ($request->wantsJson() || $request->header('X-Inertia')) {
            return new JsonResponse([
                'two_factor' => false,
                'redirect'   => $this->redirectUrl(),
            ], 200);
        }

        // Normal HTTP request → classic redirect
        return redirect()->intended($this->redirectUrl());
    }

    protected function redirectUrl(): string
    {
        return Auth::user()->isAdmin() ? '/admin' : '/dashboard';
    }

}
