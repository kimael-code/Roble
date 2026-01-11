<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class UserActivationController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_if($user->is_active, 403, 'Este usuario ya ha sido activado.');

        return Inertia::render('security/users/Activate', [
            'user' => $user->load('person'),
            'formActionUrl' => $request->fullUrl(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        abort_if($user->is_active, 403, 'Este usuario ya ha sido activado.');

        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        // Autenticar al usuario antes de persistir los cambios
        // Esto permite que el UserObserver tenga acceso a auth()->user()
        // y mejora la UX al ingresar directamente al sistema
        Auth::login($user);

        $user->password = $request->password;
        $user->is_active = true;
        $user->email_verified_at = now();
        $user->save();

        return redirect()->route('dashboard')->with(
            'message',
            [
                'title' => '¡CUENTA ACTIVADA!',
                'content' => 'Tu cuenta ha sido activada exitosamente. Bienvenido al sistema.',
                'type' => 'success',
            ]
        );
    }
}