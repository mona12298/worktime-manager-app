@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/user_login.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <h2>ログイン</h2>
    <form action="/login" method="post">
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
            {{ $message }}
            @enderror
        </div>
        <div class="form-btn">
            <input type="submit" value="ログインする">
        </div>
    </form>
    <div class="register-link">
        <a href="/register">会員登録はこちら</a>
    </div>
</div>
@endsection
