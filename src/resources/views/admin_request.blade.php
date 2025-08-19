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

        {{-- ================= 承認待ち ================= --}}
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

                @php
                    // 同一申請を1グループにまとめるためのキーを作成
                    $pendingGroups = $correctionRequests
                        ->where('status', 'pending')
                        ->groupBy(function($r) {
                            // アンカーは attendance_id。無ければ work_break_id を使う（親勤怠に紐づく想定）
                            $anchor = $r->attendance_id ?: ('wb-'.$r->work_break_id);
                            $ts = optional($r->requested_at)->format('Y/m/d H:i:s'); // 申請時刻
                            // user / anchor / status / requested_at / reason で同一申請とみなす
                            return $r->user_id.'|'.$anchor.'|'.$r->status.'|'.$ts.'|'.md5((string)$r->reason);
                        });
                @endphp

                @foreach ($pendingGroups as $group)
                    @php $first = $group->first(); @endphp
                    <tr>
                        <td>{{ $first->status_label }}</td>
                        <td>{{ $first->user->name }}</td>
                        <td>{{ $first->display_date }}</td>
                        <td>{{ $first->reason }}</td>
                        <td>{{ optional($first->requested_at)->format('Y/m/d') ?? optional($first->created_at)->format('Y/m/d') }}</td>
                        <td>
                            {{-- 代表ID（first）へ飛ばす。グループ内の詳細は遷移先でまとめて表示してもOK --}}
                            <a href="/stamp_correction_request/approve/{{ $first->id }}">詳細</a>
                        </td>
                    </tr>
                @endforeach
            </table>
        </div>

        {{-- ================= 承認済み ================= --}}
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

                @php
                    $approvedGroups = $correctionRequests
                        ->where('status', 'approved')
                        ->groupBy(function($r) {
                            $anchor = $r->attendance_id ?: ('wb-'.$r->work_break_id);
                            $ts = optional($r->requested_at)->format('Y/m/d H:i:s');
                            return $r->user_id.'|'.$anchor.'|'.$r->status.'|'.$ts.'|'.md5((string)$r->reason);
                        });
                @endphp

                @foreach ($approvedGroups as $group)
                    @php $first = $group->first(); @endphp
                    <tr>
                        <td>{{ $first->status_label }}</td>
                        <td>{{ $first->user->name }}</td>
                        <td>{{ $first->display_date }}</td>
                        <td>{{ $first->reason }}</td>
                        <td>{{ optional($first->approved_at)->format('Y/m/d') }}</td>
                        <td>
                            <a href="/stamp_correction_request/approve/{{ $first->id }}">詳細</a>
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
