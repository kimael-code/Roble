<?php

namespace App\Actions\Security;

use App\Models\User;

class BatchDeleteUser
{
    public static function execute(array $ids): array
    {
        $msg = [
            'message' => 'registros eliminados.',
            'title' => __('PROCESSED!'),
            'type' => 'success',
        ];
        $deleteCount = 0;
        $nonDeleteCount = 0;
        $nonDeleteReasons = '';

        foreach ($ids as $id => $isSelected)
        {
            if (!$isSelected)
            {
                continue;
            }

            $user = User::find($id);

            if ($user->is(auth()->user()))
            {
                $nonDeleteCount += 1;
                $nonDeleteReasons .= $nonDeleteCount > 1 ? ', ' : '';
                $nonDeleteReasons .= 'usted no puede eliminar su propia cuenta';
            }
            elseif (!$user->disabled_at && $user->hasRole('Superusuario'))
            {
                $nonDeleteCount += 1;
                $nonDeleteReasons .= $nonDeleteCount > 1 ? ', ' : '';
                $nonDeleteReasons .= "{$user->name} es un Superusuario activo";
            }
            else
            {
                $user->delete();
                $deleteCount += 1;
            }
        }

        if ($deleteCount === 1)
        {
            $msg['message'] = "$deleteCount registro eliminado";
            $msg['type'] = 'success';
        }
        else
        {
            $msg['message'] = "$deleteCount registros eliminados";
            $msg['type'] = 'success';
        }

        if ($nonDeleteCount === 1)
        {
            $msg['message'] .= ". $nonDeleteCount registro NO eliminado. Causa/s: $nonDeleteReasons";
            $msg['type'] = 'warning';
        }
        elseif ($nonDeleteCount > 1)
        {
            $msg['message'] .= ". $nonDeleteCount registros NO eliminados. Causa/s: $nonDeleteReasons";
            $msg['type'] = 'warning';
        }

        $msg['message'] .= '.';

        return $msg;
    }
}
