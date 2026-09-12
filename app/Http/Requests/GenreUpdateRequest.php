<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenreUpdateRequest extends FormRequest
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
        // ルートパラメータから対象のジャンルIDを取得（自身のIDをユニークチェックから除外）
        $genreId = $this->route('genre');

        return [
            'name' => ['required', 'string', 'max:255', 'unique:genres,name,' . $genreId],
        ];
    }
}
