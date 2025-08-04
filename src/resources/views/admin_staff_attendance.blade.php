@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_staff_attendance.css')}}">
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
            <div class="ttl-txt">{{$user->name}}さんの勤怠</div>
        </div>
        @php
            use Carbon\Carbon;
            $current = Carbon::create($year, $month, 1);
            $prev = $current->copy()->subMonth();
            $next = $current->copy()->addMonth();
        @endphp
        <div class="change-month">
            <div class="change-month__before">
                <a href="{{ url()->current() }}?year={{ $prev->year }}&month={{ $prev->month }}">
                    <img src="{{asset('images/leftside.png')}}" alt="←">
                    前月
                </a>
            </div>
            <div class="change-month__current">
                <img src="{{asset('images/calender.png')}}" alt="カレンダー">
                <p>{{ $current->format('Y/m')}}</p>
            </div>
            <div class="change-month__after">
                <a href="{{ url()->current() }}?year={{ $next->year }}&month={{ $next->month }}">
                    翌月
                    <img src="{{asset('images/leftside.png')}}" alt="→">
                </a>
            </div>
        </div>
        <table class="table">
            <tr class="first-tr">
                <th>日付</th>
                <th>出勤</th>
                <th>退勤</th>
                <th>休憩</th>
                <th>合計</th>
                <th>詳細</th>
            </tr>
            @foreach ($dates as $date)
            @php
                $key = $date->format('Y-m-d');
                $attendance = $attendanceMap[$key] ?? null;
            @endphp
            <tr>
                <td>{{ $date->locale('ja')->isoFormat('MM/DD(dd)')}}</td>
                <td>{{ optional($attendance)->clock_in ? $attendance->clock_in->format('H:i') : '' }}</td>
                <td>{{ optional($attendance)->clock_out ? $attendance->clock_out->format('H:i') : '' }}</td>
                <td>{{ $attendance ? $attendance->breakHours() : '' }}</td>
                <td>{{ $attendance ? $attendance->workedHours() : '' }}</td>
                <td>
                    @if ($attendance)
                    <a href="/attendance/{{ $attendance->id}}">詳細</a>
                    @endif
                </td>
            </tr>
            @endforeach
        </table>
    <div class="csv-btn">
        <a href="/admin/attendance/staff/export/{{ $user->id }}?year={{ $year }}&month={{ $month }}">CSV出力</a>
    </div>
    </div>
</div>
@endsection