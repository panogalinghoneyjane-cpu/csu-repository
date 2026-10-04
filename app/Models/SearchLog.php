<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchLog extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'keyword', 'results_count', 'searched_at'];

    protected function casts(): array
    {
        return ['searched_at' => 'datetime'];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
