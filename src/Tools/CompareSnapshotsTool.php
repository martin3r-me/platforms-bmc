<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Bmc\Models\BmcCanvasSnapshot;
use Platform\Bmc\Services\BmcCanvasService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class CompareSnapshotsTool implements ToolContract, ToolMetadataContract
{
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.snapshots.compare.GET';
    }

    public function getDescription(): string
    {
        return 'GET /bmc/snapshots/compare - Vergleicht zwei Snapshots und zeigt Unterschiede. ERFORDERLICH: snapshot_a_id, snapshot_b_id.';
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
                'snapshot_a_id' => [
                    'type' => 'integer',
                    'description' => 'ID des ersten Snapshots (ERFORDERLICH).',
                ],
                'snapshot_b_id' => [
                    'type' => 'integer',
                    'description' => 'ID des zweiten Snapshots (ERFORDERLICH).',
                ],
            ],
            'required' => ['snapshot_a_id', 'snapshot_b_id'],
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

            $snapshotAId = (int)($arguments['snapshot_a_id'] ?? 0);
            $snapshotBId = (int)($arguments['snapshot_b_id'] ?? 0);

            if ($snapshotAId <= 0 || $snapshotBId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'Beide snapshot_a_id und snapshot_b_id sind erforderlich.');
            }

            $snapshotA = BmcCanvasSnapshot::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($snapshotAId);

            if (!$snapshotA) {
                return ToolResult::error('NOT_FOUND', 'Snapshot A nicht gefunden (oder kein Zugriff).');
            }

            $snapshotB = BmcCanvasSnapshot::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($snapshotBId);

            if (!$snapshotB) {
                return ToolResult::error('NOT_FOUND', 'Snapshot B nicht gefunden (oder kein Zugriff).');
            }

            if ($snapshotA->bmc_canvas_id !== $snapshotB->bmc_canvas_id) {
                return ToolResult::error('VALIDATION_ERROR', 'Die Snapshots gehören zu unterschiedlichen Canvases.');
            }

            $canvasService = new BmcCanvasService();
            $comparison = $canvasService->compareSnapshots($snapshotA, $snapshotB);

            return ToolResult::success($comparison);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Vergleichen der Snapshots: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => true,
            'category' => 'read',
            'tags' => ['bmc', 'snapshots', 'compare'],
            'risk_level' => 'safe',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
