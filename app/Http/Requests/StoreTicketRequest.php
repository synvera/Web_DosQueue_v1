<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'wave_id' => ['required', 'integer', 'exists:waves,id'],
            'current_token' => ['nullable', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'wave_id.required' => 'Gelombang antrian wajib dipilih.',
            'wave_id.integer' => 'Format ID gelombang tidak valid.',
            'wave_id.exists' => 'Gelombang yang dipilih tidak ditemukan.',
        ];
    }
}