<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'id_number', 'email', 'role', 'college_id', 'is_active', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function recommendations()
    {
        return $this->hasMany(Recommendation::class, 'faculty_id');
    }

    public function isLibrarian(): bool
    {
        return $this->role === 'librarian';
    }

    public function isFaculty(): bool
    {
        return $this->role === 'faculty';
    }

    public function isStudent(): bool
    {
        return $this->role === 'student';
    }
}
