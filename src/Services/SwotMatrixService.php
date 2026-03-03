<?php

namespace Platform\Bmc\Services;

use Platform\Bmc\Models\BmcCanvas;

class SwotMatrixService
{
    /**
     * Generate TOWS matrix (SO/ST/WO/WT strategies) from a SWOT canvas.
     */
    public function generateMatrix(BmcCanvas $canvas): array
    {
        $canvas->loadMissing(['buildingBlocks.entries']);

        $blocks = [];
        foreach ($canvas->buildingBlocks as $block) {
            $blocks[$block->block_type] = $block->entries->map(fn ($e) => [
                'id' => $e->id,
                'title' => $e->title,
                'content' => $e->content,
            ])->values()->toArray();
        }

        $strengths = $blocks['strengths'] ?? [];
        $weaknesses = $blocks['weaknesses'] ?? [];
        $opportunities = $blocks['opportunities'] ?? [];
        $threats = $blocks['threats'] ?? [];

        return [
            'canvas_id' => $canvas->id,
            'canvas_name' => $canvas->name,
            'swot_summary' => [
                'strengths' => count($strengths),
                'weaknesses' => count($weaknesses),
                'opportunities' => count($opportunities),
                'threats' => count($threats),
            ],
            'tows_matrix' => [
                'SO' => [
                    'label' => 'SO-Strategien (Maxi-Maxi)',
                    'description' => 'Staerken nutzen, um Chancen zu ergreifen.',
                    'strengths' => $strengths,
                    'opportunities' => $opportunities,
                ],
                'ST' => [
                    'label' => 'ST-Strategien (Maxi-Mini)',
                    'description' => 'Staerken nutzen, um Bedrohungen abzuwehren.',
                    'strengths' => $strengths,
                    'threats' => $threats,
                ],
                'WO' => [
                    'label' => 'WO-Strategien (Mini-Maxi)',
                    'description' => 'Schwaechen ueberwinden, um Chancen zu nutzen.',
                    'weaknesses' => $weaknesses,
                    'opportunities' => $opportunities,
                ],
                'WT' => [
                    'label' => 'WT-Strategien (Mini-Mini)',
                    'description' => 'Schwaechen minimieren und Bedrohungen vermeiden.',
                    'weaknesses' => $weaknesses,
                    'threats' => $threats,
                ],
            ],
        ];
    }

    /**
     * Link a SWOT canvas to a BMC canvas via contextable.
     */
    public function linkToBmc(BmcCanvas $swotCanvas, BmcCanvas $bmcCanvas): void
    {
        $swotCanvas->update([
            'contextable_type' => BmcCanvas::class,
            'contextable_id' => $bmcCanvas->id,
        ]);
    }
}
