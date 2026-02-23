<?php

namespace Platform\Bmc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class BmcCanvasSnapshot extends Model
{
    public $timestamps = false;

    protected $table = 'bmc_canvas_snapshots';

    protected $fillable = [
        'uuid',
        'bmc_canvas_id',
        'version',
        'snapshot_data',
        'created_by_user_id',
        'created_at',
    ];

    protected $casts = [
        'snapshot_data' => 'array',
        'version' => 'integer',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                do {
                    $uuid = UuidV7::generate();
                } while (self::where('uuid', $uuid)->exists());
                $model->uuid = $uuid;
            }
            if (empty($model->created_at)) {
                $model->created_at = now();
            }
        });
    }

    // Relationships

    public function canvas(): BelongsTo
    {
        return $this->belongsTo(BmcCanvas::class, 'bmc_canvas_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
}
