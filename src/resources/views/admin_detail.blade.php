@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{asset('css/admin_detail.css')}}">
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
            <div class="ttl-txt">勤怠一覧</div>
        </div>
        <form action="/attendance/{{ $attendance->id }}" method="post">
            @csrf
            <table class="table">
                <tr>
                    <th>名前</th>
                    <td class="name">{{ $attendance->user->name }}</td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td class="year">{{ \Carbon\Carbon::parse($attendance->date)->format('Y年') }}</td>
                    <td class="date">{{ \Carbon\Carbon::parse($attendance->date)->format('n月j日') }}</td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <input type="hidden" name="requests[clock_in][column_name]" value="clock_in">
                        <input type="hidden" name="requests[clock_in][original_value]" value="{{ $attendance->clock_in }}">
                        <input type="hidden" name="requests[clock_in][attendance_id]" value="{{ $attendance->id }}">
                        <input type="text" id="clock_in" name="requests[clock_in][corrected_value]" class="table-input__in" value="{{ old('requests.clock_in.corrected_value', optional($attendance->clock_in)->format('H:i')) }}">
                    </td>
                    <td class="range-separator">〜</td>
                    <td>
                        <input type="hidden" name="requests[clock_out][column_name]" value="clock_out">
                        <input type="hidden" name="requests[clock_out][original_value]" value="{{ $attendance->clock_out }}">
                        <input type="hidden" name="requests[clock_out][attendance_id]" value="{{ $attendance->id }}">
                        <input type="text" id="clock_out" name="requests[clock_out][corrected_value]" class="table-input__out" value="{{ old('requests.clock_out.corrected_value', optional($attendance->clock_out)->format('H:i')) }}">
                    </td>
                </tr>

                @php
                    $breaks = $attendance->workBreaks;
                @endphp

                @for ($i = 0; $i < 2; $i++)
                <tr>
                    <th>休憩{{ $i === 0 ? '' : '２' }}</th>
                    <td>
                        <input type="hidden" name="requests[break_{{ $i }}_in][column_name]" value="start">
                        <input type="hidden" name="requests[break_{{ $i }}_in][original_value]" value="{{ optional($breaks[$i] ?? null)->break_in }}">
                        <input type="hidden" name="requests[break_{{ $i }}_in][work_break_id]" value="{{ optional($breaks[$i] ?? null)->id }}">
                        <input type="text" id="break_{{ $i }}_in" name="requests[break_{{ $i }}_in][corrected_value]" class="table-input__in" value="{{ old('requests.break_'.$i.'_in.corrected_value', optional($breaks[$i] ?? null)->break_in ? \Carbon\Carbon::parse($breaks[$i]->break_in)->format('H:i') : '') }}">
                    </td>
                    <td class="range-separator">〜</td>
                    <td>
                        <input type="hidden" name="requests[break_{{ $i }}_out][column_name]" value="end">
                        <input type="hidden" name="requests[break_{{ $i }}_out][original_value]" value="{{ optional($breaks[$i] ?? null)->break_out }}">
                        <input type="hidden" name="requests[break_{{ $i }}_out][work_break_id]" value="{{ optional($breaks[$i] ?? null)->id }}">
                        <input type="text" id="break_{{ $i }}_out" name="requests[break_{{ $i }}_out][corrected_value]" class="table-input__out" value="{{ old('requests.break_'.$i.'_out.corrected_value', optional($breaks[$i] ?? null)->break_out ? \Carbon\Carbon::parse($breaks[$i]->break_out)->format('H:i') : '') }}">
                    </td>
                </tr>
                @endfor

                @foreach ($breaks->slice(2) as $index => $break)
                <tr>
                    <th>休憩{{ $index + 3 }}</th>
                    <td>
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_in][column_name]" value="start">
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_in][original_value]" value="{{ $break->break_in }}">
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_in][work_break_id]" value="{{ $break->id }}">
                        <input type="text" id="break_{{ $index + 2 }}_in" name="requests[break_{{ $index + 2 }}_in][corrected_value]" class="table-input__in" value="{{ old('requests.break_'.($index + 2).'_in.corrected_value', optional($break->break_in)->format('H:i')) }}">
                    </td>
                    <td class="range-separator">〜</td>
                    <td>
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_out][column_name]" value="end">
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_out][original_value]" value="{{ $break->break_out }}">
                        <input type="hidden" name="requests[break_{{ $index + 2 }}_out][work_break_id]" value="{{ $break->id }}">
                        <input type="text" id="break_{{ $index + 2 }}_out" name="requests[break_{{ $index + 2 }}_out][corrected_value]" class="table-input__out" value="{{ old('requests.break_'.($index + 2).'_out.corrected_value', optional($break->break_out)->format('H:i')) }}">
                    </td>
                </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td class="table-textarea" colspan="3">
                        <textarea name="reason">{{ old('reason') }}</textarea>
                    </td>
                </tr>
            </table>

            <div class="form__error">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>

            <div class="form-btn">
                <input type="submit" value="修正">
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const times = {
            clock_in: '{{ optional($attendance->clock_in)->format("H:i") ?? '' }}',
            clock_out: '{{ optional($attendance->clock_out)->format("H:i") ?? '' }}',

            @foreach ($attendance->workBreaks as $index => $break)
            break_{{ $index }}_in: '{{ optional($break->break_in)->format("H:i") ?? '' }}',
            break_{{ $index }}_out: '{{ optional($break->break_out)->format("H:i") ?? '' }}',
            @endforeach
        };

        Object.entries(times).forEach(([id, val]) => {
            const el = document.getElementById(id);
            if (el) el.value = val || '';
        });
    });
</script>
@endsection
