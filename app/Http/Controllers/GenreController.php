<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\Request;

class GenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    public function create()
    {
        return view('genres.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:genres,name'],
        ], [
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'このジャンル名はすでに登録されています。',
        ]);

        Genre::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを登録しました。');
    }

    public function show(Genre $genre)
    {
        $genre->load('books');

        return view('genres.show', compact('genre'));
    }

    public function edit(Genre $genre)
    {
        if ($genre->user_id !== auth()->id()) {
            abort(403);
        }

        return view('genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre)
    {
        if ($genre->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:genres,name,' . $genre->id,
            ],
        ], [
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'このジャンル名はすでに登録されています。',
        ]);

        $genre->update([
            'name' => $validated['name'],
        ]);

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを更新しました。');
    }

    public function destroy(Request $request, Genre $genre)
    {
        if ($genre->user_id !== $request->user()->id) {
            return redirect()
                ->route('genres.index')
                ->with('error', 'このジャンルを削除できるのは登録者本人のみです。');
        }

        if ($genre->books()->exists()) {
            return redirect()
                ->route('genres.index')
                ->with('error', '書籍に使用されているジャンルは削除できません。');
        }

        $genre->delete();

        return redirect()
            ->route('genres.index')
            ->with('success', 'ジャンルを削除しました。');
    }
}