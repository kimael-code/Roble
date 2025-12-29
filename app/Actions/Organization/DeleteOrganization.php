<?php

namespace App\Actions\Organization;

use App\Models\Organization\Organization;
use Illuminate\Support\Facades\Storage;

class DeleteOrganization
{
    public function __invoke(Organization $organization): void
    {
        $logoPath = $organization->logo_path;

        $organization->delete();

        if ($logoPath)
        {
            Storage::disk('public')->delete($logoPath);
        }
    }
}
