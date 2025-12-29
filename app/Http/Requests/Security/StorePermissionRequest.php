<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;

class StorePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create new permissions');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'lowercase', 'max:255', 'doesnt_end_with:.', 'unique:permissions',],
            'description' => ['required', 'string', 'lowercase', 'max:255', 'doesnt_end_with:.',],
            'guard_name' => ['required', 'string', 'lowercase', 'regex:/^(web)$/',],
            'set_menu'    => ['nullable', 'boolean',],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'Nombre',
            'description' => 'Descripción',
            'guard_name' => 'Autenticación',
            'set_menu' => 'Establece menú',
        ];
    }
}
