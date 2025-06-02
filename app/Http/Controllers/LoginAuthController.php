<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;


class LoginAuthController extends Controller

{
    public function getLogin()
    {
        return view('login.login');
    }
    
    public function postLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $validatedAdmin = auth()->attempt([
            'username' => $request->username,
            'password' => $request->password,
        ]);

        if ($validatedAdmin) {
            $route = (auth()->user()->role == "Administrator") || (auth()->user()->role == "staff") ? 'dashboard' : 'dashboard';
            return redirect()->route($route)->with('success', 'Login Successfully');
        }else {
            return redirect()->back()->with('error', 'Invalid Credentials');
        }
    }
    
}


