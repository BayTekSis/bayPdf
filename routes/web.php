<?php

use BayPdf\Http\AuthorizeDesigner;
use BayPdf\Http\DesignerController;
use Illuminate\Support\Facades\Route;

Route::prefix(config('baypdf.path'))
    ->middleware([...config('baypdf.middleware'), AuthorizeDesigner::class])
    ->name('baypdf.')
    ->group(function (): void {
        Route::get('/', [DesignerController::class, 'index'])->name('designer');
        Route::get('/api/catalog', [DesignerController::class, 'catalog'])->name('catalog');
        Route::get('/api/templates', [DesignerController::class, 'templates'])->name('templates');
        Route::post('/api/templates', [DesignerController::class, 'store'])->name('store');
        Route::get('/api/templates/{template}', [DesignerController::class, 'show'])->name('show');
        Route::put('/api/versions/{version}', [DesignerController::class, 'update'])->name('update');
        Route::post('/api/versions/{version}/publish', [DesignerController::class, 'publish'])->name('publish');
        Route::post('/api/versions/{version}/clone', [DesignerController::class, 'clone'])->name('clone');
        Route::post('/api/versions/{version}/preview', [DesignerController::class, 'preview'])->name('preview');
        Route::post('/api/assets', [DesignerController::class, 'upload'])->name('upload');
        Route::get('/api/assets/catalog', [DesignerController::class, 'assetCatalog'])->name('asset-catalog');
        Route::get('/api/assets', [DesignerController::class, 'asset'])->name('asset');
    });
