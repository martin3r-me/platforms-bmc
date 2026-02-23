<?php

namespace Platform\Bmc\Services;

use Platform\Bmc\Models\BmcBuildingBlock;
use Platform\Bmc\Models\BmcEntry;

class BmcEntryService
{
    /**
     * Create a single entry in a building block.
     */
    public function createEntry(BmcBuildingBlock $block, array $data): BmcEntry
    {
        if (!isset($data['position'])) {
            $data['position'] = ($block->entries()->max('position') ?? 0) + 1;
        }

        return $block->entries()->create($data);
    }

    /**
     * Bulk create entries in a building block.
     *
     * @return array<BmcEntry>
     */
    public function bulkCreateEntries(BmcBuildingBlock $block, array $entriesData, int $userId): array
    {
        $maxPosition = $block->entries()->max('position') ?? 0;
        $created = [];

        foreach ($entriesData as $data) {
            $maxPosition++;
            $created[] = $block->entries()->create([
                'title' => $data['title'],
                'content' => $data['content'] ?? null,
                'position' => $data['position'] ?? $maxPosition,
                'metadata' => $data['metadata'] ?? null,
                'created_by_user_id' => $userId,
            ]);
        }

        return $created;
    }

    /**
     * Reorder entries within a building block.
     *
     * @param array<int> $entryIds Ordered list of entry IDs
     */
    public function reorderEntries(BmcBuildingBlock $block, array $entryIds): void
    {
        foreach ($entryIds as $position => $entryId) {
            $block->entries()
                ->where('id', $entryId)
                ->update(['position' => $position + 1]);
        }
    }
}
