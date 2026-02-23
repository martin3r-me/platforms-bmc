<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class UpdateCanvasTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.canvases.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /bmc/canvases/{id} - Aktualisiert einen BMC Canvas. Parameter: canvas_id (required). Optional: name, description, status.';
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
                    'description' => 'ID des Canvas (ERFORDERLICH). Nutze "bmc.canvases.GET".',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Name.',
                ],
                'description' => [
                    'type' => 'string',
                    'description' => 'Optional: Neue Beschreibung.',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'active', 'archived'],
                    'description' => 'Optional: Neuer Status (draft, active, archived).',
                ],
            ],
            'required' => ['canvas_id'],
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

            foreach (['name', 'description'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $canvas->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            if (array_key_exists('status', $arguments)) {
                $newStatus = $arguments['status'];
                if (!in_array($newStatus, ['draft', 'active', 'archived'])) {
                    return ToolResult::error('VALIDATION_ERROR', 'Ungültiger Status. Erlaubt: draft, active, archived.');
                }
                $canvas->status = $newStatus;
            }

            $canvas->save();

            return ToolResult::success([
                'id' => $canvas->id,
                'uuid' => $canvas->uuid,
                'name' => $canvas->name,
                'status' => $canvas->status,
                'team_id' => $canvas->team_id,
                'message' => 'Canvas erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Canvas: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'canvases', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
