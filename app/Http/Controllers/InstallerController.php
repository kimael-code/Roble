<?php

namespace App\Http\Controllers;

use App\Http\Requests\Installer\StoreSuperuserRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Response;

class InstallerController extends Controller
{
    public function index(): Response
    {
        if (Storage::disk('local')->exists('.app_installed'))
        {
            abort(403, 'El sistema ya se encuentra instalado.');
        }

        return inertia('installer/Index');
    }

    public function wizard(Request $request): Response
    {
        return inertia('installer/Wizard');
    }

    public function store(StoreSuperuserRequest $request): RedirectResponse
    {
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->remember_token = Str::random(60);
        $user->is_password_set = true;
        $user->save();
        $user->assignRole(1);

        Storage::disk('local')->put('.app_installed', '');

        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
