@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/user_attendance.css')}}">
@endsection

@section('header-menu')
<nav class="header__nav">
    <ul>
        <li>
            <a href="/attendance">勤怠</a>
        </li>
        <li>
            <a href="/attendance/list">勤怠一覧</a>
        </li>
        <li>
            <a href="/stamp_correction_request/list">申請</a>
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
        <div class="current-condition">
            <p>{{ $currentCondition }}</p>
        </div>
        <div class="date">
            <p>{{ $date }}</p>
        </div>
        <div class="time" id="currentTime">
            <p>{{ $time }}</p>
        </div>
        <div class="clock-action">
        @php
            $actionClassMap = [
                '出勤' => 'black',
                '退勤' => 'black',
                '休憩入' => 'white',
                '休憩戻' => 'white',
            ];
        @endphp
            @foreach ($actions as $action)
            @if ($action['type'] === 'input')
                <form action="/attendance" method="post">
                @csrf
                    <input type="hidden" name="action" value="{{ $action['label'] }}">
                    <input type="submit" value="{{ $action['label'] }}"
                    class="clock-action--{{ $actionClassMap[$action['label']] }}">
                </form>
                @elseif ($action['type'] === 'p')
                    <p>{{ $action['label'] }}</p>
                @endif
            @endforeach
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    setInterval(function(){
        document.getElementById('currentTime').innerText = new Date().toLocaleTimeString('ja-JP', {hour:'2-digit',minute:'2-digit'})
    }, 1000);
</script>
@endsection