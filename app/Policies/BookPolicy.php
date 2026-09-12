<?php

namespace App\Policies;

use App\Models\Book;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BookPolicy
{
    /**
     * すべてのモデルの閲覧権限があるかどうかを決定する
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * 特定のモデルの閲覧権限があるかどうかを決定する
     */
    public function view(User $user, Book $book): bool
    {
        return true;
    }

    /**
     * モデルの作成権限があるかどうかを決定する
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * モデルの更新（編集）権限があるかどうかを決定する
     */
    public function update(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * モデルの削除権限があるかどうかを決定する
     */
    public function delete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * モデルの復元権限があるかどうかを決定する
     */
    public function restore(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }

    /**
     * モデルの完全削除権限があるかどうかを決定する
     */
    public function forceDelete(User $user, Book $book): bool
    {
        return $user->id === $book->user_id;
    }
}