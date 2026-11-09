<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $userRole = $user->role instanceof \BackedEnum ? $user->role->value : (string) $user->role;

        if (! in_array($userRole, $roles, true) && $userRole !== 'admin') {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized access to this clinical portal.');
        }

        return $next($request);
    }
}
