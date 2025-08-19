<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
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
            'userId'
        ));
    }

    public function showUserAttendance($id)
    {
        $attendance = Attendance::with(['user', 'workBreaks'])->findOrFail($id);

        $pendingRequests = CorrectionRequest::where('attendance_id', $id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $pending = $pendingRequests->isNotEmpty();

        $pendingClockIn = $pendingRequests->firstWhere('column_name', 'clock_in');
        $pendingClockOut = $pendingRequests->firstWhere('column_name', 'clock_out');

        $display_clock_in = $pendingClockIn
            ? Carbon::parse($pendingClockIn->corrected_value)->format('H:i')
            : (optional($attendance->clock_in)->format('H:i') ?? '');

        $display_clock_out = $pendingClockOut
            ? Carbon::parse($pendingClockOut->corrected_value)->format('H:i')
            : (optional($attendance->clock_out)->format('H:i') ?? '');

        $breaks = $attendance->workBreaks->sortBy('occurred_at')->values();
        $pairedBreaks = [];

        $pendingByWorkBreakStart = [];
        $pendingByWorkBreakEnd = [];
        $pendingNullWorkBreakStart = null;
        $pendingNullWorkBreakEnd = null;

        foreach ($pendingRequests as $req) {
            if (!in_array($req->column_name, ['start', 'end'])) {
                continue;
            }

            if ($req->work_break_id) {
                if ($req->column_name === 'start') {
                    $pendingByWorkBreakStart[$req->work_break_id] = $req;
                } else {
                    $pendingByWorkBreakEnd[$req->work_break_id] = $req;
                }
            } else {
                if ($req->column_name === 'start' && !$pendingNullWorkBreakStart) {
                    $pendingNullWorkBreakStart = $req;
                }
                if ($req->column_name === 'end' && !$pendingNullWorkBreakEnd) {
                    $pendingNullWorkBreakEnd = $req;
                }
            }
        }

        for ($i = 0; $i * 2 < $breaks->count(); $i++) {
            $start = $breaks->get($i * 2);
            $end = $breaks->get($i * 2 + 1);

            $startVal = $start ? $start->occurred_at : null;
            $endVal = $end ? $end->occurred_at : null;

            $display_start = '';
            $display_end = '';

            if ($start && isset($pendingByWorkBreakStart[$start->id])) {
                $display_start = Carbon::parse($pendingByWorkBreakStart[$start->id]->corrected_value)->format('H:i');
            } else {
                $display_start = $startVal ? Carbon::parse($startVal)->format('H:i') : '';
            }

            if ($end && isset($pendingByWorkBreakEnd[$end->id])) {
                $display_end = Carbon::parse($pendingByWorkBreakEnd[$end->id]->corrected_value)->format('H:i');
            } else {
                $display_end = $endVal ? Carbon::parse($endVal)->format('H:i') : '';
            }

            $pairedBreaks[] = [
                'start' => $startVal,
                'end' => $endVal,
                'start_id' => $start ? $start->id : null,
                'end_id' => $end ? $end->id : null,
                'formatted_start' => $startVal ? Carbon::parse($startVal)->format('H:i') : '',
                'formatted_end' => $endVal ? Carbon::parse($endVal)->format('H:i') : '',
                'display_start' => $display_start,
                'display_end' => $display_end,
            ];
        }

        if (count($pairedBreaks) === 0) {
            $pairedBreaks[] = [
                'start' => null,
                'end' => null,
                'start_id' => null,
                'end_id' => null,
                'formatted_start' => '',
                'formatted_end' => '',
                'display_start' => '',
                'display_end' => '',
            ];
        }

        if ($pendingNullWorkBreakStart) {
            $pairedBreaks[0]['display_start'] = Carbon::parse($pendingNullWorkBreakStart->corrected_value)->format('H:i');
            if (empty($pairedBreaks[0]['formatted_start'])) {
                $pairedBreaks[0]['formatted_start'] = Carbon::parse($pendingNullWorkBreakStart->corrected_value)->format('H:i');
            }
        }
        if ($pendingNullWorkBreakEnd) {
            $pairedBreaks[0]['display_end'] = Carbon::parse($pendingNullWorkBreakEnd->corrected_value)->format('H:i');
            if (empty($pairedBreaks[0]['formatted_end'])) {
                $pairedBreaks[0]['formatted_end'] = Carbon::parse($pendingNullWorkBreakEnd->corrected_value)->format('H:i');
            }
        }

        $latestRequest = $pendingRequests->first();

        return view('user_detail', compact(
            'attendance',
            'pairedBreaks',
            'latestRequest',
            'pending',
            'display_clock_in',
            'display_clock_out'
        ));
    }

    public function storeUserCorrectionRequest(CorrectionFormRequest $request, $id)
    {
        $allRequests = $request->input('requests', []);
        $reason = $request->input('reason');

        foreach ($allRequests as $key => $data) {
            if (empty($data['corrected_value'])) continue;

            $attendance = isset($data['attendance_id']) ? Attendance::find($data['attendance_id']) : null;
            if (empty($data['attendance_id']) && !empty($data['work_break_id'])) {
                $workBreak = WorkBreak::find($data['work_break_id']);
                $attendance = $workBreak ? Attendance::find($workBreak->attendance_id) : null;
                $data['attendance_id'] = $workBreak ? $workBreak->attendance_id : null;
            }
            if (!$attendance) continue;

            if (in_array($data['column_name'], ['start', 'end'])) {
                $workBreak = !empty($data['work_break_id']) ? WorkBreak::find($data['work_break_id']) : null;
                $orig = $workBreak && $workBreak->occurred_at
                    ? Carbon::parse($workBreak->occurred_at)->format('Y-m-d H:i:s')
                    : null;
            } else {
                $column = $data['column_name'];
                $orig = $attendance->$column
                    ? Carbon::parse($attendance->$column)->format('Y-m-d H:i:s')
                    : null;
            }

            if (in_array($data['column_name'], ['start', 'end'])) {
                $corr = Carbon::parse($attendance->clock_in)
                            ->setTimeFromTimeString($data['corrected_value'])
                            ->format('Y-m-d H:i:s');
            } else {
                $corr = $this->mergeDateTime($orig, $data['corrected_value']);
                if ($data['column_name'] === 'clock_in' && $attendance->clock_out && $corr > Carbon::parse($attendance->clock_out)->format('Y-m-d H:i:s')) {
                    $corr = Carbon::parse($corr)->subDay()->format('Y-m-d H:i:s');
                }
                if ($data['column_name'] === 'clock_out' && $attendance->clock_in && $corr < Carbon::parse($attendance->clock_in)->format('Y-m-d H:i:s')) {
                    $corr = Carbon::parse($corr)->addDay()->format('Y-m-d H:i:s');
                }
            }

            $existingPending = CorrectionRequest::where('attendance_id', $data['attendance_id'])
                ->where('column_name', $data['column_name'])
                ->where('work_break_id', $data['work_break_id'] ?? null)
                ->where('status', 'pending')
                ->first();

            if ($existingPending) {
                if ($existingPending->corrected_value === $corr && $existingPending->reason === $reason) {
                    continue;
                }
                $existingPending->update([
                    'corrected_value' => $corr,
                    'reason' => $reason,
                    'user_id' => auth()->id(),
                ]);
                continue;
            }

            CorrectionRequest::create([
                'user_id' => auth()->id(),
                'attendance_id' => $data['attendance_id'],
                'work_break_id' => $data['work_break_id'] ?? null,
                'column_name' => $data['column_name'],
                'original_value' => $orig,
                'corrected_value' => $corr,
                'reason' => $reason,
                'status' => 'pending',
                'requested_at' => Carbon::now(),
            ]);
        }

        return redirect()->back();
    }

    private function mergeDateTime(?string $original, ?string $correctedTime): ?string
    {
        if (!$original || strlen($original) < 10) {
            return null;
        }

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

    public function indexUserStampRequests()
    {
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
                $req = $requests->first();
                $req->display_approved_at = $req->approved_at ? Carbon::parse($req->approved_at)->format('Y/m/d') : '-';
                return $req;
            });

        return view('user_request', compact(
            'pendingRequests', 'approvedRequests', 'latestRequests'
        ));
    }
}
