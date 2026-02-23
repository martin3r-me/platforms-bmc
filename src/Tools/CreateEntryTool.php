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

class CreateEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.entries.POST';
    }

    public function getDescription(): string
    {
        return 'POST /bmc/entries - Erstellt einen Entry in einem Building Block. ERFORDERLICH: building_block_id, title. Optional: content, position, metadata.';
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
                'title' => [
                    'type' => 'string',
                    'description' => 'Titel des Entries (ERFORDERLICH).',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Inhalt/Beschreibung.',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Position (auto-increment wenn nicht angegeben).',
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Optional: Zusätzliche Metadaten (JSON).',
                ],
            ],
            'required' => ['building_block_id', 'title'],
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

            $title = trim((string)($arguments['title'] ?? ''));
            if ($title === '') {
                return ToolResult::error('VALIDATION_ERROR', 'title ist erforderlich.');
            }

            $entryService = new BmcEntryService();
            $entry = $entryService->createEntry($block, [
                'title' => $title,
                'content' => $arguments['content'] ?? null,
                'position' => $arguments['position'] ?? null,
                'metadata' => $arguments['metadata'] ?? null,
                'created_by_user_id' => $context->user->id,
            ]);

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'position' => $entry->position,
                'building_block_id' => $entry->bmc_building_block_id,
                'message' => 'Entry erfolgreich erstellt.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Erstellen des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'entries', 'create'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
