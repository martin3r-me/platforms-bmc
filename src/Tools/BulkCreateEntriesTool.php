<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcBuildingBlock;
use Platform\Bmc\Services\BmcEntryService;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class BulkCreateEntriesTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.entries.bulk.POST';
    }

    public function getDescription(): string
    {
        return 'POST /bmc/entries/bulk - Bulk-Erstellung von Entries in einem Building Block. ERFORDERLICH: building_block_id, entries (Array mit {title, content?, metadata?}).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'building_block_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Building Blocks (ERFORDERLICH).',
                ],
                'entries' => [
                    'type' => 'array',
                    'description' => 'Array von Entry-Objekten mit {title, content?, metadata?} (ERFORDERLICH).',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'content' => ['type' => 'string'],
                            'metadata' => ['type' => 'object'],
                        ],
                        'required' => ['title'],
                    ],
                ],
            ],
            'required' => ['building_block_id', 'entries'],
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

            $blockId = (int)($arguments['building_block_id'] ?? 0);
            if ($blockId <= 0) {
                return ToolResult::error('VALIDATION_ERROR', 'building_block_id ist erforderlich.');
            }

            $block = BmcBuildingBlock::query()
                ->whereHas('canvas', fn($q) => $q->where('team_id', $teamId))
                ->find($blockId);

            if (!$block) {
                return ToolResult::error('NOT_FOUND', 'Building Block nicht gefunden (oder kein Zugriff).');
            }

            $entriesData = $arguments['entries'] ?? [];
            if (!is_array($entriesData) || empty($entriesData)) {
                return ToolResult::error('VALIDATION_ERROR', 'entries Array ist erforderlich und darf nicht leer sein.');
            }

            $entryService = new BmcEntryService();
            $created = $entryService->bulkCreateEntries($block, $entriesData, $context->user->id);

            return ToolResult::success([
                'building_block_id' => $blockId,
                'created_count' => count($created),
                'entries' => array_map(fn($e) => [
                    'id' => $e->id,
                    'uuid' => $e->uuid,
                    'title' => $e->title,
                    'position' => $e->position,
                ], $created),
                'message' => count($created) . ' Entries erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Bulk-Erstellen der Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'entries', 'bulk', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
