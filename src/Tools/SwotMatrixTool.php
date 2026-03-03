<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Services\SwotMatrixService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class SwotMatrixTool implements ToolContract, ToolMetadataContract
{
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.swot.matrix.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/swot/matrix - Generiert TOWS-Matrix (SO/ST/WO/WT Strategien) aus einer SWOT-Analyse. Parameter: canvas_id (required).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID der SWOT-Analyse (ERFORDERLICH). Nutze "bmc.swot.GET" um IDs zu finden.',
                ],
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
            ],
            'required' => ['canvas_id'],
        ];
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $canvasId = (int)($arguments['canvas_id'] ?? 0);
            if ($canvasId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'canvas_id ist erforderlich.');
            }

            $canvas = BmcCanvas::query()
                ->with(['buildingBlocks.entries'])
                ->where('team_id', $teamId)
                ->where('canvas_type', 'swot')
                ->find($canvasId);

            if (!$canvas) {
                return ToolResult::error('NOT_FOUND', 'SWOT-Canvas nicht gefunden (oder kein Zugriff). Stelle sicher, dass es sich um eine SWOT-Analyse handelt.');
            }

            $matrix = (new SwotMatrixService())->generateMatrix($canvas);

            return ToolResult::success($matrix);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Generieren der TOWS-Matrix: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'swot', 'matrix', 'tows'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
