<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
       if(Auth::guard('web')->check()){
            if(!Auth::user()->role){
                return redirect()->route('getLogin')->with('error','You have to be admin user to access this page');
            }
            // if(Auth::user()->hasRole('Administrator')){
            //     if ($request->is('users') || $request->is('users/*')) {
            //         return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
            //     }
            // }
            if(Auth::user()->hasRole('staffs')) {
                if ($request->is('users', 'office') || $request->is('users/*', 'office/*')) {
                    return redirect()->route('dashboard')->with('error1', 'You do not have permission to access this page');
                }
            }
        }else{
            return redirect()->route('getLogin')->with('error','You have to Sign In first to access this page');
        }
        // return $next($request)->header('Cache-Control','no-cache, no-store, max-age=0, must-revalidate')
        //                       ->header('Pragma','no-cache')
        //                       ->header('Expires','Sat 01 Jan 1990 00:00:00 GMT');

        $response = $next($request);

        // Prevent caching for sensitive pages
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}