<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Attendance;
use App\Models\User;
use App\Models\CorrectionRequest;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\DB;

class AdminController extends Controller
{
    public function indexAdminAttendance(Request $request){
        $date = Carbon::parse($request->input('date', Carbon::today()->toDateString()));

        $attendances = Attendance::with('user')
        ->whereDate('clock_in', $date)->get();

        return view('admin_list',compact(
            'date',
            'attendances'
        ));
    }

    public function showAdminAttendance($id)
    {
        $attendance = \App\Models\Attendance::with(['user', 'workBreaks', 'correctionRequests'])
            ->findOrFail($id);

        $getLatestRequestFor = function($columnName, $workBreakId = null) use ($attendance) {
            $candidates = $attendance->correctionRequests
                ->filter(function($r) use ($columnName, $workBreakId) {
                    if ($r->column_name !== $columnName) return false;

                    if ($workBreakId === null) {
                        return $r->work_break_id === null;
                    }
                    return $r->work_break_id == $workBreakId;
                });

            $approved = $candidates->where('status', 'approved')->sortByDesc('created_at')->first();
            if ($approved) return $approved;
            $pending = $candidates->where('status', 'pending')->sortByDesc('created_at')->first();
            if ($pending) return $pending;
            return null;
        };

        $clockInReq = $getLatestRequestFor('clock_in', null);
        $clockOutReq = $getLatestRequestFor('clock_out', null);

        $display_clock_in = $clockInReq
            ? \Carbon\Carbon::parse($clockInReq->corrected_value)->format('H:i')
            : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('H:i') : '-');

        $display_clock_out = $clockOutReq
            ? \Carbon\Carbon::parse($clockOutReq->corrected_value)->format('H:i')
            : ($attendance->clock_out ? \Carbon\Carbon::parse($attendance->clock_out)->format('H:i') : '-');

        $breakRows = $attendance->workBreaks->sortBy('occurred_at')->values();
        $pairedBreaks = [];
        $i = 0;
        while ($i < $breakRows->count()) {
            $row = $breakRows[$i];
            if ($row->break_type === 'start') {
                $startRow = $row;
                $endRow = null;
                if (isset($breakRows[$i + 1]) && $breakRows[$i + 1]->break_type === 'end') {
                    $endRow = $breakRows[$i + 1];
                    $i += 2;
                } else {
                    $i += 1;
                }

                $startReq = $getLatestRequestFor('start', $startRow->id);
                $endReq   = $endRow ? $getLatestRequestFor('end', $endRow->id) : null;

                $displayStart = $startReq
                    ? \Carbon\Carbon::parse($startReq->corrected_value)->format('H:i')
                    : ($startRow->occurred_at ? \Carbon\Carbon::parse($startRow->occurred_at)->format('H:i') : '');

                $displayEnd = $endReq
                    ? \Carbon\Carbon::parse($endReq->corrected_value)->format('H:i')
                    : ($endRow && $endRow->occurred_at ? \Carbon\Carbon::parse($endRow->occurred_at)->format('H:i') : '');

                $pairedBreaks[] = [
                    'start_id' => $startRow->id,
                    'end_id' => $endRow ? $endRow->id : null,
                    'formatted_start' => $displayStart,
                    'formatted_end' => $displayEnd,
                    'start' => $startRow->occurred_at,
                    'end' => $endRow ? $endRow->occurred_at : null,
                ];
            } else {

                $endRow = $row;
                $i += 1;

                $endReq = $getLatestRequestFor('end', $endRow->id);

                $displayEnd = $endReq
                    ? \Carbon\Carbon::parse($endReq->corrected_value)->format('H:i')
                    : ($endRow->occurred_at ? \Carbon\Carbon::parse($endRow->occurred_at)->format('H:i') : '');

                $pairedBreaks[] = [
                    'start_id' => null,
                    'end_id' => $endRow->id,
                    'formatted_start' => '',
                    'formatted_end' => $displayEnd,
                    'start' => null,
                    'end' => $endRow->occurred_at,
                ];
            }
        }

        $newBreakStarts = $attendance->correctionRequests
            ->filter(fn($r) => $r->column_name === 'start' && $r->work_break_id === null)
            ->sortByDesc('created_at');

        $newBreakEnds = $attendance->correctionRequests
            ->filter(fn($r) => $r->column_name === 'end' && $r->work_break_id === null)
            ->sortByDesc('created_at');

        $maxNew = max($newBreakStarts->count(), $newBreakEnds->count());
        for ($j = 0; $j < $maxNew; $j++) {
            $s = $newBreakStarts->values()->get($j);
            $e = $newBreakEnds->values()->get($j);
            $displayStart = $s ? \Carbon\Carbon::parse($s->corrected_value)->format('H:i') : '';
            $displayEnd   = $e ? \Carbon\Carbon::parse($e->corrected_value)->format('H:i') : '';

            $pairedBreaks[] = [
                'start_id' => null,
                'end_id' => null,
                'formatted_start' => $displayStart,
                'formatted_end' => $displayEnd,
                'start' => null,
                'end' => null,
            ];
        }

        $minRows = 2;
        while (count($pairedBreaks) < $minRows) {
            $pairedBreaks[] = [
                'start_id' => null,
                'end_id' => null,
                'formatted_start' => '',
                'formatted_end' => '',
                'start' => null,
                'end' => null,
            ];
        }

        $latestRequest = $attendance->correctionRequests->sortByDesc('created_at')->first();

        $displayYear = $attendance->date
            ? \Carbon\Carbon::parse($attendance->date)->format('Y年')
            : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('Y年') : '');
        $displayDate = $attendance->date
            ? \Carbon\Carbon::parse($attendance->date)->format('n月j日')
            : ($attendance->clock_in ? \Carbon\Carbon::parse($attendance->clock_in)->format('n月j日') : '');

            $pending = $attendance->correctionRequests
                ->where('status', 'pending')
                ->isNotEmpty();

            return view('admin_detail', compact(
                'attendance',
                'display_clock_in',
                'display_clock_out',
                'pairedBreaks',
                'latestRequest',
                'displayYear',
                'displayDate',
                'pending'
        ));
    }




    public function indexStaff(){
        $users = User::with('attendances')->get();
        return view('admin_staff_list', compact('users'));
    }

    public function indexAdminStampRequests()
    {
        $correctionRequests = CorrectionRequest::with(['attendance', 'user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {

                $displayDate = '-';
                if ($req->attendance) {
                    if ($req->attendance->clock_in) {
                        $displayDate = Carbon::parse($req->attendance->clock_in)->format('Y/m/d');
                    } elseif ($req->attendance->clock_out) {
                        $displayDate = Carbon::parse($req->attendance->clock_out)->format('Y/m/d');
                    }
                }
                $req->display_date = $displayDate;

                $req->display_approved_at = $req->approved_at ? Carbon::parse($req->approved_at)->format('Y/m/d') : '-';

                return $req;
            });

        return view('admin_request', compact('correctionRequests'));
    }


    public function showStampCorrectionRequest($attendance_correct_request)
    {
        $correctionRequest = CorrectionRequest::with([
            'attendance.user',
            'attendance.workBreaks',
            'attendance.correctionRequests'
        ])->findOrFail($attendance_correct_request);

        $attendance = $correctionRequest->attendance;
        if (! $attendance) {
            abort(404, '関連する勤怠が見つかりません。');
        }

        $getLatestRequestFor = function($columnName, $workBreakId = null) use ($attendance) {
            $candidates = $attendance->correctionRequests->filter(function($r) use ($columnName, $workBreakId) {
                if ($r->column_name !== $columnName) return false;

                return ($workBreakId === null) ? ($r->work_break_id === null) : ($r->work_break_id == $workBreakId);
            });

            $approved = $candidates->where('status', 'approved')->sortByDesc('created_at')->first();
            if ($approved) return $approved;

            $pending = $candidates->where('status', 'pending')->sortByDesc('created_at')->first();
            if ($pending) return $pending;

            return null;
        };

        $clockInReq = $getLatestRequestFor('clock_in', null);
        $clockOutReq = $getLatestRequestFor('clock_out', null);

        $display_clock_in = $clockInReq && $clockInReq->corrected_value
            ? Carbon::parse($clockInReq->corrected_value)->format('H:i')
            : ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '-');

        $display_clock_out = $clockOutReq && $clockOutReq->corrected_value
            ? Carbon::parse($clockOutReq->corrected_value)->format('H:i')
            : ($attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '-');

        $breakRows = $attendance->workBreaks->sortBy('occurred_at')->values();
        $pairedBreaks = [];
        $i = 0;
        while ($i < $breakRows->count()) {
            $row = $breakRows[$i];
            if ($row->break_type === 'start') {
                $startRow = $row;
                $endRow = null;
                if (isset($breakRows[$i + 1]) && $breakRows[$i + 1]->break_type === 'end') {
                    $endRow = $breakRows[$i + 1];
                    $i += 2;
                } else {
                    $i += 1;
                }

                $startReq = $getLatestRequestFor('start', $startRow->id);
                $endReq   = $endRow ? $getLatestRequestFor('end', $endRow->id) : null;

                $displayStart = $startReq && $startReq->corrected_value
                    ? Carbon::parse($startReq->corrected_value)->format('H:i')
                    : ($startRow->occurred_at ? Carbon::parse($startRow->occurred_at)->format('H:i') : '');

                $displayEnd = $endReq && $endReq->corrected_value
                    ? Carbon::parse($endReq->corrected_value)->format('H:i')
                    : ($endRow && $endRow->occurred_at ? Carbon::parse($endRow->occurred_at)->format('H:i') : '');

                $pairedBreaks[] = [
                    'start_id' => $startRow->id,
                    'end_id' => $endRow ? $endRow->id : null,
                    'formatted_start' => $displayStart,
                    'formatted_end' => $displayEnd,
                    'start' => $startRow->occurred_at,
                    'end' => $endRow ? $endRow->occurred_at : null,
                ];
            } else {
                $endRow = $row;
                $i += 1;

                $endReq = $getLatestRequestFor('end', $endRow->id);

                $displayEnd = $endReq && $endReq->corrected_value
                    ? Carbon::parse($endReq->corrected_value)->format('H:i')
                    : ($endRow->occurred_at ? Carbon::parse($endRow->occurred_at)->format('H:i') : '');

                $pairedBreaks[] = [
                    'start_id' => null,
                    'end_id' => $endRow->id,
                    'formatted_start' => '',
                    'formatted_end' => $displayEnd,
                    'start' => null,
                    'end' => $endRow->occurred_at,
                ];
            }
        }

        $newStarts = $attendance->correctionRequests
            ->filter(fn($r) => $r->column_name === 'start' && $r->work_break_id === null)
            ->sortByDesc('created_at');

        $newEnds = $attendance->correctionRequests
            ->filter(fn($r) => $r->column_name === 'end' && $r->work_break_id === null)
            ->sortByDesc('created_at');

        $maxNew = max($newStarts->count(), $newEnds->count());
        for ($j = 0; $j < $maxNew; $j++) {
            $s = $newStarts->values()->get($j);
            $e = $newEnds->values()->get($j);
            $displayStart = $s && $s->corrected_value ? Carbon::parse($s->corrected_value)->format('H:i') : '';
            $displayEnd   = $e && $e->corrected_value ? Carbon::parse($e->corrected_value)->format('H:i') : '';

            $pairedBreaks[] = [
                'start_id' => null,
                'end_id' => null,
                'formatted_start' => $displayStart,
                'formatted_end' => $displayEnd,
                'start' => null,
                'end' => null,
            ];
        }

        $minRows = 2;
        while (count($pairedBreaks) < $minRows) {
            $pairedBreaks[] = [
                'start_id' => null,
                'end_id' => null,
                'formatted_start' => '',
                'formatted_end' => '',
                'start' => null,
                'end' => null,
            ];
        }

        $latestRequest = $correctionRequest;

        $displayYear = $attendance->date
            ? Carbon::parse($attendance->date)->format('Y年')
            : ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('Y年') : '');
        $displayDate = $attendance->date
            ? Carbon::parse($attendance->date)->format('n月j日')
            : ($attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('n月j日') : '');

        $pending = $attendance->correctionRequests->where('status', 'pending')->isNotEmpty();

        $hasPendingRequests = \App\Models\CorrectionRequest::where('attendance_id', $attendance->id)
        ->where('status', 'pending')
        ->exists();

        return view('admin_approve', compact(
            'attendance',
            'display_clock_in',
            'display_clock_out',
            'pairedBreaks',
            'latestRequest',
            'displayYear',
            'displayDate',
            'pending',
            'hasPendingRequests'
        ))->with('request', $correctionRequest);
    }


    public function indexAttendanceByStaff(Request $request, int $userId){
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $user = User::findOrFail($userId);
        $dates = Attendance::getMonthDates($year, $month);
        $attendanceMap = Attendance::getMonthAttendanceMap($userId, $year, $month);
        return view('admin_staff_attendance', compact(
            'dates',
            'attendanceMap',
            'year',
            'month',
            'user'
        ));
    }

    public function approveStampCorrectionRequest($attendance_correct_request)
    {
        $attendanceId = $attendance_correct_request;

        DB::transaction(function () use ($attendanceId) {
            $pending = CorrectionRequest::where('attendance_id', $attendanceId)
                        ->where('status', 'pending')
                        ->lockForUpdate()
                        ->get();

            if ($pending->isEmpty()) {
                return;
            }

            $attendance = Attendance::find($attendanceId);

            foreach ($pending as $req) {
                $col = $req->column_name;
                $corr = $req->corrected_value;

                if (empty($corr)) {
                    $req->status = 'approved';
                    $req->approved_at = now();
                    $req->save();
                    continue;
                }

                if (in_array($col, ['clock_in', 'clock_out'])) {
                    if ($attendance) {
                        $attendance->$col = Carbon::parse($corr)->format('Y-m-d H:i:s');
                    }
                } else {
                    if ($req->work_break_id) {
                        $wb = WorkBreak::find($req->work_break_id);
                        if ($wb) {
                            $wb->occurred_at = Carbon::parse($corr)->format('Y-m-d H:i:s');
                            $wb->save();
                        } else {
                            WorkBreak::create([
                                'attendance_id' => $attendanceId,
                                'break_type' => $col,
                                'occurred_at' => Carbon::parse($corr)->format('Y-m-d H:i:s'),
                            ]);
                        }
                    } else {
                        WorkBreak::create([
                            'attendance_id' => $attendanceId,
                            'break_type' => $col,
                            'occurred_at' => Carbon::parse($corr)->format('Y-m-d H:i:s'),
                        ]);
                    }
                }

                $req->status = 'approved';
                $req->approved_at = now();
                $req->save();
            }

            if (isset($attendance) && $attendance->isDirty()) {
                $attendance->save();
            }
        });

        return back();
    }


    public function storeAdminCorrectionRequest(Request $request, $attendanceId)
    {
        $allRequests = $request->input('requests', []);
        $reason = $request->input('reason', null);

        foreach ($allRequests as $data) {
            if (empty($data['corrected_value'])) continue;

            $attendance = isset($data['attendance_id'])
                ? Attendance::find($data['attendance_id'])
                : Attendance::find($attendanceId);
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

            $corr = $this->mergeDateTime($orig, $data['corrected_value'] ?? null);
            if (!$corr) continue;

            CorrectionRequest::create([
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'work_break_id' => $data['work_break_id'] ?? null,
                'column_name' => $data['column_name'],
                'original_value' => $orig,
                'corrected_value' => $corr,
                'reason' => $reason,
                'status' => 'pending',
                'requested_at' => Carbon::now(),
            ]);

            if (in_array($data['column_name'], ['clock_in', 'clock_out'])) {
                $attendance->{$data['column_name']} = $corr;
                $attendance->save();
            } else {
                if (!empty($data['work_break_id'])) {
                    $wb = WorkBreak::find($data['work_break_id']);
                    if ($wb) {
                        $wb->occurred_at = $corr;
                        $wb->save();
                    } else {
                        WorkBreak::create([
                            'attendance_id' => $attendance->id,
                            'break_type' => $data['column_name'],
                            'occurred_at' => $corr,
                        ]);
                    }
                } else {
                    WorkBreak::create([
                        'attendance_id' => $attendance->id,
                        'break_type' => $data['column_name'],
                        'occurred_at' => $corr,
                    ]);
                }
            }
        }

        return redirect()->back();
    }

    private function mergeDateTime(?string $original, ?string $correctedTime): ?string
    {
        if (!$correctedTime) return null;

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $correctedTime)) {
            return $correctedTime;
        }

        $datePart = $original && strlen($original) >= 10 ? substr($original, 0, 10) : Carbon::today()->format('Y-m-d');
        $timePart = trim($correctedTime);

        if ($timePart && preg_match('/^\d{1,2}:\d{2}$/', $timePart)) {
            $timePart .= ':00';
        }

        return $timePart ? ($datePart . ' ' . $timePart) : null;
    }

}