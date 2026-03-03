<?php

use Platform\Bmc\Livewire\Canvas\Index;
use Platform\Bmc\Livewire\Canvas\Show;
use Platform\Bmc\Livewire\Swot\Index as SwotIndex;
use Platform\Bmc\Livewire\Swot\Show as SwotShow;

Route::get('/', Index::class)->name('bmc.dashboard');
Route::get('/canvases', Index::class)->name('bmc.canvases.index');
Route::get('/canvases/{canvas}', Show::class)->name('bmc.canvases.show');

Route::get('/swot', SwotIndex::class)->name('bmc.swot.index');
Route::get('/swot/{canvas}', SwotShow::class)->name('bmc.swot.show');
