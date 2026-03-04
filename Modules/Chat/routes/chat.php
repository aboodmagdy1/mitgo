<?php

use App\Http\Middleware\AppLocale;
use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;
Route::group(['prefix' => 'conversations', 'as' => 'conversations.', 'middleware' => ['auth:sanctum,verified',AppLocale::class]], function () {

    // Route::get('/', [ChatController::class, 'getConversations'])->name('index');
    Route::post('/', [ChatController::class, 'startConversation'])->name('start-conversation');
    Route::get('/{id}/messages', [ChatController::class, 'getConversationMessages'])->name('conversation.messages');
    Route::post('/send-message', [ChatController::class, 'sendMessage'])->name('conversation.messages.send');

});







