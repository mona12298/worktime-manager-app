@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_login.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <h2>管理者ログイン</h2>
    <form action="/admin/login" method="post">
        @csrf
        <div class="form-item">
            <label for="email">メールアドレス</label>
            <input type="text" id="email" name="email" value="{{old('email')}}">
        </div>
        <div class="form__error">
            @error('email')
            {{ $message }}
            @enderror
        </div>
        <div class="form-item">
            <label for="password">パスワード</label>
            <input type="password" id="password" name="password" value="{{old('password')}}">
        </div>
        <div class="form__error">
            @error('password')
            {{ $message }}<br>
            @enderror
            @if ($errors->has('auth'))
            {{ $errors->first('auth') }}<br>
            @endif
        </div>
        <div class="form-btn">
            <input type="submit" value="管理者ログインする">
        </div>
    </form>
</div>
@endsection