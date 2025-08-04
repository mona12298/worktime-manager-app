<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response;


class AdminAttendanceExportController extends Controller
{
    public function exportCsv(Request $request, User $user){
        $year = $request->input('year');
        $month = $request->input('month');

        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $attendances = Attendance::where('user_id', $user->id)->whereBetween('clock_in', [$startDate, $endDate])->orderBy('clock_in')->get();

        $csvHeader = ['日付', '出勤', '退勤', '休憩時間', '勤務時間'];

        $csvData = [];
        foreach ($attendances as $attendance) {
            $csvData[] = [
                Carbon::parse($attendance->clock_in)->format('Y/m/d'),
                optional($attendance->clock_in)->format('H:i'),
                optional($attendance->clock_out)->format('H:i'),
                $attendance->breakHours(),
                $attendance->workedHours(),
            ];
        }

        $filename = $user->name . "_{$year}_" . str_pad($month, 2, '0', STR_PAD_LEFT) . "_attendance.csv";


        $callback = function () use ($csvHeader, $csvData) {
            $file = fopen('php://output', 'w');
            stream_filter_append($file, 'convert.iconv.utf-8/cp932'); // Windows対応
            fputcsv($file, $csvHeader);
            foreach ($csvData as $row) {
                fputcsv($file, $row);
            }
            fclose($file);
        };

        return Response::stream($callback, 200, [
            'Content-Type' => 'text/csv',
            "Content-Disposition" => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
