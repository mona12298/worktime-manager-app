@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/user_detail.css') }}">
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
            <div class="ttl-txt">勤怠一覧</div>
        </div>

        @if ($pending)
        {{-- 修正申請中：フォーム非表示 --}}
        <table class="table">
            <tr>
                <th>名前</th>
                <td class="name">{{ $attendance->user->name }}</td>
            </tr>
            <tr>
                <th>日付</th>
                <td class="year">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') }}</td>
                <td class="date">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('n月j日') }}</td>
            </tr>
            <tr>
                <th>出勤・退勤</th>
                <td class="table-display__in">{{ $display_clock_in }}</td>
                <td class="range-separator display-mode">〜</td>
                <td class="table-display__out">{{ $display_clock_out }}</td>
            </tr>

            {{-- 休憩行：1行目は必ず表示、2行目以降は start/end 両方あれば表示 --}}
            @foreach ($pairedBreaks as $index => $break)
                @php
                    $hasBothBreaks = (!empty($break['display_start']) && !empty($break['display_end']));
                @endphp

                @if ($index === 0 || $hasBothBreaks)
                <tr>
                    <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td class="table-display__in">{{ $break['display_start'] }}</td>
                    <td class="range-separator display-mode">〜</td>
                    <td class="table-display__out">{{ $break['display_end'] }}</td>
                </tr>
                @endif
            @endforeach

            <tr>
                <th>備考</th>
                <td class="table-textarea" colspan="3">{{ $latestRequest->reason ?? '-' }}</td>
            </tr>
        </table>

        <div class="correction">
            <p class="pending-msg">*承認待ちのため修正はできません。</p>
        </div>

        @else
        {{-- 修正可能：フォーム表示 --}}
        <form action="{{ url('/attendance/' . $attendance->id) }}" method="post">
            @csrf
            <table class="table">
                <tr>
                    <th>名前</th>
                    <td class="name">{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td class="year">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') }}</td>
                    <td class="date">{{ \Carbon\Carbon::parse($attendance->clock_in)->format('n月j日') }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <input type="hidden" name="requests[clock_in][column_name]" value="clock_in">
                        <input type="hidden" name="requests[clock_in][attendance_id]" value="{{ $attendance->id }}">
                        <input type="hidden" name="requests[clock_in][original_value]" value="{{ optional($attendance->clock_in)->format('Y-m-d H:i:s') }}">
                        <input type="text" class="table-input__in" name="requests[clock_in][corrected_value]" value="{{ old('requests.clock_in.corrected_value', optional($attendance->clock_in)->format('H:i')) }}">
                    </td>
                    <td class="range-separator input-mode">〜</td>
                    <td>
                        <input type="hidden" name="requests[clock_out][column_name]" value="clock_out">
                        <input type="hidden" name="requests[clock_out][attendance_id]" value="{{ $attendance->id }}">
                        <input type="hidden" name="requests[clock_out][original_value]" value="{{ optional($attendance->clock_out)->format('Y-m-d H:i:s') }}">
                        <input type="text" class="table-input__out" name="requests[clock_out][corrected_value]" value="{{ old('requests.clock_out.corrected_value', optional($attendance->clock_out)->format('H:i')) }}">
                    </td>
                </tr>

                {{-- フォーム側の休憩行：pairedBreaks の数だけ表示（pairedBreaks は最低1要素） --}}
                @foreach ($pairedBreaks as $index => $break)
                <tr>
                    <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td>
                        <input type="hidden" name="requests[break_{{ $index }}_in][column_name]" value="start">
                        <input type="hidden" name="requests[break_{{ $index }}_in][work_break_id]" value="{{ $break['start_id'] }}">
                        <input type="hidden" name="requests[break_{{ $index }}_in][original_value]" value="{{ $break['start'] }}">
                        <input type="text" class="table-input__in"
                               name="requests[break_{{ $index }}_in][corrected_value]"
                               value="{{ old("requests.break_{$index}_in.corrected_value", $break['formatted_start']) }}">
                    </td>
                    <td class="range-separator {{ $pending ? 'display-mode break' : 'input-mode' }}">〜</td>
                    <td>
                        <input type="hidden" name="requests[break_{{ $index }}_out][column_name]" value="end">
                        <input type="hidden" name="requests[break_{{ $index }}_out][work_break_id]" value="{{ $break['end_id'] }}">
                        <input type="hidden" name="requests[break_{{ $index }}_out][original_value]" value="{{ $break['end'] }}">
                        <input type="text" class="table-input__out"
                               name="requests[break_{{ $index }}_out][corrected_value]"
                               value="{{ old("requests.break_{$index}_out.corrected_value", $break['formatted_end']) }}">
                    </td>
                </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td class="table-display__text" colspan="3">
                        <textarea name="reason">{{ old('reason', optional($latestRequest)->reason) }}</textarea>
                    </td>
                </tr>
            </table>

            <div class="form__error">
                @foreach (collect($errors->all())->unique() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>

            <div class="correction">
                <input type="submit" value="修正">
            </div>
        </form>
        @endif
    </div>
</div>
@endsection
