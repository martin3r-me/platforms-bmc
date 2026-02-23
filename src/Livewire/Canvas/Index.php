<?php

namespace Platform\Bmc\Livewire\Canvas;

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
            'subject' => 'Business Model Canvas',
            'description' => 'Canvas-Übersicht',
            'url' => route('bmc.canvases.index'),
            'source' => 'bmc.canvases.index',
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
            'total' => BmcCanvas::forTeam($teamId)->count(),
            'draft' => BmcCanvas::forTeam($teamId)->byStatus('draft')->count(),
            'active' => BmcCanvas::forTeam($teamId)->byStatus('active')->count(),
            'archived' => BmcCanvas::forTeam($teamId)->byStatus('archived')->count(),
        ];

        return view('bmc::livewire.canvas.index', [
            'canvases' => $canvases,
            'stats' => $stats,
        ])->layout('platform::layouts.app');
    }
}
