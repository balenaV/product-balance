<?php

use App\Http\Controllers\ProductBalanceDraftController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/balances/{balance}/draft', [ProductBalanceDraftController::class, 'show'])
    ->name('balances.draft.show');

Route::post('/balances/{balance}/draft/items', [ProductBalanceDraftController::class, 'storeItem'])
    ->name('balances.draft.items.store');

Route::delete('/balances/{balance}/draft/items/{product}/{variation}', [ProductBalanceDraftController::class, 'destroyItem'])
    ->name('balances.draft.items.destroy');
