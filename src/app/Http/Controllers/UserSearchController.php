<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserSearchController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'query' => ['required', 'string', 'min:2', 'max:255'],
        ]);

        $term = mb_strtolower(trim($data['query']));

        $users = User::query()
            ->select(['id', 'nickname', 'email', 'avatar_path', 'hide_email'])
            ->where('id', '!=', $request->user()->id)
            ->where(function ($query) use ($term) {
                $query->whereRaw('LOWER(nickname) LIKE ?', ["%{$term}%"])
                    ->orWhere(function ($emailQuery) use ($term) {
                        $emailQuery->where('hide_email', false)
                            ->whereRaw('LOWER(email) LIKE ?', ["%{$term}%"]);
                    });
            })
            ->orderByRaw('LOWER(nickname)')
            ->limit(20)
            ->get();

        $payload = $users->map(function (User $user) {
            return [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar_url' => $user->avatar_url,
                'email' => $user->hide_email ? null : $user->email,
            ];
        });

        return response()->json($payload);
    }
}
