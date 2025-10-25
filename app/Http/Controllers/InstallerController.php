<?php

namespace App\Http\Controllers;

use App\Http\Requests\Installer\StoreSuperuserRequest;
use App\Models\User;
use App\Repositories\EmployeeRepository;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InstallerController extends Controller
{
    public function index(): Response
    {
        if (!Auth::guest())
        {
            Auth::logout();
        }

        return inertia('installer/Index');
    }

    public function wizard(Request $request): Response
    {
        $idCard = $request->input('id_card');

        return inertia('installer/Wizard', [
            'employee' => Inertia::optional(fn() => new EmployeeRepository()->find($idCard)[0] ?? null),
        ]);
    }

    public function store(StoreSuperuserRequest $request): RedirectResponse
    {
        $user = new User();

        DB::transaction(function () use (&$user, $request)
        {
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->remember_token = Str::random(60);
            $user->is_password_set = true;
            $user->save();
            $user->assignRole(1);
        });

        session()->flush();
        event(new Registered($user));

        Auth::login($user);

        return to_route('dashboard');
    }
}
