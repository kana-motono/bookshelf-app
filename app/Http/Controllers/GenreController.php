<?php

namespace App\Http\Controllers;

use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GenreController extends Controller
{
    public function index(): View
    {
        $genres = Genre::withCount('books')->get();

        return view('genres.index', compact('genres'));
    }

    public function create(): View
    {
        return view('genres.create');
    }

    public function store(Request $request): RedirectResponse
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

    public function show(Genre $genre): View
    {
        $genre->load('books');

        return view('genres.show', compact('genre'));
    }

    public function edit(Genre $genre): View
    {
        $this->authorize('update', $genre);

        return view('genres.edit', compact('genre'));
    }

    public function update(Request $request, Genre $genre): RedirectResponse
    {
        $this->authorize('update', $genre);

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

    public function destroy(Genre $genre): RedirectResponse
    {
        $this->authorize('delete', $genre);

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