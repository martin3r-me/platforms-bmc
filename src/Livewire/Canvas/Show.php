<?php

namespace Platform\Bmc\Livewire\Canvas;

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

        $this->canvas = $canvas;
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => 'BmcCanvas',
            'modelId' => $this->canvas->id,
            'subject' => $this->canvas->name,
            'description' => 'Business Model Canvas',
            'url' => route('bmc.canvases.show', $this->canvas),
            'source' => 'bmc.canvases.show',
            'recipients' => [],
            'meta' => ['view_type' => 'show'],
        ]);
    }

    public function render()
    {
        $this->canvas->load(['buildingBlocks.entries', 'createdByUser', 'snapshots']);

        $canvasData = $this->canvas->toCanvasArray();
        $calcData = (new BmcCalculationService())->calculate($this->canvas);

        $blockTypes = config('bmc-templates.block_types', []);

        return view('bmc::livewire.canvas.show', [
            'canvasData' => $canvasData,
            'calcData' => $calcData,
            'blockTypes' => $blockTypes,
        ])->layout('platform::layouts.app');
    }
}
