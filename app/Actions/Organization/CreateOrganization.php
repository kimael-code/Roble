<?php

namespace App\Actions\Organization;

use App\Models\Organization\Organization;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Crea una nueva organización en el sistema.
 * 
 * LÓGICA DE NEGOCIO IMPORTANTE:
 * Este sistema está diseñado para una sola organización activa a la vez.
 * Al crear una nueva organización, se deshabilitan automáticamente todas
 * las organizaciones anteriores para mantener un histórico de cambios
 * de razón social (ej: cambio de nombre o RIF de la institución).
 * 
 * No se eliminan las organizaciones anteriores por razones de auditoría
 * e historial de datos.
 */
class CreateOrganization
{
    public function __invoke(array $inputs): Organization
    {
        $logoPath = null;

        try
        {
            return DB::transaction(function () use ($inputs, &$logoPath)
            {
                // Deshabilitar todas las organizaciones existentes
                $this->disablePreviousOrganizations();

                // Guardar el logo
                $logoPath = $this->storeLogo($inputs['logo_path']);

                // Crear la nueva organización
                $organization = new Organization($inputs);
                $organization->logo_path = $logoPath;
                $organization->save();

                return $organization;
            });
        }
        catch (\Throwable $th)
        {
            // Si la transacción falla, eliminar el logo subido
            if ($logoPath)
            {
                Storage::disk('public')->delete($logoPath);
            }
            throw $th;
        }
    }

    /**
     * Deshabilita todas las organizaciones existentes.
     * 
     * Esto mantiene el histórico de organizaciones anteriores
     * cuando la institución cambia su razón social.
     */
    private function disablePreviousOrganizations(): void
    {
        Organization::query()->update(['disabled_at' => now()]);
    }

    /**
     * Almacena el logo de la organización.
     */
    private function storeLogo(UploadedFile $file): string
    {
        return Storage::disk('public')->putFile('logos', $file);
    }
}
