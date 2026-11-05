<?php

declare(strict_types=1);

use App\Addons\RackList\Controllers\Admin\RackListController;
use App\Addons\RackList\Controllers\Admin\RackListMappingController;
use Illuminate\Support\Facades\Route;

Route::post('/settings', [RackListController::class, 'updateSettings'])->name('settings.update');
Route::post('/test', [RackListController::class, 'test'])->name('test');
Route::post('/sync', [RackListController::class, 'sync'])->name('sync');
Route::post('/reference/refresh', [RackListController::class, 'refreshReference'])->name('reference.refresh');

Route::post('/groups', [RackListMappingController::class, 'updateGroups'])->name('groups.update');
Route::get('/products/{product}', [RackListMappingController::class, 'editProduct'])->name('products.edit');
Route::post('/products/{product}', [RackListMappingController::class, 'updateProduct'])->name('products.update');
