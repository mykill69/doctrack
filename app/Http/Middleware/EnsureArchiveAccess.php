<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureArchiveAccess
{
    /**
     * Only the account with id 56 and the records_officer role is allowed to
     * use the Archive Logbook. Every other account receives HTTP 403.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || (int) $user->id !== 56 || $user->role !== 'records_officer') {
            abort(403, 'You are not authorized to access the Archive Logbook.');
        }

        return $next($request);
    }
}
