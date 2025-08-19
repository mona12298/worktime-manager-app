<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\WorkBreak;

class CorrectionFormRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $all = $this->input('requests', []);
        $requests = array_filter($all, fn($r) => !empty($r['corrected_value']));

        $rules = [
            'reason' => 'required|string|max:1000',
        ];

        foreach ($requests as $key => $req) {
            $column = $req['column_name'] ?? null;

            if (in_array($column, ['clock_in', 'clock_out'])) {
                $rules["requests.$key.attendance_id"] = 'required|exists:attendances,id';
            }

            if (in_array($column, ['start', 'end'])) {
                $rules["requests.$key.work_break_id"] = 'nullable|exists:work_breaks,id';
            }

            $rules["requests.$key.column_name"] = 'required|in:clock_in,clock_out,start,end';

            if (!empty($req['corrected_value'])) {
                $rules["requests.$key.corrected_value"] = 'date_format:H:i';
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $requests = $this->input('requests', []);

            $clockInOutByAttendance = [];
            $startEndByBreak = [];

            foreach ($requests as $key => $data) {
                $column = $data['column_name'] ?? null;
                $corrected = $data['corrected_value'] ?? null;
                $attendanceId = $data['attendance_id'] ?? null;

                if (!$attendanceId && isset($data['work_break_id'])) {
                    $workBreak = WorkBreak::find($data['work_break_id']);
                    $attendanceId = $workBreak ? $workBreak->attendance_id : null;
                }

                if (empty($corrected)) continue;

                // 出勤退勤記録
                if (in_array($column, ['clock_in', 'clock_out'])) {
                    $clockInOutByAttendance[$attendanceId][$column] = [
                        'key' => $key,
                        'time' => $corrected,
                    ];
                }

                // 休憩 start/end ペア記録
                if (in_array($column, ['start', 'end'])) {
                    $startEndByBreak[$data['work_break_id']][$column] = [
                        'key' => $key,
                        'value' => $corrected,
                    ];
                }
            }

            // 出勤・退勤の前後チェック
            foreach ($clockInOutByAttendance as $attendanceId => $cols) {
                if (isset($cols['clock_in']) && isset($cols['clock_out'])) {
                    $in = strtotime($cols['clock_in']['time']);
                    $out = strtotime($cols['clock_out']['time']);
                    if ($in >= $out) {
                        $validator->errors()->add("requests.{$cols['clock_in']['key']}.corrected_value", '出勤時間もしくは退勤時間が不適切な値です。');
                    }
                }
            }

            // start/end ペア必須チェック
            foreach ($startEndByBreak as $breakId => $cols) {
                if (isset($cols['start']) && !isset($cols['end'])) {
                    $validator->errors()->add("requests.{$cols['start']['key']}.corrected_value", '休憩時間が不適切な値です。');
                }
                if (isset($cols['end']) && !isset($cols['start'])) {
                    $validator->errors()->add("requests.{$cols['end']['key']}.corrected_value", '休憩時間が不適切な値です。');
                }
            }

            // 休憩時間の順序チェック（同一 break_id 内で start と end を比較）
            foreach ($startEndByBreak as $breakId => $cols) {
                if (isset($cols['start'], $cols['end'])) {
                    $startTime = strtotime($cols['start']['value']);
                    $endTime = strtotime($cols['end']['value']);
                    if ($startTime >= $endTime) {
                        $validator->errors()->add("requests.{$cols['start']['key']}.corrected_value", '休憩時間が不適切な値です。');
                    }
                }
            }
        });
    }

    public function messages(){
        return [
            'reason.required' => '備考を記入してください。',
        ];
    }
}
