<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Services\BmcCalculationService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class CalculateTool implements ToolContract, ToolMetadataContract
{
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.calculate.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/calculate - Berechnet Canvas-Vollständigkeit und Health-Metriken. ERFORDERLICH: canvas_id.';
    }

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas (ERFORDERLICH).',
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
                ->where('team_id', $teamId)
                ->find($canvasId);

            if (!$canvas) {
                return ToolResult::error('NOT_FOUND', 'Canvas nicht gefunden (oder kein Zugriff).');
            }

            $calculationService = new BmcCalculationService();
            $metrics = $calculationService->calculate($canvas);

            return ToolResult::success($metrics);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Berechnen der Canvas-Metriken: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'calculate', 'metrics'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
