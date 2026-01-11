<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $contacts = Contact::query()
            ->with('contact')
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($contacts);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'contact_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'email' => ['nullable', 'string', 'email'],
            'alias' => ['nullable', 'string', 'max:255'],
        ]);

        $user = $request->user();
        $contactUser = null;

        if (!empty($data['contact_user_id'])) {
            $contactUser = User::find($data['contact_user_id']);
        }

        if (!$contactUser && !empty($data['email'])) {
            $contactUser = User::where('email', $data['email'])
                ->where(function ($query) {
                    $query->where('hide_email', false)
                        ->orWhereNull('nickname');
                })
                ->first();
        }

        if (!$contactUser) {
            return response()->json(['message' => 'Контакт не найден.'], 404);
        }

        if ($contactUser->id === $user->id) {
            return response()->json(['message' => 'Нельзя добавить себя в контакты.'], 422);
        }

        $contact = Contact::firstOrCreate(
            [
                'user_id' => $user->id,
                'contact_user_id' => $contactUser->id,
            ],
            [
                'alias' => $data['alias'] ?? null,
            ]
        );

        if (!empty($data['alias']) && $contact->alias !== $data['alias']) {
            $contact->update(['alias' => $data['alias']]);
        }

        return response()->json($contact->load('contact'), 201);
    }

    public function destroy(Contact $contact, Request $request)
    {
        if ($contact->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $contact->delete();

        return response()->json(['status' => 'deleted']);
    }
}
