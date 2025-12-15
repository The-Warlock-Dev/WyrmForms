<?php

use YourUsername\FormBuilder\Http\Controllers\FormController;
use Illuminate\Support\Facades\Route;

// Admin routes
Route::middleware(config('form-builder.routes.middleware', ['web', 'auth']))
    ->prefix(config('form-builder.routes.prefix', 'admin/forms'))
    ->name(config('form-builder.routes.name_prefix', 'forms.'))
    ->group(function () {
        Route::get('/', [FormController::class, 'index'])->name('index');
        Route::get('/create', [FormController::class, 'create'])->name('create');
        Route::post('/', [FormController::class, 'store'])->name('store');
        Route::get('/{form}/edit', [FormController::class, 'edit'])->name('edit');
        Route::put('/{form}', [FormController::class, 'update'])->name('update');
        Route::delete('/{form}', [FormController::class, 'destroy'])->name('destroy');
        Route::get('/{form}/submissions', [FormController::class, 'submissions'])->name('submissions');
        Route::get('/{form}/statistics', [FormController::class, 'statistics'])->name('statistics');
        Route::get('/{form}/export', [FormController::class, 'export'])->name('export');
    });

// Public routes
Route::middleware(config('form-builder.public_routes.middleware', ['web']))
    ->prefix(config('form-builder.public_routes.prefix', 'forms'))
    ->group(function () {
        Route::get('/{slug}', [FormController::class, 'show'])->name('forms.show');
        Route::post('/{slug}', [FormController::class, 'submit'])->name('forms.submit');
    });
