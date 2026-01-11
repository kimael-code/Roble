<?php

namespace App\Http\Requests\Security;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->role?->id !== 1 && $this->user()->can('update roles');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                'doesnt_end_with:.',
                Rule::unique('roles')->ignore($this->role),
            ],
            'description' => ['required', 'string', 'max:255', 'lowercase', 'doesnt_end_with:.',],
            'guard_name' => ['required', 'string', 'max:255',],
            'permissions' => ['nullable', 'array',],
        ];

        // Solo validar la existencia de los permisos si el array no está vacío
        // esto evita que aparezca el mensaje de validación si permissions está vacío
        if (!empty($this->permissions))
        {
            $rules['permissions.*'] = ['exists:permissions,description'];
        }

        return $rules;
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
            'permissions' => 'Permisos',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $messages = [];

        // establecer este mensaje solo cuando permissions no esté vacío
        // esto evita que aparezca el mensaje de validación
        if (!empty($this->permissions))
        {
            $messages['permissions.*'] = 'Algunos permisos seleccionados no existen.';
        }

        return $messages;
    }
}
