<?php

namespace Platform\Bmc\Livewire\Swot;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Platform\Bmc\Models\BmcCanvas;
use Platform\Bmc\Services\BmcCalculationService;

class Show extends Component
{
    public BmcCanvas $canvas;

    public function mount(BmcCanvas $canvas): void
    {
        abort_unless($canvas->team_id === Auth::user()->currentTeam->id, 403);
        abort_unless(($canvas->canvas_type ?? 'bmc') === 'swot', 404);

        $this->canvas = $canvas;
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => 'BmcCanvas',
            'modelId' => $this->canvas->id,
            'subject' => $this->canvas->name,
            'description' => 'SWOT-Analyse',
            'url' => route('bmc.swot.show', $this->canvas),
            'source' => 'bmc.swot.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show'],
        ]);
    }

    public function render()
    {
        $this->canvas->load(['buildingBlocks.entries', 'createdByUser', 'snapshots', 'contextable']);

        $canvasData = $this->canvas->toCanvasArray();
        $calcData = (new BmcCalculationService())->calculate($this->canvas);

        $blockTypes = config('bmc-templates.swot_block_types', []);

        // Get linked BMC canvas if any
        $linkedBmcCanvas = null;
        if ($this->canvas->contextable_type === BmcCanvas::class && $this->canvas->contextable_id) {
            $linkedBmcCanvas = $this->canvas->contextable;
        }

        return view('bmc::livewire.swot.show', [
            'canvasData' => $canvasData,
            'calcData' => $calcData,
            'blockTypes' => $blockTypes,
            'linkedBmcCanvas' => $linkedBmcCanvas,
        ])->layout('platform::layouts.app');
    }
}
