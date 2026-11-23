<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

// Public invoice link shared with clients (PDF)
Route::get('/i/{token}', [Api\PublicInvoiceController::class, 'show'])->name('invoice.public');

Route::prefix('api')->group(function () {
    Route::post('login', [Api\AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth')->group(function () {
        Route::post('logout', [Api\AuthController::class, 'logout']);
        Route::get('me', [Api\AuthController::class, 'me']);
        Route::put('profile', [Api\AuthController::class, 'profile']);
        Route::put('password', [Api\AuthController::class, 'password']);

        Route::get('dashboard', [Api\DashboardController::class, 'index']);

        Route::apiResource('clients', Api\ClientController::class);
        Route::apiResource('catalog', Api\CatalogController::class)->except('show');

        Route::get('projects', [Api\ProjectController::class, 'index']);
        Route::post('projects', [Api\ProjectController::class, 'store']);
        Route::get('projects/{project}', [Api\ProjectController::class, 'show']);
        Route::put('projects/{project}', [Api\ProjectController::class, 'update']);
        Route::delete('projects/{project}', [Api\ProjectController::class, 'destroy']);
        Route::post('projects/{project}/close', [Api\ProjectController::class, 'close']);
        Route::post('projects/{project}/reopen', [Api\ProjectController::class, 'reopen']);
        Route::post('projects/{project}/bill-period', [Api\ProjectController::class, 'billPeriod']);

        Route::post('projects/{project}/items', [Api\ItemController::class, 'store']);
        Route::put('items/{item}', [Api\ItemController::class, 'update']);
        Route::delete('items/{item}', [Api\ItemController::class, 'destroy']);

        Route::post('projects/{project}/payments', [Api\PaymentController::class, 'store']);
        Route::post('payments/{payment}', [Api\PaymentController::class, 'update']); // POST so a receipt file can come along
        Route::delete('payments/{payment}', [Api\PaymentController::class, 'destroy']);
        Route::get('payments/{payment}/receipt', [Api\PaymentController::class, 'receipt']);

        Route::get('expenses', [Api\ExpenseController::class, 'index']);
        Route::post('expenses', [Api\ExpenseController::class, 'store']);
        Route::put('expenses/{expense}', [Api\ExpenseController::class, 'update']);
        Route::delete('expenses/{expense}', [Api\ExpenseController::class, 'destroy']);
        Route::post('expenses/{expense}/reimburse', [Api\ExpenseController::class, 'reimburse']);
        Route::delete('expenses/{expense}/reimburse', [Api\ExpenseController::class, 'undoReimburse']);

        Route::get('invoices', [Api\InvoiceController::class, 'index']);
        Route::get('projects/{project}/invoice-preview', [Api\InvoiceController::class, 'preview']);
        Route::post('projects/{project}/invoices', [Api\InvoiceController::class, 'store']);
        Route::delete('invoices/{invoice}', [Api\InvoiceController::class, 'destroy']);
        Route::get('invoices/{invoice}/pdf', [Api\InvoiceController::class, 'pdf']);
        Route::get('invoices/{invoice}/share', [Api\InvoiceController::class, 'share']);
        Route::post('invoices/{invoice}/whatsapp-sent', [Api\InvoiceController::class, 'whatsappSent']);
        Route::post('invoices/{invoice}/email', [Api\InvoiceController::class, 'email']);

        Route::get('partners', [Api\PartnerController::class, 'index']);
        Route::get('partners/{partner}', [Api\PartnerController::class, 'show']);
        Route::put('partners/{partner}', [Api\PartnerController::class, 'update']);
        Route::post('partners/{partner}/pay-all', [Api\PartnerController::class, 'payAll']);
        Route::post('shares/{share}/payouts', [Api\PartnerController::class, 'payout']);
        Route::delete('payouts/{payout}', [Api\PartnerController::class, 'destroyPayout']);
        Route::get('payouts/{payout}/voucher', [Api\PartnerController::class, 'voucher']);

        Route::get('account', [Api\AccountController::class, 'show']);
        Route::post('account/adjustments', [Api\AccountController::class, 'adjust']);
        Route::delete('account/adjustments/{adjustment}', [Api\AccountController::class, 'destroyAdjustment']);

        Route::get('renewals', [Api\ClientServiceController::class, 'index']);
        Route::post('renewals', [Api\ClientServiceController::class, 'store']);
        Route::put('renewals/{service}', [Api\ClientServiceController::class, 'update']);
        Route::delete('renewals/{service}', [Api\ClientServiceController::class, 'destroy']);
        Route::post('renewals/{service}/renew', [Api\ClientServiceController::class, 'renew']);

        Route::get('settings', [Api\SettingsController::class, 'show']);
        Route::put('settings', [Api\SettingsController::class, 'update']);
    });
});

// Backend only serves the API; the Vue app lives in /frontend
Route::get('/', fn () => response()->json(['app' => 'Rivoxa CRM API', 'status' => 'running']));
Route::get('/login', fn () => response()->json(['message' => 'Unauthenticated.'], 401))->name('login');
