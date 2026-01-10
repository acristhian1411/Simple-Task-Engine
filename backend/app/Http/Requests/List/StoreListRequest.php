<?php

namespace App\Http\Requests\List;

use Illuminate\Foundation\Http\FormRequest;

class StoreListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_id' => ['required','integer','exists:boards,id'],
            'title' => ['required','string','max:255'],
            'order' => ['nullable','integer','min:0'],
        ];
    }
}
