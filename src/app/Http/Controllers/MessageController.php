<?php

namespace App\Http\Controllers;

use App\Events\MessageDeleted;
use App\Events\MessageSent;
use App\Events\MessageUpdated;
use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'chat_id' => ['required', 'integer', 'exists:chats,id'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $chat = $this->resolveChat($data['chat_id'], $request);

        if (!$chat) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $message->load(['sender']);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    public function update(Message $message, Request $request)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message->update([
            'body' => $data['body'],
            'edited_at' => now(),
        ]);

        $message->load(['sender']);

        broadcast(new MessageUpdated($message))->toOthers();

        return response()->json($message);
    }

    public function destroy(Message $message, Request $request)
    {
        if ($message->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $messageId = $message->id;
        $chatId = $message->chat_id;

        $message->delete();

        broadcast(new MessageDeleted($chatId, $messageId))->toOthers();

        return response()->json(['status' => 'deleted']);
    }

    public function forward(Request $request)
    {
        $data = $request->validate([
            'chat_id' => ['required', 'integer', 'exists:chats,id'],
            'message_id' => ['required', 'integer', 'exists:messages,id'],
        ]);

        $chat = $this->resolveChat($data['chat_id'], $request);

        if (!$chat) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $original = Message::with('sender')->findOrFail($data['message_id']);

        $message = Message::create([
            'chat_id' => $chat->id,
            'user_id' => $request->user()->id,
            'body' => $original->body,
            'forwarded_message_id' => $original->id,
        ]);

        $message->load(['sender', 'forwardedMessage']);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json($message, 201);
    }

    private function resolveChat(int $chatId, Request $request): ?Chat
    {
        $chat = Chat::find($chatId);

        if (!$chat) {
            return null;
        }

        $isMember = $chat->users()
            ->where('users.id', $request->user()->id)
            ->exists();

        return $isMember ? $chat : null;
    }
}
