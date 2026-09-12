<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // ルートパラメータから対象の書籍IDを取得（自身のIDをユニークチェックから除外するため）
        $bookId = $this->route('book');

        return [
            'title' => ['required', 'string', 'max:255'],
            'author' => ['required', 'string', 'max:255'],
            // 自分のIDを除外してISBNの重複をチェック
            'isbn' => ['required', 'string', 'size:13', 'unique:books,isbn,' . $bookId],
            'published_date' => ['required', 'date'],
            'genre_id' => ['required', 'exists:genres,id'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}