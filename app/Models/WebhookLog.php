<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebhookLog extends Model
{
    protected $fillable = ['provider', 'webhook_hash', 'payload', 'status', 'error'];

    protected $casts = [
        'payload' => 'array',
    ];
}
