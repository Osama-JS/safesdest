<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'purpose',
        'template_name',
        'language',
        'status',
        'category',
        'meta_status',
        'body_text',
        'components',
    ];

    protected $casts = [
        'components' => 'array',
        'status' => 'boolean',
    ];
}
