<?php

namespace App\Http\Requests\List;

use Illuminate\Foundation\Http\FormRequest;

class UpdateListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'board_id' => ['sometimes','required','integer','exists:boards,id'],
            'title' => ['sometimes','required','string','max:255'],
            'order' => ['sometimes','nullable','integer','min:0'],
        ];
    }
}
