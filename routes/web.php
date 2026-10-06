<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\PasswordResetController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('home');

Route::get('/reserver/{ticketType}', [OrderController::class, 'create'])->name('checkout');
Route::post('/reserver/{ticketType}', [OrderController::class, 'store'])->name('order.store');

Route::get('/paiement/{order:reference}', [PaymentController::class, 'show'])->name('payment.show');
Route::post('/paiement/{order:reference}/simuler', [PaymentController::class, 'simulate'])->name('payment.simulate');
Route::post('/paiement/{order:reference}/kkiapay/verifier', [PaymentController::class, 'verifyKkiapay'])
    ->middleware('throttle:10,1')
    ->name('payment.kkiapay.verify');
Route::get('/paiement/{order:reference}/confirmation', [PaymentController::class, 'confirmation'])->name('payment.confirmation');

Route::get('/connexion-admin', [AuthController::class, 'create'])->name('login');
Route::post('/connexion-admin', [AuthController::class, 'store'])->middleware('throttle:login')->name('admin.login.store');
Route::get('/mot-de-passe-oublie', [PasswordResetController::class, 'createLink'])->name('password.request');
Route::post('/mot-de-passe-oublie', [PasswordResetController::class, 'sendLink'])
    ->middleware('throttle:6,1')
    ->name('password.email');
Route::get('/reinitialiser-mot-de-passe/{token}', [PasswordResetController::class, 'showResetForm'])
    ->name('password.reset');
Route::post('/reinitialiser-mot-de-passe', [PasswordResetController::class, 'reset'])
    ->middleware('throttle:6,1')
    ->name('password.update');

Route::get('/billets/{order:reference}', [TicketController::class, 'show'])->name('tickets.show');
Route::get('/billets/{order:reference}/pdf', [TicketController::class, 'downloadOrder'])
    ->middleware('signed')->name('orders.tickets.download');
Route::get('/billet/{ticket}/pdf', [TicketController::class, 'download'])
    ->middleware('signed')->name('tickets.download');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/scan', [DashboardController::class, 'scan'])->name('admin.scan');
    Route::post('/capacites/{ticketType}', [DashboardController::class, 'updateCapacity'])->name('admin.capacity.update');
    Route::post('/scan', [DashboardController::class, 'check'])->name('admin.check');
    Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('admin.logout');
});
