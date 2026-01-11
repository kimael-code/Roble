<?php

namespace App\Http\Controllers;

use App\Actions\SuInstaller\CreateNewSuperuser;
use App\Http\Requests\Installer\StoreSuperuserRequest;
use App\Repositories\JsonEmployeeRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SuInstallerController extends Controller
{
    public function index(): Response
    {
        if (!Auth::guest())
        {
            Auth::logout();
        }

        return inertia('su-installer/Index');
    }

    public function wizard(Request $request): Response
    {
        $idCard = $request->input('id_card');

        return inertia('su-installer/Wizard', [
            'employee' => Inertia::optional(fn() => new JsonEmployeeRepository()->find($idCard)),
        ]);
    }

    public function store(StoreSuperuserRequest $request): RedirectResponse
    {
        $user = CreateNewSuperuser::run($request->validated());

        $request->session()->flush();
        event(new Registered($user));

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard')->with('message', [
            'type' => 'success',
            'content' => 'Superusuario '
                . Str::of($user?->person?->names ?? $user->name)
                    ->words(1, '.')
                    ->title(),
            'title' => '¡Bienvenido!',
        ]);
    }
}
