<?php

namespace App\Actions\Security;

use App\Models\User;

class BatchEnableUser
{
    public function __construct(
        private EnableUser $enableUser
    ) {
    }

    public function __invoke(array $ids): array
    {
        $activateCount = 0;
        $nonActivateCount = 0;
        $nonActivateReasons = '';

        foreach ($ids as $id => $isSelected)
        {
            if (!$isSelected)
            {
                continue;
            }

            $user = User::withTrashed()->find($id);

            if ($user->is(auth()->user()))
            {
                $nonActivateCount += 1;
                $nonActivateReasons .= $nonActivateCount > 1 ? ', ' : '';
                $nonActivateReasons .= 'usted no puede activar su propia cuenta';
            }
            elseif (!$user->disabled_at && !$user->deleted_at)
            {
                $nonActivateCount += 1;
                $nonActivateReasons .= $nonActivateCount > 1 ? ', ' : '';
                $nonActivateReasons .= "{$user->name} ya está activado";
            }
            else
            {
                ($this->enableUser)($user);
                $activateCount += 1;
            }
        }

        return $this->buildMessage($activateCount, $nonActivateCount, $nonActivateReasons);
    }

    private function buildMessage(int $successCount, int $failCount, string $reasons): array
    {
        $msg = [
            'content' => $successCount === 1
                ? "$successCount registro activado"
                : "$successCount registros activados",
            'title' => '¡PROCESADO!',
            'type' => 'success',
        ];

        if ($failCount === 1)
        {
            $msg['content'] .= ". $failCount registro NO activado. Causa: $reasons";
            $msg['type'] = 'warning';
        }
        elseif ($failCount > 1)
        {
            $msg['content'] .= ". $failCount registros NO activados. Causa/s: $reasons";
            $msg['type'] = 'warning';
        }

        $msg['content'] .= '.';

        return $msg;
    }
}
