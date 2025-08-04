@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_request.css')}}">
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
            <div class="ttl-txt">申請一覧</div>
        </div>
        <div class="tabs">
            <div class="tabs__item is-active">承認待ち</div>
            <div class="tabs__item">承認済み</div>
        </div>
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
                @foreach ($correctionRequests->where('status', 'pending') as $req)
                <tr>
                    <td>{{$req->status_label}}</td>
                    <td>{{$req->user->name}}</td>
                    <td>{{$req->attendance->date->format('Y-m-d')}}</td>
                    <td>{{$req->reason}}</td>
                    <td>{{optional($req->approved_at)->format('Y-m-d')}}</td>
                    <td>
                        <a href="/attendance/{{ $req->attendance->id}}">詳細</a>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
        <div class="tab-content">
            <table class="table">
                <tr>
                    <th>状態</th>
                    <th>名前</th>
                    <th>対象日時</th>
                    <th>申請理由</th>
                    <th>申請日時</th>
                    <th>詳細</th>
                </tr>
                @foreach ($correctionRequests->where('status', 'approved') as $req)
                <tr>
                    <td>{{$req->status_label}}</td>
                    <td>{{$req->user->name}}</td>
                    <td>{{$req->attendance->date->format('Y-m-d')}}</td>
                    <td>{{$req->reason}}</td>
                    <td>{{optional($req->approved_at)->format('Y-m-d')}}</td>
                    <td>
                        <a href="/stamp_correction_request/approve/{attendance_correct_request}">詳細</a>
                    </td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</div>

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

@endsection
