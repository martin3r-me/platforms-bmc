<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Models\BmcBuildingBlock;
use Platform\Bmc\Models\BmcEntry;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class ListEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.entries.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/entries - Listet Entries für einen Canvas oder Building Block. Parameter: canvas_id (optional) oder building_block_id (optional), block_type (optional filter bei canvas_id).';
    }

    public function getSchema(): array
    {
        return $this->mergeSchemas(
            $this->getStandardGetSchema(),
            [
                'properties' => [
                    'team_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                    ],
                    'canvas_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Canvas-ID. Liefert alle Entries über alle Blocks.',
                    ],
                    'building_block_id' => [
                        'type' => 'integer',
                        'description' => 'Optional: Building Block-ID. Liefert nur Entries für diesen Block.',
                    ],
                    'block_type' => [
                        'type' => 'string',
                        'description' => 'Optional: Filter nach Block-Type (nur bei canvas_id).',
                    ],
                ],
            ]
        );
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $canvasId = $arguments['canvas_id'] ?? null;
            $blockId = $arguments['building_block_id'] ?? null;

            if (!$canvasId && !$blockId) {
                return ToolResult::error('VALIDATION_ERROR', 'Entweder canvas_id oder building_block_id ist erforderlich.');
            }

            if ($blockId) {
                $block = BmcBuildingBlock::query()
                    ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                    ->find($blockId);

                if (!$block) {
                    return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden (oder kein Zugriff).');
                }

                $query = BmcEntry::query()
                    ->where('bmc_building_block_id', $blockId)
                    ->with('buildingBlock:id,block_type,label');
            } else {
                $canvas = BmcCanvas::query()
                    ->where('team_id', $teamId)
                    ->find($canvasId);

                if (!$canvas) {
                    return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden (oder kein Zugriff).');
                }

                $query = BmcEntry::query()
                    ->whereHas('buildingBlock', function($q) use ($canvasId, $arguments) {
                        $q->where('bmc_canvas_id', $canvasId);
                        if (isset($arguments['block_type'])) {
                            $q->where('block_type', $arguments['block_type']);
                        }
                    })
                    ->with('buildingBlock:id,block_type,label');
            }

            $this->applyStandardSort($query, $arguments, [
                'position',
                'created_at',
                'updated_at',
            ], 'position', 'asc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (BmcEntry $entry) {
                return [
                    'id' => $entry->id,
                    'uuid' => $entry->uuid,
                    'title' => $entry->title,
                    'content' => $entry->content,
                    'position' => $entry->position,
                    'metadata' => $entry->metadata,
                    'block_type' => $entry->buildingBlock?->block_type,
                    'block_label' => $entry->buildingBlock?->label,
                    'building_block_id' => $entry->bmc_building_block_id,
                    'created_at' => $entry->created_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'entries', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
