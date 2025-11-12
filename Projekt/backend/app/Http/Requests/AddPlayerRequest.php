<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AddPlayerRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            //
            'name' => [
                'required',
                'min:3',
                'max:20',
                //'unique:players,name,' . $this->player->id
                Rule::unique('players')->ignore($this->player)
            ],
            'age' =>['required','integer', 'min:3', 'max:150'],
            'points' =>['required','integer'],
            'address' =>['required']
        ];
    }
}
