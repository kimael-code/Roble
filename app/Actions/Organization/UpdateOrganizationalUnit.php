<?php

namespace App\Actions\Organization;

use App\Models\Organization\OrganizationalUnit;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationalUnit
{
    public function __invoke(array $inputs, OrganizationalUnit $organizationalUnit): OrganizationalUnit
    {
        DB::transaction(function () use ($organizationalUnit, $inputs)
        {
            $organizationalUnit->code = $inputs['code'];
            $organizationalUnit->name = $inputs['name'];
            $organizationalUnit->acronym = $inputs['acronym'];
            $organizationalUnit->floor = $inputs['floor'];
            $organizationalUnit->organizationalUnit()->associate($inputs['organizational_unit_id']);

            // Handle activation/deactivation
            if (isset($inputs['disabled']))
            {
                $organizationalUnit->disabled_at = $inputs['disabled'] ? now() : null;
            }

            $organizationalUnit->save();
        });

        return $organizationalUnit;
    }
}
