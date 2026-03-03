<?php

namespace Platform\Bmc\Livewire\Swot;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Platform\Bmc\Models\BmcCanvas;

class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function setStatusFilter(string $status): void
    {
        $this->statusFilter = $this->statusFilter === $status ? '' : $status;
        $this->resetPage();
    }

    public function rendered(): void
    {
        $this->dispatch('comms', [
            'model' => null,
            'modelId' => null,
            'subject' => 'SWOT-Analyse',
            'description' => 'SWOT-Uebersicht',
            'url' => route('bmc.swot.index'),
            'source' => 'bmc.swot.index',
            'recipients' => [],
            'meta' => ['view_type' => 'index'],
        ]);
    }

    public function render()
    {
        $user = Auth::user();
        $team = $user->currentTeam;
        $teamId = $team?->id;

        $query = BmcCanvas::forTeam($teamId)
            ->byCanvasType('swot')
            ->withCount('buildingBlocks')
            ->with('createdByUser');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        if ($this->statusFilter) {
            $query->byStatus($this->statusFilter);
        }

        $canvases = $query->orderBy('updated_at', 'desc')->paginate(15);

        $stats = [
            'total' => BmcCanvas::forTeam($teamId)->byCanvasType('swot')->count(),
            'draft' => BmcCanvas::forTeam($teamId)->byCanvasType('swot')->byStatus('draft')->count(),
            'active' => BmcCanvas::forTeam($teamId)->byCanvasType('swot')->byStatus('active')->count(),
            'archived' => BmcCanvas::forTeam($teamId)->byCanvasType('swot')->byStatus('archived')->count(),
        ];

        return view('bmc::livewire.swot.index', [
            'canvases' => $canvases,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
