<?php

namespace App\Dto;

class EmployeeDto
{
    public function __construct(
        public readonly string $company_code,
        public readonly string $nationality,
        public readonly string $id_card,
        public readonly ?string $rif,
        public readonly string $names,
        public readonly string $surnames,
        public readonly string $staff_type_code,
        public readonly string $org_unit_code,
        public readonly string $position,
        public readonly ?string $email,
        public readonly ?string $phone_ext,
        public readonly string $staff_type_name,
        public readonly string $org_unit_name,
    ) {}
}