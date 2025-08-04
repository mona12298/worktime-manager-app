<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\AdminLoginRequest;
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

    public function showAdminLoginForm(){
        return view('auth.admin_login');
    }

    public function loginAdmin(LoginRequest $request){
        $credentials = $request->only('email', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
        $user = Auth::guard('admin')->user();

        if ($user->is_admin) {
            $request->session()->regenerate();
            return redirect()->intended('/admin/attendance/list');
        } else {
            Auth::guard('admin')->logout();
            return redirect('/admin/login')
                ->withErrors(['email' => 'このアカウントには管理者権限がありません'])
                ->withInput($request->only('email'));
            }
        }
        return redirect('/admin/login')
        ->withErrors(['email' => 'ログインに失敗しました'])
        ->withInput($request->only('email'));
    }


}