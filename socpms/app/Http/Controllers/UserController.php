<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return view('admin.manage-accounts', compact('users'));
    }

    public function show()
    {
        $user = Auth::user();
        return view('user.account', compact('user'));
    }

    public function update(Request $request)
    {
        $user = User::find(Auth::id()); // Use the User model to find the current user

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->fill($validated); // Use fill() to mass assign validated data

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save(); // Now save() should work

        return redirect()->route('user.account')
            ->with('success', 'Account updated successfully');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'user_type' => 'required|in:admin,user'
        ]);

        $user = new User($validated);
        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('admin.accounts')
            ->with('success', 'User created successfully');
    }
}
