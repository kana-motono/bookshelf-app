<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Genre;
use App\Http\Requests\BookRequest; // ← 1. ここを修正
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧画面
     */
    public function index(): View
    {
        // 登録されている書籍の一覧をページネーション付きで取得
        $books = Book::with('genres')->paginate(10);

        return view('books.index', compact('books'));
    }

    /**
     * 書籍登録画面
     */
    public function create(): View
    {
        // ジャンル一覧を取得してビューに渡す
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 新規書籍を登録する
     */
    public function store(BookRequest $request) // ← 2. 引数を BookRequest に変更
    {
        // 3. バリデーション済みのデータで書籍を作成し、ログインユーザーIDを紐付ける
        $book = Book::create(array_merge(
            $request->validated(),
            ['user_id' => auth()->id()]
        ));

        // もしジャンルが選択されていたら紐付けも保存する
        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        }

        // 登録完了後は、その書籍の詳細画面へリダイレクトする
        return redirect()->route('books.show', $book);
    }

    /**
     * 書籍詳細画面
     */
    public function show(Book $book): View
    {
        // レビューとその投稿者、およびジャンルを一緒にロードする
        $book->load('genres', 'reviews.user');

        return view('books.show', compact('book'));
    }

/**
     * 削除処理
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book); // 認可チェック

        $book->delete();

        return redirect()->route('books.index')->with('success', '書籍を削除しました。');
    }

    /**
     * 書籍編集画面を表示する
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book); // 認可チェック

        $genres = Genre::all();
        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 更新処理
     */
    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book); // 認可チェック

        $book->update($request->validated());

        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        } else {
            $book->genres()->detach();
        }

        return redirect()->route('books.show', $book)->with('success', '書籍を更新しました。');
    }


}