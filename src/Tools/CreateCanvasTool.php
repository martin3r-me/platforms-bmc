<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Services\BmcCanvasService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class CreateCanvasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.canvases.POST';
    }

    public function getDescription(): string
    {
        return 'POST /bmc/canvases - Erstellt einen neuen BMC Canvas (initialisiert automatisch 9 Osterwalder Building Blocks). ERFORDERLICH: name. Optional: description, status (default: draft), contextable_type, contextable_id.';
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
                    'description' => 'Name des Canvas (ERFORDERLICH).',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'archived'],
                    'description' => 'Optional: Status (draft, active, archived). Default: draft.',
                ],
                'contextable_type' => [
                    'type' => 'string',
                    'description' => 'Optional: Polymorphic type (z.B. "Project").',
                ],
                'contextable_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Polymorphic ID.',
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
                'contextable_type' => $arguments['contextable_type'] ?? null,
                'contextable_id' => $arguments['contextable_id'] ?? null,
                'team_id' => $teamId,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $canvas->id,
                'uuid' => $canvas->uuid,
                'name' => $canvas->name,
                'status' => $canvas->status,
                'building_blocks_count' => $canvas->buildingBlocks->count(),
                'team_id' => $canvas->team_id,
                'message' => 'Canvas erstellt mit 9 Osterwalder Building Blocks.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Canvas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'canvases', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
