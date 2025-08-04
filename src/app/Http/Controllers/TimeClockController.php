<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\WorkBreak;
use App\Models\CorrectionRequest;

class TimeClockController extends Controller
{
    public function showClockInForm(){
        $attendance = Attendance::with('workBreaks')
        ->where('user_id', Auth::id())
        ->whereDate('clock_in', Carbon::today())
        ->first();

        $currentCondition = $this->determineCurrentCondition($attendance);

        $actions = [];
        switch ($currentCondition) {
            case '勤務外':
                $actions[] = ['type' => 'input', 'label' => '出勤'];
                break;
            case '出勤中':
                $actions[] = ['type' => 'input', 'label' => '退勤'];
                $actions[] = ['type' => 'input', 'label' => '休憩入'];
                break;
            case '休憩中':
                $actions[] = ['type' => 'input', 'label' => '休憩戻'];
                break;
            case '退勤済':
                $actions[] = ['type' => 'p', 'label' => 'お疲れ様でした。'];
                break;
        }

        $weeks = ['日', '月', '火', '水', '木', '金', '土'];
        $now = Carbon::now();
        $date = now()->format('Y年n月j日') . '(' . $weeks[now()->dayOfWeek] . ')';
        $time = now()->format('H:i');

        $correction = CorrectionRequest::where('user_id', Auth::id())->first();

        return view('user_attendance', compact(
            'attendance',
            'currentCondition',
            'date',
            'time',
            'actions',
            'correction',
        ));
    }

    public function storeAttendance(Request $request){
        $request->validate(['action' => ['required', 'in:出勤,退勤,休憩入,休憩戻'] ]);
        $action = $request->input('action');

        $today = Carbon::today()->toDateString();

        $attendance = Attendance::where('user_id', Auth::id())
        ->whereDate('clock_in', $today)
        ->first();

        if (!$attendance && $action === '出勤') {
            $attendance = Attendance::create([
                'user_id' => Auth::id(),
                'clock_in' => now(),
            ]);
        }
        switch ($action){
            case '出勤':
                break;
            case '退勤':
                if ($attendance) {
                    $attendance->clock_out = now();
                    $attendance->save();
                }
                break;
            case '休憩入':
                if ($attendance) {
                    $attendance->workBreaks()->create([
                        'break_type' => 'start',
                        'occurred_at' => now(),
                    ]);
                }
                break;
            case '休憩戻':
                if ($attendance) {
                    $attendance->workBreaks()->create([
                        'break_type' => 'end',
                        'occurred_at' => now(),
                    ]);
                }
                break;
        }
        return back();
    }

    private function determineCurrentCondition($attendance){
        if (is_null($attendance)) {
            return '勤務外';
        }

        if ($attendance->clock_out) {
            return '退勤済';
        }

        $lastBreak = $attendance->workBreaks()
        ->latest('occurred_at')
        ->first();

        if ($lastBreak && $lastBreak->break_type === 'start'){
            return '休憩中';
        }
            return '出勤中';
    }
}
