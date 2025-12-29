<?php

namespace App\Http\Requests\Installer;

use App\Contracts\EmployeeRepository;
use App\Models\User;
use App\Rules\ActiveEmployee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreSuperuserRequest extends FormRequest
{
    public function __construct(
        private EmployeeRepository $employeeRepository
    ) {
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return !auth()->hasUser();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'id_card' => ['required', 'numeric', 'integer', 'min:500000', 'max:99999999', new ActiveEmployee($this->employeeRepository)],
            'name' => ['required', 'string', 'lowercase', 'max:255', 'unique:' . User::class],
            'email' => ['required', 'email:spoof,filter', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'id_card' => 'N° de CI',
            'name' => 'Nombre de Usuario',
            'password' => 'Contraseña',
        ];
    }
}
