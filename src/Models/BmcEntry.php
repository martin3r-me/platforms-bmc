<?php

namespace Platform\Bmc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class BmcEntry extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'bmc_entries';

    protected $fillable = [
        'uuid',
        'bmc_building_block_id',
        'title',
        'content',
        'position',
        'metadata',
        'created_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
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
        });
    }

    // Relationships

    public function buildingBlock(): BelongsTo
    {
        return $this->belongsTo(BmcBuildingBlock::class, 'bmc_building_block_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }
}
