<?php

namespace App\Actions\Organization;

use App\Models\Organization\OrganizationalUnit;

class BatchDeleteOrganizationalUnit
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

            $organizationalUnit = OrganizationalUnit::find($id);

            if (
                !$organizationalUnit->disabled_at
                || $organizationalUnit->organizationalUnits()->exists()
                || $organizationalUnit->users()->exists()
            )
            {
                $nonDeleteCount += 1;
            }
            else
            {
                $organizationalUnit->delete();
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
            $msg['content'] .= ". $failCount registro NO eliminado. Causa/s: asociación de registros";
            $msg['type'] = 'warning';
        }
        elseif ($failCount > 1)
        {
            $msg['content'] .= ". $failCount registros NO eliminados. Causa/s: asociación de registros";
            $msg['type'] = 'warning';
        }

        $msg['content'] .= '.';

        return $msg;
    }
}
