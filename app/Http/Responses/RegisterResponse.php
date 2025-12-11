<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;
use Inertia\Inertia;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        // Force a full page reload after registration to ensure fresh CSRF token
        // This prevents the "CSRF token mismatch" error when users try to add to cart
        return Inertia::location('/');
    }
}
