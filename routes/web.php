<?php

use App\Http\Controllers\InvitationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/invitation/{token}', [InvitationController::class, 'show'])->name('invitation.accept');
Route::post('/invitation/{token}/register', [InvitationController::class, 'register'])->name('invitation.register');
