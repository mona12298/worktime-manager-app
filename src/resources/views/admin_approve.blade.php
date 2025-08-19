@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_approve.css')}}">
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
            <div class="ttl-txt">勤怠詳細</div>
        </div>

        {{-- フォーム要素を削除し、全てテキスト表示に変更 --}}
        <table class="table">
            <tr>
                <th>名前</th>
                <td class="name">{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td class="year">{{ $displayYear }}</td>
                <td class="date">{{ $displayDate }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="table-display__in">{{ $display_clock_in }}</td>
                <td class="range-separator">〜</td>
                <td class="table-display__out">{{ $display_clock_out }}</td>
            </tr>

            {{-- 休憩 --}}
            @foreach ($pairedBreaks as $index => $break)
            <tr>
                <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                <td class="table-display__in">{{ $break['formatted_start'] ?? '-' }}</td>
                <td class="range-separator">〜</td>
                <td class="table-display__out">{{ $break['formatted_end'] ?? '-' }}</td>
            </tr>
            @endforeach

            <tr>
                <th>備考</th>
                <td class="table-textarea" colspan="3">{{ $latestRequest->reason ?? '-' }}</td>
            </tr>
        </table>

        <div class="correction">
            @if(session('success'))
                <div class="flash-success">{{ session('success') }}</div>
            @endif

            @if($hasPendingRequests)
                <form action="/stamp_correction_request/approve/{{ $attendance->id }}" method="post">
                    @csrf
                    <button type="submit" class="btn-approve">承認</button>
                </form>
            @else
                <button class="btn-approved" disabled>承認済み</button>
            @endif
        </div>


    </div>
</div>
@endsection
