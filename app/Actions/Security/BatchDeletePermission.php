<?php

namespace App\Actions\Security;

use App\Models\Security\Permission;

class BatchDeletePermission
{
    public function __invoke(array $ids): array
    {
        $deleteCount = 0;
        $nonDeleteCount = 0;

        foreach ($ids as $id => $isSelected)
        {
            if (!$isSelected)
            {
                continue;
            }

            $permission = Permission::find($id);

            if ($permission->users()->exists() || $permission->roles()->exists())
            {
                $nonDeleteCount += 1;
            }
            else
            {
                $permission->delete();
                $deleteCount += 1;
            }
        }

        return $this->buildMessage($deleteCount, $nonDeleteCount);
    }

    private function buildMessage(int $successCount, int $failCount): array
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
            $msg['content'] .= ". $failCount registro NO eliminado. Causa: asociación de registros";
            $msg['type'] = 'warning';
        }
        elseif ($failCount > 1)
        {
            $msg['content'] .= ". $failCount registros NO eliminados. Causa: asociación de registros";
            $msg['type'] = 'warning';
        }

        $msg['content'] .= '.';

        return $msg;
    }
}
