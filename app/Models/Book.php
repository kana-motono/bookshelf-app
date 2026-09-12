<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ← これが必要

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date', // ← 'published_at' から変更
        'image_url',
        'description',
    ];

    /**
     * ジャンルとの多対多のリレーション
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class);
    }

    /**
     * この書籍に対するレビュー一覧
     */
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

}
