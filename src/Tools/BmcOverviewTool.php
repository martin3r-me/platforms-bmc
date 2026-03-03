<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;

class BmcOverviewTool implements ToolContract, ToolMetadataContract
{
    public function getName(): string
    {
        return 'bmc.overview.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/overview - Zeigt Uebersicht ueber das Business Model Canvas Modul (Konzepte, Block-Typen, verfuegbare Tools).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => new \stdClass(),
            'required' => [],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $blockTypes = config('bmc-templates.block_types', []);
            $swotBlockTypes = config('bmc-templates.swot_block_types', []);

            return ToolResult::success([
                'module' => 'bmc',
                'scope' => [
                    'team_scoped' => true,
                    'team_id_source' => 'ToolContext.team bzw. team_id Parameter',
                ],
                'canvas_types' => [
                    'bmc' => [
                        'label' => 'Business Model Canvas',
                        'blocks_count' => 9,
                        'note' => 'Osterwalder Business Model Canvas mit 9 Building Blocks.',
                    ],
                    'swot' => [
                        'label' => 'SWOT-Analyse',
                        'blocks_count' => 4,
                        'note' => 'SWOT-Analyse mit 4 Quadranten: Strengths, Weaknesses, Opportunities, Threats. Kann mit BMC verknuepft werden.',
                    ],
                ],
                'concepts' => [
                    'bmc_canvases' => [
                        'model' => 'Platform\\Bmc\\Models\\BmcCanvas',
                        'table' => 'bmc_canvases',
                        'key_fields' => ['id', 'uuid', 'name', 'description', 'status', 'canvas_type', 'contextable_type', 'contextable_id', 'team_id'],
                        'note' => 'Ein Canvas (BMC oder SWOT). canvas_type bestimmt den Typ. BMC: 9 Blocks, SWOT: 4 Blocks. Status: draft, active, archived.',
                    ],
                    'bmc_building_blocks' => [
                        'model' => 'Platform\\Bmc\\Models\\BmcBuildingBlock',
                        'table' => 'bmc_building_blocks',
                        'key_fields' => ['id', 'uuid', 'bmc_canvas_id', 'block_type', 'label', 'position'],
                        'note' => 'Die Bausteine eines Canvas. BMC: 9, SWOT: 4. Werden automatisch beim Erstellen angelegt.',
                    ],
                    'bmc_entries' => [
                        'model' => 'Platform\\Bmc\\Models\\BmcEntry',
                        'table' => 'bmc_entries',
                        'key_fields' => ['id', 'uuid', 'bmc_building_block_id', 'title', 'content', 'position', 'metadata'],
                        'note' => 'Einzelne Eintraege innerhalb eines Building Blocks.',
                    ],
                    'bmc_canvas_snapshots' => [
                        'model' => 'Platform\\Bmc\\Models\\BmcCanvasSnapshot',
                        'table' => 'bmc_canvas_snapshots',
                        'key_fields' => ['id', 'uuid', 'bmc_canvas_id', 'version', 'snapshot_data'],
                        'note' => 'Versionierte Snapshots eines Canvas fuer Vergleiche.',
                    ],
                ],
                'bmc_block_types' => collect($blockTypes)->map(fn ($def, $type) => [
                    'type' => $type,
                    'label' => $def['label'],
                    'description' => $def['description'],
                ])->values()->toArray(),
                'swot_block_types' => collect($swotBlockTypes)->map(fn ($def, $type) => [
                    'type' => $type,
                    'label' => $def['label'],
                    'description' => $def['description'],
                ])->values()->toArray(),
                'relationships' => [
                    'canvas_has_blocks' => 'BmcCanvas -> BmcBuildingBlocks (BMC: 9, SWOT: 4, automatisch)',
                    'block_has_entries' => 'BmcBuildingBlock -> BmcEntries',
                    'canvas_has_snapshots' => 'BmcCanvas -> BmcCanvasSnapshots',
                    'swot_linked_to_bmc' => 'SWOT Canvas -> BMC Canvas (via contextable, optional)',
                ],
                'related_tools' => [
                    'canvases' => [
                        'list' => 'bmc.canvases.GET',
                        'get' => 'bmc.canvas.GET',
                        'create' => 'bmc.canvases.POST',
                        'update' => 'bmc.canvases.PUT',
                        'delete' => 'bmc.canvases.DELETE',
                    ],
                    'swot' => [
                        'create' => 'bmc.swot.POST',
                        'list' => 'bmc.swot.GET',
                        'matrix' => 'bmc.swot.matrix.GET',
                    ],
                    'entries' => [
                        'list' => 'bmc.entries.GET',
                        'create' => 'bmc.entries.POST',
                        'update' => 'bmc.entries.PUT',
                        'delete' => 'bmc.entries.DELETE',
                        'bulk_create' => 'bmc.entries.bulk.POST',
                        'reorder' => 'bmc.entries.reorder.PUT',
                    ],
                    'snapshots' => [
                        'list' => 'bmc.snapshots.GET',
                        'create' => 'bmc.snapshots.POST',
                        'get' => 'bmc.snapshot.GET',
                        'compare' => 'bmc.snapshots.compare.GET',
                    ],
                    'utilities' => [
                        'export' => 'bmc.export.GET',
                        'calculate' => 'bmc.calculate.GET',
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden der BMC-Uebersicht: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'category' => 'overview',
            'tags' => ['overview', 'help', 'bmc', 'canvas'],
            'read_only' => true,
            'requires_auth' => true,
            'requires_team' => true,
            'risk_level' => 'safe',
            'idempotent' => true,
        ];
    }
}
