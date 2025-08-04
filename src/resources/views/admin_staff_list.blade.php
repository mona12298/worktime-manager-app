@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_staff_list.css')}}">
@endsection

@section('header-menu')
<nav class="header__nav">
    <ul>
        <li>
            <a href="/admin/attendance/list">勤怠一覧</a>
        </li>
        <li>
            <a href="/admin/staff/list">スタッフ一覧</a>
        </li>
        <li>
            <a href="/stamp_correction_request/list">申請一覧</a>
        </li>
        <li>
            <form action="/logout" method="post">
                @csrf
                <input class="header-link"  type="submit" value="ログアウト" >
            </form>
        </li>
    </ul>
</nav>
@endsection

@section('content')
<div class="wrapper">
    <div class="content">
        <div class="ttl">
            <div class="ttl-left"></div>
            <div class="ttl-txt">スタッフ一覧</div>
        </div>
        <table class="table">
            <tr class="first-tr">
                <th>名前</th>
                <th>メールアドレス</th>
                <th>月次勤怠</th>
            </tr>
            @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>
                    @if ($users)
                    <a href="/admin/attendance/staff/{{ $user->id}}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
