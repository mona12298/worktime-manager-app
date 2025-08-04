<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Attendance;
use App\Models\WorkBreak;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CorrectionFormRequest extends FormRequest
{
    public function prepareForValidation()
    {
        // 全角数字を半角に変換
        $requests = $this->input('requests', []);
        foreach ($requests as $key => $data) {
            if (isset($data['corrected_value'])) {
                $corrected = $data['corrected_value'];
                $corrected = str_replace(
                    ['０','１','２','３','４','５','６','７','８','９'],
                    ['0','1','2','3','4','5','6','7','8','9'],
                    $corrected
                );
                $requests[$key]['corrected_value'] = $corrected;
            }
        }
        $this->merge(['requests' => $requests]);

        // ログデバッグ（必要なら残す）
        Log::debug('clock_in:', [$this->input('requests.clock_in.corrected_value')]);
        Log::debug('clock_out:', [$this->input('requests.clock_out.corrected_value')]);
        Log::debug('reason:', [$this->input('reason')]);
    }

    public function authorize()
    {
        return true;
    }

    public function rules(){
        $all = $this->input('requests', []);

        // ２．ユーザーが何か入力したものだけに絞る
        $requests = array_filter($all, fn($r) => !empty($r['corrected_value']));

        // ３．reasonは常に必須
        $rules = [
            'reason' => 'required|string|max:1000',
        ];

        // ４．絞り込んだリクエストだけをループしてルールを追加
        foreach ($requests as $key => $req) {
            $column = $req['column_name'] ?? null;

            // 出退勤なら attendance_id
            if (in_array($column, ['clock_in', 'clock_out'])) {
                $rules["requests.$key.attendance_id"] = 'required|exists:attendances,id';
            }

            // 休憩なら work_break_id
            if (in_array($column, ['start', 'end'])) {
                $rules["requests.$key.work_break_id"] = 'required|exists:work_breaks,id';
            }

            // どのカラム名も必須・型チェック
            $rules["requests.$key.column_name"]     = 'required|in:clock_in,clock_out,start,end';
            $rules["requests.$key.corrected_value"] = 'required|date_format:H:i';
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'reason.required' => '備考を記入してください',

            'requests.*.attendance_id.required' => '出勤時間もしくは退勤時間が不適切な値です',
            'requests.*.attendance_id.exists'   => '出勤時間もしくは退勤時間が不適切な値です',

            'requests.*.work_break_id.exists'   => '休憩時間が不適切な値です',

            'requests.*.column_name.required'   => '出勤時間もしくは退勤時間が不適切な値です',
            'requests.*.column_name.in'         => '出勤時間もしくは退勤時間が不適切な値です',

            'requests.*.corrected_value.required'    => '出勤時間もしくは退勤時間が不適切な値です',
            'requests.*.corrected_value.date_format' => '出勤時間もしくは退勤時間が不適切な値です',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requests = $this->input('requests', []);

            $breaksByAttendance = [];
            $clockOutByAttendance = [];

            foreach ($requests as $key => $data) {
                $column = $data['column_name'] ?? null;
                $corrected = $data['corrected_value'] ?? null;
                $attendanceId = $data['attendance_id'] ?? null;

                if (!$attendanceId && isset($data['work_break_id'])) {
                    $workBreak = WorkBreak::find($data['work_break_id']);
                    $attendanceId = $workBreak ? $workBreak->attendance_id : null;
                }

                if ($attendanceId && in_array($column, ['start', 'end'])) {
                    $breaksByAttendance[$attendanceId][$column][] = [
                        'key' => $key,
                        'time' => $corrected,
                    ];
                }

                if ($attendanceId && in_array($column, ['clock_in', 'clock_out'])) {
                    $attendance = Attendance::find($attendanceId);
                    if (!$attendance || !$corrected) {
                        continue;
                    }

                    $baseDate = Carbon::parse($attendance->clock_in ?? $attendance->clock_out)->format('Y-m-d');
                    $correctedFull = Carbon::parse("$baseDate $corrected");

                    if ($column === 'clock_in' && $attendance->clock_out) {
                        $clockOut = Carbon::parse($attendance->clock_out);
                        if ($correctedFull->gt($clockOut)) {
                            $validator->errors()->add("requests.$key.corrected_value", '出勤時間もしくは退勤時間が不適切な値です');
                        }
                    }

                    if ($column === 'clock_out' && $attendance->clock_in) {
                        $clockIn = Carbon::parse($attendance->clock_in);
                        if ($correctedFull->lt($clockIn)) {
                            $validator->errors()->add("requests.$key.corrected_value", '出勤時間もしくは退勤時間が不適切な値です');
                        }
                    }

                    if ($column === 'clock_out') {
                        $clockOutByAttendance[$attendanceId] = [
                            'key' => $key,
                            'time' => $corrected,
                        ];
                    }
                }
            }

            foreach ($breaksByAttendance as $attendanceId => $group) {
                $starts = $group['start'] ?? [];
                $ends = $group['end'] ?? [];

                $pairCount = min(count($starts), count($ends));
                for ($i = 0; $i < $pairCount; $i++) {
                    $startTime = $starts[$i]['time'];
                    $endTime = $ends[$i]['time'];

                    if ($startTime && $endTime && strtotime($startTime) > strtotime($endTime)) {
                        $validator->errors()->add(
                            "requests.{$starts[$i]['key']}.corrected_value",
                            '休憩時間が不適切な値です'
                        );
                    }
                }

                $lastBreakEnd = null;

                if (!empty($ends)) {
                    usort($ends, fn($a, $b) => strtotime($a['time']) <=> strtotime($b['time']));
                    $lastBreakEnd = end($ends)['time'];
                }

                if ($lastBreakEnd) {
                    $clockOut = $clockOutByAttendance[$attendanceId]['time'] ?? null;
                    $clockOutKey = $clockOutByAttendance[$attendanceId]['key'] ?? null;

                    if (!$clockOut) {
                        $attendance = Attendance::find($attendanceId);
                        if ($attendance && $attendance->clock_out) {
                            $clockOut = Carbon::parse($attendance->clock_out)->format('H:i');
                        }
                    }

                    if ($clockOut && strtotime($clockOut) < strtotime($lastBreakEnd)) {
                        $keyPath = $clockOutKey ? "requests.$clockOutKey.corrected_value" : "requests";
                        $validator->errors()->add(
                            $keyPath,
                            '出勤時間もしくは退勤時間が不適切な値です'
                        );
                    }
                }
            }
        });
    }
}
