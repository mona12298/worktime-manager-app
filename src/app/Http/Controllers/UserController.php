<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\CorrectionRequest;
use App\Models\WorkBreak;
use App\Http\Requests\CorrectionFormRequest;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function indexUserAttendance(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $userId = Auth::id();

        $dates = Attendance::getMonthDates($year, $month);
        $attendanceMap = Attendance::getMonthAttendanceMap($userId, $year, $month);

        return view('user_list', compact(
            'dates',
            'attendanceMap',
            'year',
            'month',
            'userId',
        ));
    }

    public function showUserAttendance($id)
    {
        $attendance = Attendance::with(['user', 'workBreaks'])->findOrFail($id);

        $latestRequest = \App\Models\CorrectionRequest::where('attendance_id', $id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        $breaks = $attendance->workBreaks->sortBy('occurred_at')->values();
        $pairedBreaks = [];

        // 最初の2ペアはH:iフォーマットで保存
        for ($i = 0; $i < 2; $i++) {
            $start = $breaks->get($i * 2);
            $end = $breaks->get($i * 2 + 1);

            $startVal = $start ? $start->occurred_at : null;
            $endVal = $end ? $end->occurred_at : null;

            $pairedBreaks[] = [
                // 元の日時（安全にnullも含む）
                'start' => $startVal,
                'end' => $endVal,
                'start_id' => $start ? $start->id : null,
                'end_id' => $end ? $end->id : null,
                // 追加：フォーマット済み時間（ビュー用）
                'formatted_start' => $startVal ? \Carbon\Carbon::parse($startVal)->format('H:i') : '',
                'formatted_end' => $endVal ? \Carbon\Carbon::parse($endVal)->format('H:i') : '',
            ];
        }

        // 3ペア目以降も同様にH:iフォーマットを付与
        for ($i = 2; $i * 2 < $breaks->count(); $i++) {
            $start = $breaks->get($i * 2);
            $end = $breaks->get($i * 2 + 1);

            $startVal = $start ? $start->occurred_at : null;
            $endVal = $end ? $end->occurred_at : null;

            $pairedBreaks[] = [
                'start' => $startVal,
                'end' => $endVal,
                'start_id' => $start ? $start->id : null,
                'end_id' => $end ? $end->id : null,
                'formatted_start' => $startVal ? \Carbon\Carbon::parse($startVal)->format('H:i') : '',
                'formatted_end' => $endVal ? \Carbon\Carbon::parse($endVal)->format('H:i') : '',
            ];
        }

        return view('user_detail', compact('attendance', 'pairedBreaks', 'latestRequest'));
    }


    public function storeUserCorrectionRequest(CorrectionFormRequest $request, $id){
        $allRequests = $request->input('requests', []);
        $reason      = $request->input('reason');

        foreach ($allRequests as $key => $data) {
            if (empty($data['corrected_value'])) {
                continue; // 空の修正はスキップ
            }

            $attendance = isset($data['attendance_id']) ? Attendance::find($data['attendance_id']) : null;

            if (empty($data['attendance_id']) && !empty($data['work_break_id'])) {
                $workBreak             = WorkBreak::find($data['work_break_id']);
                $data['attendance_id'] = $workBreak ? $workBreak->attendance_id : null;
                $attendance            = $workBreak
                                            ? Attendance::find($workBreak->attendance_id)
                                            : null;
            }

            if (!$attendance) {
                continue;
            }

            // original_value（DBから）
            if (in_array($data['column_name'], ['start', 'end'])) {
                $workBreak = WorkBreak::find($data['work_break_id']);
                $orig = $workBreak && $workBreak->{$data['column_name']}
                    ? $workBreak->{$data['column_name']}->format('Y-m-d H:i:s')
                    : null;
            } else {
                $column = $data['column_name'];
                $orig = $attendance->$column
                    ? $attendance->$column->format('Y-m-d H:i:s')
                    : null;
            }

            // corrected_value（フォームから）
            if (in_array($data['column_name'], ['start', 'end'])) {
                $corr = $attendance->clock_in->format('Y-m-d')
                    . ' '
                    . trim($data['corrected_value'])
                    . ':00';
            } else {
                $corr = $this->mergeDateTime(
                    $orig,
                    $data['corrected_value']
                );
            }

            CorrectionRequest::create([
                'user_id'         => auth()->id(),
                'attendance_id'   => $data['attendance_id'],
                'work_break_id'   => $data['work_break_id'] ?? null,
                'column_name'     => $data['column_name'],
                'original_value'  => $orig,
                'corrected_value' => $corr,
                'reason'          => $reason,
                'status'          => 'pending',
            ]);
        }

        return redirect()->back();
    }


    private function mergeDateTime(?string $original, ?string $correctedTime): ?string
    {
        if (!$original || strlen($original) < 10) {
            return null;
        }

        // フル形式（すでに Y-m-d H:i:s）の場合はそのまま
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $correctedTime)) {
            return $correctedTime;
        }

        $datePart = substr($original, 0, 10);
        $timePart = $correctedTime ? trim($correctedTime) : null;

        if ($timePart && preg_match('/^\d{1,2}:\d{2}$/', $timePart)) {
            $timePart .= ':00';
        }

        return $timePart ? $datePart . ' ' . $timePart : null;
    }


    public function indexUserStampRequests(){

        $user = Auth::user();

        $pendingRequests = $user->correctionRequests()
            ->with(['attendance', 'user'])
            ->where('status', 'pending')
            ->get()
            ->groupBy('attendance_id');

        $approvedRequests = $user->correctionRequests()
            ->with(['attendance', 'user'])
            ->where('status', 'approved')
            ->get()
            ->groupBy('attendance_id');

        $latestRequests = $user->correctionRequests()
            ->with(['attendance', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('attendance_id')
            ->map(function ($requests) {
                return $requests->first();
            });

        return view('user_request', compact(
            'pendingRequests', 'approvedRequests','latestRequests'
        ));
    }

}
