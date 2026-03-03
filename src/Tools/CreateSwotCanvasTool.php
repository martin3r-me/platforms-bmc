<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Services\BmcCanvasService;
use Platform\Bmc\Services\SwotMatrixService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class CreateSwotCanvasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.swot.POST';
    }

    public function getDescription(): string
    {
        return 'POST /bmc/swot - Erstellt eine neue SWOT-Analyse (4 Blocks: Strengths, Weaknesses, Opportunities, Threats). Optional mit BMC-Canvas verknuepfbar.';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Name der SWOT-Analyse (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'archived'],
                    'description' => 'Optional: Status. Default: draft.',
                ],
                'linked_bmc_canvas_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: ID eines BMC-Canvas, mit dem die SWOT-Analyse verknuepft werden soll.',
                ],
            ],
            'required' => ['name'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            if (!$context->user) {
                return ToolResult::error('AUTH_ERROR', 'Kein User im Kontext gefunden.');
            }

            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            $name = trim((string)($arguments['name'] ?? ''));
            if ($name === '') {
                return ToolResult::error('VALIDATION_ERROR', 'name ist erforderlich.');
            }

            $canvasService = new BmcCanvasService();
            $canvas = $canvasService->createCanvas([
                'name' => $name,
                'description' => $arguments['description'] ?? null,
                'status' => $arguments['status'] ?? 'draft',
                'canvas_type' => 'swot',
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
            ]);

            // Link to BMC canvas if requested
            $linkedBmcId = $arguments['linked_bmc_canvas_id'] ?? null;
            if ($linkedBmcId) {
                $bmcCanvas = BmcCanvas::where('team_id', $teamId)
                    ->where('canvas_type', 'bmc')
                    ->find((int)$linkedBmcId);

                if (!$bmcCanvas) {
                    return ToolResult::error('NOT_FOUND', 'BMC-Canvas nicht gefunden (ID: ' . $linkedBmcId . ').');
                }

                (new SwotMatrixService())->linkToBmc($canvas, $bmcCanvas);
                $canvas->refresh();
            }

            return ToolResult::success([
                'id' => $canvas->id,
                'uuid' => $canvas->uuid,
                'name' => $canvas->name,
                'status' => $canvas->status,
                'canvas_type' => 'swot',
                'building_blocks_count' => $canvas->buildingBlocks->count(),
                'linked_bmc_canvas_id' => $canvas->contextable_id,
                'team_id' => $canvas->team_id,
                'message' => 'SWOT-Analyse erstellt mit 4 Blocks (Strengths, Weaknesses, Opportunities, Threats).',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen der SWOT-Analyse: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'swot', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
