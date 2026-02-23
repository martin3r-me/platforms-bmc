<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardGetOperations;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class ListCanvasesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardGetOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.canvases.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/canvases - Listet BMC Canvases. Parameter: team_id (optional), status (optional), filters/search/sort/limit/offset (optional).';
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
                    'status' => [
                        'type' => 'string',
                        'enum' => ['draft', 'active', 'archived'],
                        'description' => 'Optional: Filter nach Status (draft, active, archived).',
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

            $query = BmcCanvas::query()
                ->withCount('buildingBlocks', 'snapshots')
                ->forTeam($teamId);

            if (isset($arguments['status'])) {
                $query->byStatus($arguments['status']);
            }

            $this->applyStandardFilters($query, $arguments, [
                'name',
                'status',
                'created_at',
                'updated_at',
            ]);
            $this->applyStandardSearch($query, $arguments, ['name', 'description']);
            $this->applyStandardSort($query, $arguments, [
                'name',
                'status',
                'created_at',
                'updated_at',
            ], 'created_at', 'desc');

            $result = $this->applyStandardPaginationResult($query, $arguments);

            $data = collect($result['data'])->map(function (BmcCanvas $canvas) {
                return [
                    'id' => $canvas->id,
                    'uuid' => $canvas->uuid,
                    'name' => $canvas->name,
                    'description' => $canvas->description,
                    'status' => $canvas->status,
                    'building_blocks_count' => $canvas->building_blocks_count,
                    'snapshots_count' => $canvas->snapshots_count,
                    'team_id' => $canvas->team_id,
                    'created_at' => $canvas->created_at?->toISOString(),
                    'updated_at' => $canvas->updated_at?->toISOString(),
                ];
            })->values()->toArray();

            return ToolResult::success([
                'data' => $data,
                'pagination' => $result['pagination'] ?? null,
                'team_id' => $teamId,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der Canvases: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'canvases', 'list'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
