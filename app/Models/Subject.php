<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['program_id', 'code', 'name', 'year_level', 'min_resources'];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function activeResources()
    {
        return $this->hasMany(Resource::class)->where('status', 'active');
    }

    public function accessLogs()
    {
        return $this->hasManyThrough(AccessLog::class, Resource::class);
    }

    /** "CSC 126 - Computer Vision" */
    public function getLabelAttribute(): string
    {
        return $this->code.' - '.$this->name;
    }
}
