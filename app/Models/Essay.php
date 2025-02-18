<?php

namespace App\Models;

use App\Models\Offense;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Essay extends Model
{
    use HasFactory;
    protected $fillable = ['offense_id','content'];

    public function offense()
    {
        return $this->belongsTo(Offense::class);
    }
    public function reviews()
    {
        return $this->hasMany(Review::class, 'essay_id');
    }
}