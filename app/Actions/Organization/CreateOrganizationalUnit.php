<?php

namespace App\Actions\Organization;

use App\Models\Organization\OrganizationalUnit;

class CreateOrganizationalUnit
{
    public function __invoke(array $inputs): OrganizationalUnit
    {
        $ou = new OrganizationalUnit([
            'name' => $inputs['name'],
            'code' => $inputs['code'],
            'acronym' => $inputs['acronym'],
            'floor' => $inputs['floor'],
        ]);

        if (isset($inputs['organizational_unit_id']))
        {
            $ou->organizationalUnit()->associate($inputs['organizational_unit_id']);
        }

        $ou->organization()->associate($inputs['organization_id']);
        $ou->save();

        return $ou;
    }
}
