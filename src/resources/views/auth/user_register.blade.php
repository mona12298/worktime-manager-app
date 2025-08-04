@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/user_register.css')}}">
@endsection

@section('content')
<div class="wrapper">
    <h2>会員登録</h2>
    <form action="/register" method="post">
    @csrf
        <div class="form-item">
            <label for="name">名前</label>
            <input type="text" id="name" name="name" value="{{old('name')}}">
        </div>
        <div class="form__error">
            @error('name')
            {{ $message }}
            @enderror
        </div>
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
        <div class="form-item">
            <label for="password_confirmation">パスワード確認</label>
            <input type="password" id="password_confirmation" name="password_confirmation" value="{{old('password_confirmation')}}">
        </div>
        <div class="form__error">
            @error('password_confirmation')
            {{ $message }}
            @enderror
        </div>
        <div class="form-btn">
            <input type="submit" value="登録する">
        </div>
    </form>
    <div class="login-link">
        <a href="/login">ログインはこちら</a>
    </div>
</div>
@endsection