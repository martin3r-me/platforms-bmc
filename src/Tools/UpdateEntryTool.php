<?php

namespace Platform\Bmc\Tools;

use Platform\Core\Contracts\ToolContract;
use Platform\Core\Contracts\ToolContext;
use Platform\Core\Contracts\ToolMetadataContract;
use Platform\Core\Contracts\ToolResult;
use Platform\Core\Tools\Concerns\HasStandardizedWriteOperations;
use Platform\Bmc\Models\BmcEntry;
use Platform\Bmc\Tools\Concerns\ResolvesBmcTeam;

class UpdateEntryTool implements ToolContract, ToolMetadataContract
{
    use HasStandardizedWriteOperations;
    use ResolvesBmcTeam;

    public function getName(): string
    {
        return 'bmc.entries.PUT';
    }

    public function getDescription(): string
    {
        return 'PUT /bmc/entries/{id} - Aktualisiert einen BMC Entry. Parameter: entry_id (required). Optional: title, content, position, metadata.';
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
                    'description' => 'ID des Entries (ERFORDERLICH). Nutze "bmc.entries.GET".',
                ],
                'title' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Titel.',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Optional: Neuer Inhalt.',
                ],
                'position' => [
                    'type' => 'integer',
                    'description' => 'Optional: Neue Position.',
                ],
                'metadata' => [
                    'type' => 'object',
                    'description' => 'Optional: Neue Metadaten (JSON).',
                ],
            ],
            'required' => ['entry_id'],
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

            foreach (['title', 'content', 'position'] as $field) {
                if (array_key_exists($field, $arguments)) {
                    $entry->{$field} = $arguments[$field] === '' ? null : $arguments[$field];
                }
            }

            if (array_key_exists('metadata', $arguments)) {
                $entry->metadata = $arguments['metadata'];
            }

            $entry->save();

            return ToolResult::success([
                'id' => $entry->id,
                'uuid' => $entry->uuid,
                'title' => $entry->title,
                'position' => $entry->position,
                'building_block_id' => $entry->bmc_building_block_id,
                'message' => 'Entry erfolgreich aktualisiert.',
            ]);
        } catch (\Throwable $e) {
            return ToolResult::error('EXECUTION_ERROR', 'Fehler beim Aktualisieren des Entries: ' . $e->getMessage());
        }
    }

    public function getMetadata(): array
    {
        return [
            'read_only' => false,
            'category' => 'action',
            'tags' => ['bmc', 'entries', 'update'],
            'risk_level' => 'write',
            'requires_auth' => true,
            'requires_team' => true,
            'idempotent' => true,
        ];
    }
}
