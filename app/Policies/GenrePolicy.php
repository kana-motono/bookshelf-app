<?php

namespace App\Policies;

use App\Models\Genre;
use App\Models\User;

class GenrePolicy
{
    public function update(User $user, Genre $genre): bool
    {
        return $genre->user_id !== null
            && $user->id === $genre->user_id;
    }

    public function delete(User $user, Genre $genre): bool
    {
        return $genre->user_id !== null
            && $user->id === $genre->user_id;
    }
}