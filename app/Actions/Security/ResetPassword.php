<?php

namespace App\Actions\Security;

use App\Models\Monitoring\ActivityLog;
use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetPassword
{
    public function __construct(
        private ActivityLogger $logger
    ) {
    }

    public function __invoke(User $user): array
    {
        $token = Str::random(64);
        $hashedToken = Hash::make($token);

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        DB::table('password_reset_tokens')->insert([
            'email' => $user->email,
            'token' => $hashedToken,
            'created_at' => now(),
        ]);

        ($this->logger)(
            ActivityLog::LOG_NAMES['users'],
            $user,
            ActivityLog::EVENT_NAMES['password'],
            "inició restablecimiento de contraseña para [:subject.name] [:subject.email]"
        );

        return [
            'route' => route('password.reset', ['token' => $token, 'email' => $user->email,]),
            'expiresAt' => $this->formatExpiryDate(now()->addMinutes(config('auth.passwords.users.expire', 60))),
        ];
    }

    private function formatExpiryDate(Carbon $date): string
    {
        // Ej: "en 59 minutos (17/11/2025 15:00:00)"
        $relative = $date->locale(config('app.locale'))->diffForHumans([
            'parts' => 1, // Solo una parte: "en 1 hora", no "en 1 hora y 5 minutos"
            'syntax' => Carbon::DIFF_ABSOLUTE, // "en 1 hora" en lugar de "dentro de 1 hora"
        ]);

        $fullDate = $date->format('d/m/Y H:i:s');
        return "{$relative} ({$fullDate})";
    }
}
