<x-ui-page>
    {{-- Navbar --}}
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'BMC', 'href' => route('bmc.dashboard'), 'icon' => 'squares-2x2'],
            ['label' => 'SWOT', 'href' => route('bmc.swot.index')],
            ['label' => $canvas->name],
        ]" />
    </x-slot>

    {{-- Main Content --}}
    <x-ui-page-container>
        <div class="space-y-4">
            {{-- SWOT 2x2 Grid --}}
            <div class="grid grid-cols-2 gap-3">
                {{-- Strengths (top-left) --}}
                <div>
                    @include('bmc::livewire.canvas._block', ['blockType' => 'strengths', 'blocks' => $canvasData['blocks'], 'blockTypes' => $blockTypes])
                </div>
                {{-- Weaknesses (top-right) --}}
                <div>
                    @include('bmc::livewire.canvas._block', ['blockType' => 'weaknesses', 'blocks' => $canvasData['blocks'], 'blockTypes' => $blockTypes])
                </div>
                {{-- Opportunities (bottom-left) --}}
                <div>
                    @include('bmc::livewire.canvas._block', ['blockType' => 'opportunities', 'blocks' => $canvasData['blocks'], 'blockTypes' => $blockTypes])
                </div>
                {{-- Threats (bottom-right) --}}
                <div>
                    @include('bmc::livewire.canvas._block', ['blockType' => 'threats', 'blocks' => $canvasData['blocks'], 'blockTypes' => $blockTypes])
                </div>
            </div>
        </div>
    </x-ui-page-container>

    {{-- Left Sidebar --}}
    <x-slot name="sidebar">
        <x-ui-page-sidebar title="SWOT Info" width="w-72" :defaultOpen="true">
            <div class="p-5 space-y-5">
                {{-- Linked BMC Canvas --}}
                @if($linkedBmcCanvas)
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Verknuepftes BMC</h3>
                    <a href="{{ route('bmc.canvases.show', $linkedBmcCanvas) }}" wire:navigate
                       class="d-flex items-center gap-2 p-2 rounded-md bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40 text-xs text-[var(--ui-secondary)] hover:bg-[var(--ui-primary)]/10 hover:text-[var(--ui-primary)] transition-colors">
                        @svg('heroicon-o-squares-2x2', 'w-3.5 h-3.5')
                        {{ $linkedBmcCanvas->name }}
                    </a>
                </div>
                @endif

                {{-- Status --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Status</h3>
                    <x-ui-badge :variant="match($canvas->status) { 'active' => 'success', 'archived' => 'secondary', default => 'warning' }">
                        {{ ucfirst($canvas->status) }}
                    </x-ui-badge>
                </div>

                {{-- Creator & Date --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Details</h3>
                    <div class="space-y-2 text-xs text-[var(--ui-muted)]">
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-user', 'w-3.5 h-3.5')
                            {{ $canvas->createdByUser?->name ?? 'Unbekannt' }}
                        </div>
                        <div class="d-flex items-center gap-2">
                            @svg('heroicon-o-calendar', 'w-3.5 h-3.5')
                            {{ $canvas->created_at?->format('d.m.Y H:i') }}
                        </div>
                    </div>
                </div>

                {{-- Description --}}
                @if($canvas->description)
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Beschreibung</h3>
                    <p class="text-xs text-[var(--ui-muted)] leading-relaxed">{{ $canvas->description }}</p>
                </div>
                @endif

                {{-- Completeness --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-2">Fortschritt</h3>
                    <div class="space-y-2">
                        {{-- Progress Bar --}}
                        <div>
                            <div class="d-flex items-center justify-between text-xs mb-1">
                                <span class="text-[var(--ui-muted)]">Vollstaendigkeit</span>
                                <span class="font-semibold text-[var(--ui-secondary)]">{{ $calcData['completeness_percent'] }}%</span>
                            </div>
                            <div class="w-full h-2 rounded-full bg-[var(--ui-muted-5)]">
                                <div class="h-2 rounded-full transition-all {{ match($calcData['health']) { 'good' => 'bg-green-500', 'partial' => 'bg-yellow-500', 'minimal' => 'bg-orange-500', default => 'bg-[var(--ui-muted)]' } }}"
                                     style="width: {{ $calcData['completeness_percent'] }}%"></div>
                            </div>
                        </div>

                        {{-- Stats --}}
                        <div class="space-y-1.5">
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Quadranten</span>
                                <span class="text-xs font-bold text-[var(--ui-secondary)]">{{ $calcData['filled_blocks'] }}/{{ $calcData['total_blocks'] }}</span>
                            </div>
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Eintraege</span>
                                <span class="text-xs font-bold text-[var(--ui-secondary)]">{{ $calcData['total_entries'] }}</span>
                            </div>
                            <div class="d-flex items-center justify-between p-2 bg-[var(--ui-muted-5)] rounded-md border border-[var(--ui-border)]/40">
                                <span class="text-[11px] text-[var(--ui-muted)]">Health</span>
                                <x-ui-badge :variant="match($calcData['health']) { 'good' => 'success', 'partial' => 'warning', default => 'danger' }" size="sm">
                                    {{ ucfirst($calcData['health']) }}
                                </x-ui-badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Right Sidebar --}}
    <x-slot name="activity">
        <x-ui-page-sidebar title="Empfehlungen" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-5 space-y-5">
                {{-- Missing Blocks --}}
                @if(!empty($calcData['missing_blocks']))
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Fehlende Quadranten</h3>
                    <div class="space-y-3">
                        @foreach($calcData['missing_blocks'] as $missing)
                        <div class="p-3 rounded-lg bg-[var(--ui-muted-5)]/50 border border-[var(--ui-border)]/40">
                            <div class="text-xs font-semibold text-[var(--ui-secondary)] mb-1.5">{{ $missing['label'] }}</div>
                            <ul class="space-y-1">
                                @foreach($missing['guiding_questions'] as $question)
                                <li class="text-[11px] text-[var(--ui-muted)] d-flex items-start gap-1.5">
                                    @svg('heroicon-o-question-mark-circle', 'w-3 h-3 mt-0.5 flex-shrink-0')
                                    {{ $question }}
                                </li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recommendations --}}
                @if(!empty($calcData['recommendations']))
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-3">Hinweise</h3>
                    <div class="space-y-2">
                        @foreach($calcData['recommendations'] as $rec)
                        <div class="d-flex items-start gap-2 p-2 rounded-md bg-yellow-500/10 border border-yellow-500/20">
                            @svg('heroicon-o-light-bulb', 'w-3.5 h-3.5 text-yellow-600 mt-0.5 flex-shrink-0')
                            <span class="text-[11px] text-[var(--ui-secondary)]">{{ $rec }}</span>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </x-ui-page-sidebar>
    </x-slot>
</x-ui-page>
