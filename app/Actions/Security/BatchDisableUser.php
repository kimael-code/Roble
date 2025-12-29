<?php

namespace App\Actions\Security;

use App\Models\User;

class BatchDisableUser
{
    public function __construct(
        private DisableUser $disableUser
    ) {
    }

    public function __invoke(array $ids): array
    {
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
                ($this->disableUser)($user);
                $deactivateCount += 1;
            }
        }

        return $this->buildMessage($deactivateCount, $nonDeactivateCount, $nonDeactivateReasons);
    }

    private function buildMessage(int $successCount, int $failCount, string $reasons): array
    {
        $msg = [
            'content' => $successCount === 1
                ? "$successCount registro desactivado"
                : "$successCount registros desactivados",
            'title' => '¡PROCESADO!',
            'type' => 'success',
        ];

        if ($failCount === 1)
        {
            $msg['content'] .= ". $failCount registro NO desactivado. Causa/s: $reasons";
            $msg['type'] = 'warning';
        }
        elseif ($failCount > 1)
        {
            $msg['content'] .= ". $failCount registros NO desactivados. Causa/s: $reasons";
            $msg['type'] = 'warning';
        }

        $msg['content'] .= '.';

        return $msg;
    }
}
