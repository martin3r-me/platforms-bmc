<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcEntry;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class DeleteEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.entries.DELETE';
    }

    public function getDescription(): string
    {
        return 'DELETE /bmc/entries/{id} - Soft-löscht einen BMC Entry. Parameter: entry_id (required), confirm (required=true).';
    }

    public function getSchema(): array
    {
        return $this->mergeWriteSchema([
            'properties' => [
                'team_id' => [
                    'type' => 'integer',
                    'description' => 'Optional: Team-ID. Default: aktuelles Team aus Kontext.',
                ],
                'entry_id' => [
                    'type' => 'integer',
                    'description' => 'ID des Entries (ERFORDERLICH).',
                ],
                'confirm' => [
                    'type' => 'boolean',
                    'description' => 'ERFORDERLICH: Setze confirm=true um wirklich zu löschen.',
                ],
            ],
            'required' => ['entry_id', 'confirm'],
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
                'entry_id',
                BmcEntry::class,
                'NOT_FOUND',
                'Entry nicht gefunden.'
            );
            if ($found['error']) {
                return $found['error'];
            }
            /** @var BmcEntry $entry */
            $entry = $found['model'];

            $entry->load('buildingBlock.canvas');
            if ((int)$entry->buildingBlock->canvas->team_id !== $teamId) {
                return ToolResult::error('ACCESS_DENIED', 'Du hast keinen Zugriff auf diesen Entry.');
            }

            $entryId = (int)$entry->id;
            $entryTitle = (string)$entry->title;

            $entry->delete();

            return ToolResult::success([
                'entry_id' => $entryId,
                'title' => $entryTitle,
                'message' => 'Entry soft-gelöscht.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Löschen des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'entries', 'delete'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => false,
        ];
    }
}
