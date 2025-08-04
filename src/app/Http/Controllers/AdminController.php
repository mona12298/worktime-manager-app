<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;

class AdminController extends Controller
{
    public function indexAdminAttendance(Request $request){
        $date = Carbon::parse($request->input('date', Carbon::today()->toDateString()));

        $attendances = Attendance::with('user')
        ->whereHas('user', function($query){
            $query->where('is_admin', 0);
        })
        ->whereDate('clock_in', $date)->get();

        return view('admin_list',compact(
            'date',
            'attendances'
        ));
    }

    public function showAdminAttendance($id){
        $attendance = Attendance::with('user')->findOrFail($id);
        return view('admin_detail', compact('attendance'));
    }

    public function indexStaff(){
        $users = User::with('attendances')
        ->where('is_admin', 0)->get();
        return view('admin_staff_list', compact('users'));
    }

    public function indexAdminStampRequests(){
        $correctionRequests = User::with('correctionRequests')->where('is_admin', 0)->get();
        return view('admin_request', compact('correctionRequests'));
    }

    public function showStampCorrectionRequest($attendance_correct_request){
        $request = CorrectionRequest::findOrFail($attendance_correct_request);
        return view('admin_approve', compact('request'));
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
}