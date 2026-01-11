<?php

namespace App\Http\Requests\Security;

use App\Rules\RoleSuperuser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update users');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255', Rule::unique('users')->ignore($this->user),],
            'email' => ['required', 'email', 'max:255',],
            'is_external' => ['required', 'boolean',],
            'id_card' => ['required_if_accepted:is_external', 'nullable', 'string', 'max:8', 'regex:/^[0-9]*$/' ,Rule::unique('people')->ignore($this->input('id_card'), 'id_card'),],
            'names' => ['required_if_accepted:is_external', 'nullable', 'string', 'max:255',],
            'surnames' => ['required_if_accepted:is_external', 'nullable', 'string', 'max:255',],
            'phones' => ['nullable',],
            'emails' => ['nullable',],
            'position' => ['nullable', 'string', 'max:255'],
            'staff_type' => ['nullable', 'string', 'max:255'],
            'ou_names' => ['nullable', 'array',],
            'roles' => ['nullable', 'array', new RoleSuperuser,],
            'permissions' => ['nullable', 'array',],
        ];

        if (!empty($this->ou_names))
        {
            $rules['ou_names.*'] = ['exists:organizational_units,name'];
        }
        if (!empty($this->roles))
        {
            $rules['roles.*'] = ['exists:roles,name'];
        }
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
            'email' => 'Correo Electrónico',
            'is_external' => 'Usuario Externo',
            'id_card' => 'Nro. de CI',
            'names' => 'Nombres',
            'surnames' => 'Apellidos',
            'phones' => 'Teléfonos',
            'emails' => 'Correos Electrónicos',
            'position' => 'Cargo',
            'staff_type' => 'Personal',
            'ou_names' => 'Unidad Administrativa',
            'roles' => 'Roles',
            'permissions' => 'Permisos Directos',
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

        if (!empty($this->ou_names))
        {
            $messages['ou_names.*'] = 'Algunas unidades administrativas seleccionadas no existen.';
        }
        if (!empty($this->roles))
        {
            $messages['roles.*'] = 'Algunos roles seleccionados no existen.';
        }
        if (!empty($this->permissions))
        {
            $messages['permissions.*'] = 'Algunos permisos seleccionados no existen.';
        }

        return $messages;
    }
}
