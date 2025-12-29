<?php

namespace App\Actions\Security;

use App\Models\User;

class BatchDeleteUser
{
    public function __invoke(array $ids): array
    {
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

        return $this->buildMessage($deleteCount, $nonDeleteCount, $nonDeleteReasons);
    }

    private function buildMessage(int $successCount, int $failCount, string $reasons): array
    {
        $msg = [
            'content' => $successCount === 1
                ? "$successCount registro eliminado"
                : "$successCount registros eliminados",
            'title' => '¡PROCESADO!',
            'type' => 'success',
        ];

        if ($failCount === 1)
        {
            $msg['content'] .= ". $failCount registro NO eliminado. Causa/s: $reasons";
            $msg['type'] = 'warning';
        }
        elseif ($failCount > 1)
        {
            $msg['content'] .= ". $failCount registros NO eliminados. Causa/s: $reasons";
            $msg['type'] = 'warning';
        }

        $msg['content'] .= '.';

        return $msg;
    }
}
