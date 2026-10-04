<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{
    protected $fillable = ['college_id', 'code', 'name'];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function resources()
    {
        return $this->hasManyThrough(Resource::class, Subject::class);
    }
}
