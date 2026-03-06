<?php

namespace Platform\Bmc\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Platform\Bmc\Models\BmcCanvas;

class CanvasPdfController extends Controller
{
    public function __invoke(BmcCanvas $canvas)
    {
        abort_unless(
            Auth::check() && $canvas->team_id === Auth::user()->currentTeam?->id,
            403,
            'Zugriff verweigert'
        );

        $canvas->load(['buildingBlocks.entries', 'createdByUser']);

        $canvasData = $canvas->toCanvasArray();
        $blockTypes = config('bmc-templates.block_types', []);

        $fontScale = $this->calculateFontScale($canvasData);

        $html = view('bmc::pdf.canvas', [
            'canvas' => $canvas,
            'canvasData' => $canvasData,
            'blockTypes' => $blockTypes,
            'fontScale' => $fontScale,
        ])->render();

        $filename = str($canvas->name ?: 'business-model-canvas')
            ->slug('-')
            ->append('.pdf')
            ->toString();

        return Pdf::loadHTML($html)
            ->setOption('defaultFont', 'DejaVu Sans')
            ->setOption('isHtml5ParserEnabled', true)
            ->setPaper('a4', 'landscape')
            ->download($filename);
    }

    /**
     * Calculate font scale based on total content volume.
     *
     * Returns a scale key: 'lg', 'md', 'sm', 'xs'
     * representing how much content we need to fit.
     */
    private function calculateFontScale(array $canvasData): string
    {
        $totalChars = 0;
        $totalEntries = 0;

        foreach ($canvasData['blocks'] ?? [] as $block) {
            foreach ($block['entries'] ?? [] as $entry) {
                $totalEntries++;
                $totalChars += mb_strlen($entry['title'] ?? '');
                $totalChars += mb_strlen($entry['content'] ?? '');
            }
        }

        // Thresholds tuned for A4 landscape with 9-block Osterwalder grid
        // ~277mm x 190mm usable area
        if ($totalChars < 800 && $totalEntries <= 18) {
            return 'lg';  // Comfortable, larger fonts
        }

        if ($totalChars < 1800 && $totalEntries <= 36) {
            return 'md';  // Standard
        }

        if ($totalChars < 3500 && $totalEntries <= 60) {
            return 'sm';  // Compact
        }

        return 'xs';      // Very dense, minimum readable size
    }
}
