<?php

namespace App\Actions\Security;

use App\Models\Security\Role;

class BatchDeleteRole
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

            $role = Role::find($id);

            if ($role->id === 1)
            {
                $nonDeleteCount += 1;
                $nonDeleteReasons .= $nonDeleteCount > 1 ? ', ' : '';
                $nonDeleteReasons .= 'Superusuario no es eliminable';
            }
            elseif ($role->permissions()->exists() || $role->users()->exists())
            {
                $nonDeleteCount += 1;
                $nonDeleteReasons .= $nonDeleteCount > 1 ? ', ' : '';
                $nonDeleteReasons .= 'asociación de registros';
            }
            else
            {
                $role->delete();
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
