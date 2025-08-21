<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'link_id',
        'user_agent',
        'ip_address',
    ];

    public function link()
    {
        return $this->belongsTo(Link::class);
    }
}