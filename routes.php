<?php

use Illuminate\Support\Facades\Route;
use Davox\Company\Controllers\Invoices;


Route::group([
    'prefix'     => Backend::uri() . '/davox/company/invoices',
    'middleware' => Config::get('backend.middleware_group', 'web'),
    'as'         => 'davox.company.invoices.'
], function () {
    Route::get(
        'download-pdf/{invoice_id}/{file_id}',
        [Invoices::class, 'downloadGeneratedPdf']
    )->name('downloadGeneratedPdf')
        ->where(['invoice_id' => '[0-9]+', 'file_id' => '[0-9]+']);
});
