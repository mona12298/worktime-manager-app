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
            <div class="ttl-txt">勤怠詳細</div>
        </div>

        @if (!empty($pending))
            {{-- 承認待ち: 表示モード --}}
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
                    <td class="range-separator display-mode">〜</td>
                    <td class="table-display__out">{{ $display_clock_out }}</td>
                </tr>

                @foreach ($pairedBreaks as $index => $break)
                <tr>
                    <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                    <td class="table-display__in">{{ $break['formatted_start'] }}</td>
                    <td class="range-separator display-mode break">〜</td>
                    <td class="table-display__out">{{ $break['formatted_end'] }}</td>
                </tr>
                @endforeach

                <tr>
                    <th>備考</th>
                    <td class="table-textarea" colspan="3">{{ $latestRequest->reason ?? '-' }}</td>
                </tr>
            </table>

        @else
            {{-- 編集可能: フォーム表示 --}}
            <form action="{{ url('/attendance/' . $attendance->id) }}" method="post">
                @csrf
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
                        <td>
                            <input type="hidden" name="requests[clock_in][column_name]" value="clock_in">
                            <input type="hidden" name="requests[clock_in][attendance_id]" value="{{ $attendance->id }}">
                            <input type="hidden" name="requests[clock_in][original_value]" value="{{ optional($attendance->clock_in)->format('Y-m-d H:i:s') }}">
                            <input type="text" id="clock_in" class="table-input__in" name="requests[clock_in][corrected_value]" value="{{ old('requests.clock_in.corrected_value', $display_clock_in === '-' ? (optional($attendance->clock_in)->format('H:i') ?? '') : $display_clock_in) }}">
                        </td>
                        <td class="range-separator input-mode">〜</td>
                        <td>
                            <input type="hidden" name="requests[clock_out][column_name]" value="clock_out">
                            <input type="hidden" name="requests[clock_out][attendance_id]" value="{{ $attendance->id }}">
                            <input type="hidden" name="requests[clock_out][original_value]" value="{{ optional($attendance->clock_out)->format('Y-m-d H:i:s') }}">
                            <input type="text" id="clock_out" class="table-input__out" name="requests[clock_out][corrected_value]" value="{{ old('requests.clock_out.corrected_value', $display_clock_out === '-' ? (optional($attendance->clock_out)->format('H:i') ?? '') : $display_clock_out) }}">
                        </td>
                    </tr>

                    @foreach ($pairedBreaks as $index => $break)
                    <tr>
                        <th>{{ $index === 0 ? '休憩' : '休憩' . ($index + 1) }}</th>
                        <td>
                            <input type="hidden" name="requests[break_{{ $index }}_in][column_name]" value="start">
                            <input type="hidden" name="requests[break_{{ $index }}_in][work_break_id]" value="{{ $break['start_id'] ?? '' }}">
                            <input type="hidden" name="requests[break_{{ $index }}_in][original_value]" value="{{ $break['start'] ?? '' }}">
                            <input type="text" id="break_{{ $index }}_in" class="table-input__in"
                                   name="requests[break_{{ $index }}_in][corrected_value]"
                                   value="{{ old("requests.break_{$index}_in.corrected_value", $break['formatted_start'] ?? '') }}">
                        </td>
                        <td class="range-separator input-mode">〜</td>
                        <td>
                            <input type="hidden" name="requests[break_{{ $index }}_out][column_name]" value="end">
                            <input type="hidden" name="requests[break_{{ $index }}_out][work_break_id]" value="{{ $break['end_id'] ?? '' }}">
                            <input type="hidden" name="requests[break_{{ $index }}_out][original_value]" value="{{ $break['end'] ?? '' }}">
                            <input type="text" id="break_{{ $index }}_out" class="table-input__out"
                                   name="requests[break_{{ $index }}_out][corrected_value]"
                                   value="{{ old("requests.break_{$index}_out.corrected_value", $break['formatted_end'] ?? '') }}">
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

@section('js')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const displayIn = '{{ $display_clock_in ?? '' }}';
        const displayOut = '{{ $display_clock_out ?? '' }}';
        const paired = @json($pairedBreaks);

        const elClockIn = document.getElementById('clock_in');
        const elClockOut = document.getElementById('clock_out');

        if (elClockIn) elClockIn.value = elClockIn.value || displayIn || '';
        if (elClockOut) elClockOut.value = elClockOut.value || displayOut || '';

        paired.forEach((b, idx) => {
            const elIn = document.getElementById(`break_${idx}_in`);
            const elOut = document.getElementById(`break_${idx}_out`);
            if (elIn) elIn.value = elIn.value || (b.formatted_start ?? '') || '';
            if (elOut) elOut.value = elOut.value || (b.formatted_end ?? '') || '';
        });
    });
</script>
@endsection
