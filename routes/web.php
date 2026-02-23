<?php

use Platform\Bmc\Livewire\Canvas\Index;
use Platform\Bmc\Livewire\Canvas\Show;

Route::get('/', Index::class)->name('bmc.dashboard');
Route::get('/canvases', Index::class)->name('bmc.canvases.index');
Route::get('/canvases/{canvas}', Show::class)->name('bmc.canvases.show');
