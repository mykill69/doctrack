<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Document;
use App\Models\Office;
use App\Models\Log;
use App\Models\RoutingSlip;
use App\Models\RouteDocument;



class MasterController extends Controller
{
    
  public function logout()
    {
        if (auth()->check()) {
            auth()->logout();
            return redirect()->route('getLogin')->with('success', 'You have been Successfully Logged Out');
        } else {
            return redirect()->route('dashboard')->with('error', 'No authenticated user to log out');
        }
    }

    }

