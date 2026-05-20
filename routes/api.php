<?php

use App\Http\Controllers\Api\AIChatController;
use Illuminate\Support\Facades\Route;

Route::post('/ai/chat', [AIChatController::class, 'chat']);
