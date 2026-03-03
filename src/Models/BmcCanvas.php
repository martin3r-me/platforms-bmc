<?php

namespace Platform\Bmc\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Platform\ActivityLog\Traits\LogsActivity;
use Symfony\Component\Uid\UuidV7;

class BmcCanvas extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'bmc_canvases';

    protected $fillable = [
        'uuid',
        'team_id',
        'name',
        'description',
        'status',
        'canvas_type',
        'contextable_type',
        'contextable_id',
        'created_by_user_id',
    ];

    protected $casts = [
        'status' => 'string',
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

    public function team(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\Team::class, 'team_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(\Platform\Core\Models\User::class, 'created_by_user_id');
    }

    public function contextable(): MorphTo
    {
        return $this->morphTo();
    }

    public function buildingBlocks(): HasMany
    {
        return $this->hasMany(BmcBuildingBlock::class, 'bmc_canvas_id')->orderBy('position');
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(BmcCanvasSnapshot::class, 'bmc_canvas_id')->orderBy('version', 'desc');
    }

    // Scopes

    public function scopeForTeam($query, int $teamId)
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCanvasType($query, string $canvasType)
    {
        return $query->where('canvas_type', $canvasType);
    }

    /**
     * Get the config key for block types based on canvas_type.
     */
    public function getBlockTypesConfigKey(): string
    {
        return match ($this->canvas_type) {
            'swot' => 'bmc-templates.swot_block_types',
            default => 'bmc-templates.block_types',
        };
    }

    /**
     * Get the block types config for this canvas.
     */
    public function getBlockTypesConfig(): array
    {
        return config($this->getBlockTypesConfigKey(), []);
    }

    /**
     * Initialize building blocks from config based on canvas_type.
     */
    public function initializeBlocks(): void
    {
        $blockTypes = $this->getBlockTypesConfig();

        foreach ($blockTypes as $type => $definition) {
            $this->buildingBlocks()->create([
                'block_type' => $type,
                'label' => $definition['label'],
                'position' => $definition['position'],
            ]);
        }
    }

    /**
     * Export the full canvas data as an array.
     */
    public function toCanvasArray(): array
    {
        $this->loadMissing(['buildingBlocks.entries']);

        $blocks = [];
        foreach ($this->buildingBlocks as $block) {
            $blocks[$block->block_type] = [
                'id' => $block->id,
                'label' => $block->label,
                'position' => $block->position,
                'entries' => $block->entries->map(fn (BmcEntry $e) => [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'title' => $e->title,
                    'content' => $e->content,
                    'position' => $e->position,
                    'metadata' => $e->metadata,
                ])->values()->toArray(),
            ];
        }

        return [
            'canvas' => [
                'id' => $this->id,
                'uuid' => $this->uuid,
                'name' => $this->name,
                'description' => $this->description,
                'status' => $this->status,
                'canvas_type' => $this->canvas_type ?? 'bmc',
                'team_id' => $this->team_id,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'blocks' => $blocks,
        ];
    }
}
