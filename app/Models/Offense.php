<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Offense extends Model
{
    use HasFactory;
    protected $fillable = [
        'student_id', 'teacher_id', 'word_count', 'essay_topic','offense_count', 'completed', 'due_date'
    ];
    public function essay()
    {
        return $this->hasOne(Essay::class);
    }
    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
}