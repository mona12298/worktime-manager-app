<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'work_break_id',
        'column_name',
        'original_value',
        'corrected_value',
        'reason',
        'status',
        'approved_at',
    ];

    public function user(){
        return $this->belongsTo('App\Models\User');
    }

    public function attendance(){
        return $this->belongsTo('App\Models\Attendance');
    }

    public function workBreak(){
        return $this->belongsTo(WorkBreak::class);
    }

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';

    public function getStatusLabelAttribute(): string{
        return $this->status === self::STATUS_PENDING ? '承認待ち' : '承認済み';
    }
}

