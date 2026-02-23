<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class GetCanvasTool implements ToolContract, ToolMetadataContract
{
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.canvas.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/canvases/{id} - Ruft einen einzelnen BMC Canvas ab (inkl. Building Blocks und Entries). Parameter: canvas_id (required), team_id (optional).';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas (ERFORDERLICH). Nutze "bmc.canvases.GET" um IDs zu finden.',
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
                ->with(['buildingBlocks.entries', 'contextable'])
                ->withCount('snapshots')
                ->where('team_id', $teamId)
                ->find($canvasId);

            if (!$canvas) {
                return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden (oder kein Zugriff).');
            }

            $canvasData = $canvas->toCanvasArray();

            return ToolResult::success([
                'canvas' => $canvasData['canvas'],
                'blocks' => $canvasData['blocks'],
                'snapshots_count' => $canvas->snapshots_count,
                'contextable' => $canvas->contextable ? [
                    'type' => $canvas->contextable_type,
                    'id' => $canvas->contextable_id,
                ] : null,
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Laden des Canvas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'canvas', 'get'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
