<?php

namespace Platform\Bmc\Organization;

use Illuminate\Database\Eloquent\Builder;
use Platform\Organization\Contracts\EntityLinkProvider;

class BmcEntityLinkProvider implements EntityLinkProvider
{
    public function morphAliases(): array
    {
        return ['bmc_canvas'];
    }

    public function linkTypeConfig(): array
    {
        return [
            'bmc_canvas' => ['label' => 'BMC', 'icon' => 'squares-2x2', 'route' => null],
        ];
    }

    public function applyEagerLoading(Builder $query, string $morphAlias, string $fqcn): void
    {
        $query->withCount('buildingBlocks');
    }

    public function extractMetadata(string $morphAlias, mixed $model): array
    {
        return [
            'status' => $model->status ?? null,
            'block_count' => (int) ($model->building_blocks_count ?? 0),
        ];
    }

    public function metadataDisplayRules(): array
    {
        return [
            'bmc_canvas' => [
                ['field' => 'status', 'format' => 'badge'],
                ['field' => 'block_count', 'format' => 'count', 'suffix' => 'Blocks'],
            ],
        ];
    }

    public function timeTrackableCascades(): array
    {
        return [];
    }
}
