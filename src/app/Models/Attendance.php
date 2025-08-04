<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function correctionRequests(){
        return $this->hasMany('App\Models\CorrectionRequest');
    }

    public function workBreaks(){
        return $this->hasMany(WorkBreak::class);
    }

    public function determineCondition(): string {
        if ($this->clock_out) {
            return '退勤済';
        }
        $last = $this->workBreaks()->latest('occurred_at')->first();
        if ($last && $last->break_type === 'start' && ! $this->hasUnclosedBreak()) {
            return '休憩中';
        }
        return $this->clock_in ? '出勤中' : '勤務外';
    }

    protected function hasUnclosedBreak(): bool{
        $last = $this->workBreaks()->latest('occurred_at')->first();
        return $last && $last->break_type === 'start';
    }

    public function clockIn(): void {
        if($this->clock_in){
            abort(400, '本日はすでに出勤済みです');
        }
        $this->update(['clock_in' => now()]);
    }

    public function startBreak() : void{
        if (! $this->clock_in || $this->clock_out) {
            abort(400, '出勤中のみ休憩に入れます');
        }
        $this->workBreaks()->create([
            'occurred_at' => now(),
            'break_type' => 'start',
        ]);
    }

    public function clockOut(){
        if (! $this->clock_in || $this->clock_out) {
            abort(400, '退勤できません');
        }
        $this->update(['clock_out' => now()]);
    }

    public function getBreakDurationInSeconds(){
        $totalSeconds = 0;
        $breaks = $this->workBreaks->sortBy('occurred_at')->values();
        for ($i = 0; $i < $breaks->count(); $i += 2) {
            if (isset($breaks[$i + 1])) {
                $start = Carbon::parse($breaks[$i]->occurred_at);
                $end = Carbon::parse($breaks[$i + 1]->occurred_at);
                $totalSeconds += $end->diffInSeconds($start);
            }
        }
        return $totalSeconds;
    }

    public function getWorkedDurationInSeconds(){
        if (!$this->clock_in || !$this->clock_out) return 0;

        $clockIn  = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        $total = $clockOut->diffInSeconds($clockIn);
        $break = $this->getBreakDurationInSeconds();

        return $total - $break;
    }

    public function workedHours(){
        $seconds = $this->getWorkedDurationInSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public function breakHours(){
        $seconds = $this->getBreakDurationInSeconds();
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%02d:%02d', $hours, $minutes);
    }

    public static function getMonthDates(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();

        $dates = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()){
            $dates[] = $date->copy();
        }
        return $dates;
    }

    public static function getMonthAttendanceMap(int $userId, int $year, int $month): Collection {
        $start = Carbon::create($year, $month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $attendances = self::where('user_id', $userId)->whereBetween('clock_in', [$start, $end])->with('workBreaks')->get();
        return $attendances->keyBy(fn($att) => $att->clock_in->format('Y-m-d'));
    }
}
