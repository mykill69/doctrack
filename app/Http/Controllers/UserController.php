<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Document;
use App\Models\Office;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\RoutingSlip;

class UserController extends Controller
{
    // Method to view all users
public function userView()
{
    // Get logged-in user info
    $user = auth()->user();
    $userRole = $user->role ?? null;

    // Fetch all users and offices
    $users = User::all();
    $offices = Office::orderBy('office_name', 'asc')->get();

    // Routing slip counts
    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = ($userRole === 'super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = ($userRole === 'records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    // Return the view with all necessary data
    return view('users.user', compact(
        'users',
        'offices',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount'
    ));
}

    public function addUser(Request $request)
{
    // Validate the request data with custom messages
    $request->validate([
        'email' => 'required|string|max:255|unique:users,email', // Check for unique username
        'fname' => 'required|string|max:255',
        'mname' => 'nullable|string|max:255',
        'lname' => 'required|string|max:255',
        'department' => 'required|string|max:255',
        'role' => 'required|string|max:255',
        'password' => 'required|string|min:8', // Minimum length changed to 8
    ], [
        'email.unique' => 'This email address is already taken. Please choose another one.', // Custom error message
    ]);

    // Create a new user
    $password = Hash::make($request->input('password'));
    User::create([
        'email' => $request->email,
        'fname' => $request->fname,
        'mname' => $request->mname,
        'lname' => $request->lname,
        'department' => $request->department,
        'role' => $request->role,
        'password' => $password,
    ]);


    
    // Redirect back with success message or do any additional logic here
    return redirect()->back()->with('success', 'User added successfully.');
}

public function userEdit($id)
{
    $user = User::find($id); // Find the user by ID
    $offices = Office::all();
    $office = $user->department;
    if ($user) {
        return view('users.editUser', compact('user','offices', 'office')); // Pass the user data to the edit view
    }

    return redirect()->back()->with('error', 'User not found');
}

public function userUpdate(Request $request, $id)
{
    // Validate the incoming request
    $validator = Validator::make($request->all(), [
        'email' => 'required|string|max:255|unique:users,email,' . $id . ',id',
        'password' => 'nullable|string|min:8|confirmed', // Make password nullable and use 'confirmed' for password confirmation
        'department' => 'required|string|max:255',
        'role' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    // Find the user by ID
    $user = User::find($id);
    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    // Update user details
    $user->fname = $request->input('fname');
    $user->mname = $request->input('mname');
    $user->lname = $request->input('lname');
    $user->email = $request->input('email');
    $user->department = $request->input('department');
    $user->role = $request->input('role');
    $user->position = $request->input('position');

    // Only update the password if it's provided and not empty
    if (!empty($request->input('password'))) {
        $user->password = Hash::make($request->input('password'));
    }

    // Save the updated user details
    $user->save();

    // Redirect with a success message
    return redirect()->route('userView')->with('success', 'User updated successfully.');
}


    public function deleteUser($id)
{
    // Find the user by ID
    $user = User::findOrFail($id);

    // Delete the user
    $user->delete();

    // Redirect back with a success message
    return redirect()->route('userView')->with('success', 'User deleted successfully.');
}

public function updateDpa(Request $request)
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Unauthenticated'], 401);
    }

    $user->dpa = $request->dpa === null ? null : 1;
    $user->save();

    return response()->json(['message' => 'DPA status updated.']);
}

}








