@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_list.css')}}">
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
            <div class="ttl-txt">{{ $date->format('Y年n月j日') }}の勤怠</div>
        </div>
        @php
            $prevDate = Carbon\Carbon::parse($date)->subDay()->toDateString();
            $nextDate = Carbon\Carbon::parse($date)->addDay()->toDateString();
        @endphp

        <div class="change-day">
            <div class="change-day__before">
                <a href="{{ url()->current() }}?date={{ $prevDate }}">
                    <img src="{{asset('images/leftside.png')}}" alt="←">
                    前日
                </a>
            </div>
            <div class="change-day__current">
                <img src="{{asset('images/calender.png')}}" alt="カレンダー">
                <p>{{ $date->format('Y/m/d')}}</p>
            </div>
            <div class="change-day__after">
                <a href="{{ url()->current() }}?date={{ $nextDate }}">
                    翌日
                    <img src="{{asset('images/leftside.png')}}" alt="→">
                </a>
            </div>
        </div>
        <table class="table">
            <tr class="first-tr">
                <th>名前</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach ($attendances as $attendance)
            <tr>
                <td>{{ $attendance->user->name }}</td>
                <td>
                    @if ($attendance && $attendance->clock_in)
                    {{ $attendance->clock_in->format('H:i') }}
                    @endif
                </td>
                <td>
                    @if ($attendance && $attendance->clock_out)
                    {{ $attendance->clock_out->format('H:i') }}
                    @endif
                </td>
                <td>
                    {{ $attendance ? $attendance->breakHours() : '' }}
                </td>
                <td>
                    {{ $attendance ? $attendance->workedHours() : '' }}
                </td>
                <td>
                    @if ($attendance)
                    <a href="/attendance/{{ $attendance->id }}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    </div>
</div>
@endsection
