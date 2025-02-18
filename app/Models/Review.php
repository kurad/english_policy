<?php

namespace App\Models;

use App\Models\User;
use App\Models\Essay;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;
    protected $fillable = ['essay_id','teacher_id','comments'];

    public function essay()
    {
        return $this->belongsTo(Essay::class, 'essay_id');
    }

    // A review belongs to a teacher
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

   
}