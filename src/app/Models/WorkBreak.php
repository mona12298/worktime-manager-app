<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'break_type',
        'occurred_at',
    ];

    public function attendance(){
        return $this->belongsTo(Attendance::class);
    }

}
