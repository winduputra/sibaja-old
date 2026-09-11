<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiSyncRun extends Model
{
    use HasFactory;

    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';
    public const STATUS_DRY_RUN = 'dry_run';
    public const STATUSES = [
        self::STATUS_RUNNING,
        self::STATUS_SUCCEEDED,
        self::STATUS_PARTIAL,
        self::STATUS_FAILED,
        self::STATUS_DRY_RUN,
    ];

    protected $fillable = [
        'command',
        'status',
        'is_global',
        'modules',
        'options',
        'failed_module',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'is_global' => 'boolean',
        'modules' => 'array',
        'options' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
