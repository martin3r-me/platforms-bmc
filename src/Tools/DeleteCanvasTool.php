<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class DeleteCanvasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.canvases.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /bmc/canvases/{id} - Soft-löscht einen BMC Canvas. Parameter: canvas_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'canvas_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Canvas (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu löschen.',
                ],
            ],
            'required' => ['canvas_id', 'confirm'],
        ]);
    }

    public function execute(array $arguments, ToolContext $context): ToolResult
    {
        try {
            $resolved = $this->resolveTeam($arguments, $context);
            if ($resolved['error']) {
                return $resolved['error'];
            }
            $teamId = (int)$resolved['team_id'];

            if (!($arguments['confirm'] ?? false)) {
                return ToolResult::error('CONFIRMATION_REQUIRED', 'Bitte bestätige mit confirm: true.');
            }

            $found = $this->validateAndFindModel(
                $arguments,
                $context,
                'canvas_id',
                BmcCanvas::class,
                'NOT_FOUND',
                'Canvas nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var BmcCanvas $canvas */
            $canvas = $found['model'];

            if ((int)$canvas->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Canvas.');
            }

            $canvasId = (int)$canvas->id;
            $canvasName = (string)$canvas->name;

            $canvas->delete();

            return ToolResult::success([
                'canvas_id' => $canvasId,
                'name' => $canvasName,
                'message' => 'Canvas soft-gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Canvas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'canvases', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
