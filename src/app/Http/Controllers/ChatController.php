<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Contact;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->expectsJson()) {
            return Inertia::render('Chats/Index');
        }

        $chats = Chat::query()
            ->with(['users', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->whereHas('users', function ($query) use ($request) {
                $query->where('users.id', $request->user()->id);
            })
            ->orderBy('updated_at', 'desc')
            ->get();

        return response()->json($chats);
    }

    public function show(Chat $chat, Request $request)
    {
        if (!$chat->users()->where('users.id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json($chat->load(['users', 'messages.sender', 'messages.forwardedMessage']));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'is_group' => ['sometimes', 'boolean'],
            'title' => ['nullable', 'string', 'max:255'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $user = $request->user();
        $isGroup = (bool) ($data['is_group'] ?? false);

        if ($isGroup) {
            $userIds = collect($data['user_ids'] ?? [])
                ->push($user->id)
                ->unique()
                ->values()
                ->all();

            $contactIds = Contact::query()
                ->where('user_id', $user->id)
                ->whereIn('contact_user_id', $userIds)
                ->pluck('contact_user_id')
                ->toArray();

            $allowedIds = collect($contactIds)->push($user->id)->unique();
            $invalidIds = collect($userIds)->diff($allowedIds)->values();

            if ($invalidIds->isNotEmpty()) {
                return response()->json(['message' => 'Можно добавлять только пользователей из контактов.'], 422);
            }

            $chat = Chat::create([
                'title' => $data['title'] ?? 'Группа',
                'is_group' => true,
                'created_by' => $user->id,
            ]);

            $pivotData = collect($userIds)->mapWithKeys(function ($id) use ($user) {
                return [$id => ['role' => $id === $user->id ? 'owner' : 'member']];
            })->all();

            $chat->users()->attach($pivotData);

            return response()->json($chat->load('users'), 201);
        }

        if (empty($data['user_id'])) {
            return response()->json(['message' => 'Нужен пользователь для личного чата.'], 422);
        }

        $existingChat = Chat::query()
            ->where('is_group', false)
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->whereHas('users', function ($query) use ($data) {
                $query->where('users.id', $data['user_id']);
            })
            ->has('users', 2)
            ->first();

        if ($existingChat) {
            return response()->json($existingChat->load('users'));
        }

        $chat = Chat::create([
            'title' => null,
            'is_group' => false,
            'created_by' => $user->id,
        ]);

        $chat->users()->attach([
            $user->id => ['role' => 'owner'],
            $data['user_id'] => ['role' => 'member'],
        ]);

        return response()->json($chat->load('users'), 201);
    }

    public function updateNotifications(Chat $chat, Request $request)
    {
        if (! $chat->users()->where('users.id', $request->user()->id)->exists()) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $chat->users()->updateExistingPivot($request->user()->id, [
            'notifications_enabled' => $data['enabled'],
        ]);

        return response()->json([
            'chat_id' => $chat->id,
            'notifications_enabled' => $data['enabled'],
        ]);
    }
}
