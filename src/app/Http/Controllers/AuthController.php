<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\AdminLoginRequest;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function registerUser(RegisterRequest $request){
        $user=User::create([
            'name'=>$request->name,
            'email'=>$request->email,
            'password'=>Hash::make($request->password)
        ]);
        Auth::login($user);
        return redirect('/attendance');
    }

    public function showAdminLoginForm(Request $request){
        session()->invalidate();
        session()->regenerateToken();
        return view('auth.admin_login');
    }

    public function loginAdmin(AdminLoginRequest $request){
        $credentials = $request->only('email', 'password');

        if (!Auth::guard('admin')->attempt($credentials, $request->filled('remember'))) {
        return back()
            ->withErrors(['auth' => 'ログイン情報が登録されていません'])
            ->withInput($request->only('email'));
        }
        $request->session()->regenerate();

        return redirect('/admin/attendance/list');
    }

    public function logout(Request $request)
    {
        $isAdmin = Auth::guard('admin')->check();

        Auth::guard('admin')->logout();
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect($isAdmin ? '/admin/login' : '/login');
    }
}