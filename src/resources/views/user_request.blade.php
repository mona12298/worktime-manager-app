@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user_request.css') }}">
@endsection

@section('header-menu')
<nav class="header__nav">
    <ul>
        <li><a href="/attendance">勤怠</a></li>
        <li><a href="/attendance/list">勤怠一覧</a></li>
        <li><a href="/stamp_correction_request/list">申請</a></li>
        <li>
            <form action="/logout" method="post">
                @csrf
                <input class="header-link" type="submit" value="ログアウト">
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
            <div class="ttl-txt">申請一覧</div>
        </div>

        <div class="tabs">
            <div class="tabs__item is-active">承認待ち</div>
            <div class="tabs__item">承認済み</div>
        </div>

        {{-- 承認待ち --}}
        <div class="tab-content active">
            <table class="table">
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($pendingRequests as $attendanceId => $group)
                    @php
                        $first = $group->first();
                        $date = optional(optional($first->attendance)->clock_in)->format('Y/m/d') ?? '日付なし';
                    @endphp
                    <tr>
                        <td>{{ $first->status_label }}</td>
                        <td>{{ $first->user->name }}</td>
                        <td>{{ $date }}</td>
                        <td>{{ $first->reason }}</td>
                        <td>{{ $first->created_at->format('Y/m/d') }}</td>
                        <td><a href="/attendance/{{ $attendanceId }}">詳細</a></td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- 承認済み --}}
        <div class="tab-content">
            <table class="table">
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>承認日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($approvedRequests as $attendanceId => $group)
                    @php
                        $first = $group->first();
                        $date = optional(optional($first->attendance)->clock_in)->format('Y/m/d') ?? '日付なし';
                    @endphp
                    <tr>
                        <td>{{ $first->status_label }}</td>
                        <td>{{ $first->user->name }}</td>
                        <td>{{ $date }}</td>
                        <td>{{ $first->reason }}</td>
                        <td>{{ optional($first->approved_at)->format('Y/m/d') ?? '-' }}</td>
                        <td><a href="/attendance/{{ $attendanceId }}">詳細</a></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const tabs = document.querySelectorAll('.tabs__item');
    const contents = document.querySelectorAll('.tab-content');

    tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => {
            tabs.forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            contents.forEach(c => c.classList.remove('active'));
            contents[index].classList.add('active');
        });
    });
});
</script>
@endsection
