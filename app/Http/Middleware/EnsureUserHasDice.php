<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class EnsureUserHasDice
{
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        if ($user && $user->dice_count <= 0) {
            return redirect()->back()->withErrors(['error' => 'You have no dice left.']);
        }

        return $next($request);
    }
}
