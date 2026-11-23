<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (! Auth::attempt($data, true)) {
            return response()->json(['message' => 'Wrong email or password.', 'errors' => ['email' => ['Wrong email or password.']]], 422);
        }
        $request->session()->regenerate();

        return Auth::user();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->noContent();
    }

    public function me(Request $request)
    {
        return $request->user();
    }

    public function profile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['name' => 'required|string|max:80', 'email' => 'required|email|unique:users,email,'.$user->id]);
        $user->update($data);

        return $user;
    }

    public function password(Request $request)
    {
        $user = $request->user();
        $data = $request->validate(['current' => 'required', 'password' => 'required|string|min:8|confirmed']);

        if (! Hash::check($data['current'], $user->password)) {
            return response()->json(['message' => 'Current password is wrong.', 'errors' => ['current' => ['Current password is wrong.']]], 422);
        }
        $user->update(['password' => $data['password']]);

        return response()->noContent();
    }
}
