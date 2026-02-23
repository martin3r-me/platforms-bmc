<?php

namespace Platform\Bmc\Services;

use Platform\Bmc\Models\BmcCanvas;

class BmcCalculationService
{
    /**
     * Calculate canvas completeness and health metrics.
     */
    public function calculate(BmcCanvas $canvas): array
    {
        $canvas->loadMissing(['buildingBlocks.entries']);

        $blockTypes = config('bmc-templates.block_types', []);
        $totalBlocks = count($blockTypes);
        $filledBlocks = 0;
        $totalEntries = 0;
        $blockStats = [];

        foreach ($canvas->buildingBlocks as $block) {
            $entryCount = $block->entries->count();
            $totalEntries += $entryCount;

            if ($entryCount > 0) {
                $filledBlocks++;
            }

            $blockStats[$block->block_type] = [
                'label' => $block->label,
                'entry_count' => $entryCount,
                'is_filled' => $entryCount > 0,
                'guiding_questions' => $block->getGuidingQuestions(),
                'guiding_questions_count' => count($block->getGuidingQuestions()),
            ];
        }

        $completeness = $totalBlocks > 0 ? round(($filledBlocks / $totalBlocks) * 100, 1) : 0;

        // Determine health status
        $health = match (true) {
            $completeness >= 80 => 'good',
            $completeness >= 50 => 'partial',
            $completeness > 0 => 'minimal',
            default => 'empty',
        };

        // Identify missing blocks
        $missingBlocks = [];
        foreach ($blockTypes as $type => $definition) {
            if (!isset($blockStats[$type]) || !$blockStats[$type]['is_filled']) {
                $missingBlocks[] = [
                    'block_type' => $type,
                    'label' => $definition['label'],
                    'guiding_questions' => $definition['guiding_questions'],
                ];
            }
        }

        return [
            'canvas_id' => $canvas->id,
            'canvas_name' => $canvas->name,
            'completeness_percent' => $completeness,
            'health' => $health,
            'filled_blocks' => $filledBlocks,
            'total_blocks' => $totalBlocks,
            'total_entries' => $totalEntries,
            'block_stats' => $blockStats,
            'missing_blocks' => $missingBlocks,
            'recommendations' => $this->generateRecommendations($blockStats, $missingBlocks),
        ];
    }

    private function generateRecommendations(array $blockStats, array $missingBlocks): array
    {
        $recommendations = [];

        if (!empty($missingBlocks)) {
            $labels = array_column($missingBlocks, 'label');
            $recommendations[] = 'Noch nicht ausgefuellt: ' . implode(', ', $labels) . '.';
        }

        // Check for blocks with very few entries
        foreach ($blockStats as $type => $stats) {
            if ($stats['entry_count'] === 1) {
                $recommendations[] = "'{$stats['label']}' hat nur 1 Eintrag - mehr Details wuerden das Canvas verbessern.";
            }
        }

        return $recommendations;
    }
}
