<?php

namespace App\Actions\Organization;

use App\Models\Organization\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateOrganization
{
    public function __invoke(array $inputs, Organization $organization): Organization
    {
        $oldLogoPath = $organization->logo_path;
        $newLogoPath = null;

        try
        {
            // Verificar si se subió un nuevo logo
            if (isset($inputs['logo_path']) && $inputs['logo_path'] instanceof UploadedFile)
            {
                $newLogoPath = Storage::disk('public')->putFile('logos', $inputs['logo_path']);
            }

            // Actualizar datos de la organización
            $organization->rif = $inputs['rif'] ?? $organization->rif;
            $organization->name = $inputs['name'] ?? $organization->name;
            $organization->acronym = $inputs['acronym'] ?? $organization->acronym;
            $organization->address = $inputs['address'] ?? $organization->address;
            $organization->disabled_at = $inputs['disabled'] ? now() : null;

            // Actualizar logo solo si se subió uno nuevo
            if ($newLogoPath)
            {
                $organization->logo_path = $newLogoPath;
            }

            $logoHasChanged = $organization->isDirty('logo_path');

            $organization->save();

            // Eliminar logo anterior si cambió
            if ($logoHasChanged && $oldLogoPath)
            {
                Storage::disk('public')->delete($oldLogoPath);
            }

            return $organization;
        }
        catch (\Throwable $th)
        {
            // Si algo falla, eliminar el nuevo logo subido
            if ($newLogoPath)
            {
                Storage::disk('public')->delete($newLogoPath);
            }
            throw $th;
        }
    }
}
