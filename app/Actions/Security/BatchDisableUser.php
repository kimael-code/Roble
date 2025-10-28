<?php

namespace App\Actions\Security;

use App\Models\User;

class BatchDisableUser
{
    public static function execute(array $ids): array
    {
        $msg = [
            'message' => 'registros activados.',
            'title' => '¡PROCESADO!',
            'type' => 'success',
        ];
        $deactivateCount = 0;
        $nonDeactivateCount = 0;
        $nonDeactivateReasons = '';

        foreach ($ids as $id => $isSelected)
        {
            if (!$isSelected)
            {
                continue;
            }

            $user = User::find($id);

            if ($user->is(auth()->user()))
            {
                $nonDeactivateCount += 1;
                $nonDeactivateReasons .= $nonDeactivateCount > 1 ? ', ' : '';
                $nonDeactivateReasons .= 'usted no puede desactivar su propia cuenta';
            }
            elseif ($user->disabled_at)
            {
                $nonDeactivateCount += 1;
                $nonDeactivateReasons .= $nonDeactivateCount > 1 ? ', ' : '';
                $nonDeactivateReasons .= "{$user->name} ya está desactivado";
            }
            else
            {
                DisableUser::handle($user);
                $deactivateCount += 1;
            }
        }

        if ($deactivateCount === 1)
        {
            $msg['message'] = "$deactivateCount registro desactivado";
            $msg['type'] = 'success';
        }
        else
        {
            $msg['message'] = "$deactivateCount registros desactivados";
            $msg['type'] = 'success';
        }

        if ($nonDeactivateCount === 1)
        {
            $msg['message'] .= ". $nonDeactivateCount registro NO desactivado. Causa/s: $nonDeactivateReasons";
            $msg['type'] = 'warning';
        }
        elseif ($nonDeactivateCount > 1)
        {
            $msg['message'] .= ". $nonDeactivateCount registros NO activados. Causa/s: $nonDeactivateReasons";
            $msg['type'] = 'warning';
        }

        $msg['message'] .= '.';

        return $msg;
    }
}
