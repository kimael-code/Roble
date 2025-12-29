<?php

namespace App\Http\Controllers\Security;

use App\Actions\Security\CreateUser;
use App\Actions\Security\DisableUser;
use App\Actions\Security\EnableUser;
use App\Actions\Security\ManuallyActivateUser;
use App\Actions\Security\ResendActivation;
use App\Actions\Security\ResetPassword;
use App\Actions\Security\UpdateUser;
use App\Http\Controllers\Controller;
use App\InertiaProps\Security\UserIndexProps;
use App\InertiaProps\Security\UserShowProps;
use App\InertiaProps\Security\UserCreateProps;
use App\InertiaProps\Security\UserEditProps;
use App\Http\Requests\Security\StoreUserRequest;
use App\Http\Requests\Security\UpdateUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(UserIndexProps $props)
    {
        Gate::authorize('viewAny', User::class);

        return Inertia::render('security/users/Index', $props->toArray());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(UserCreateProps $props)
    {
        Gate::authorize('create', User::class);

        return Inertia::render('security/users/Create', $props->toArray());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request, CreateUser $createUser)
    {
        $createUser($request->validated());

        return redirect(route('users.index'));
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user, UserShowProps $props)
    {
        Gate::authorize('view', $user);

        return Inertia::render('security/users/Show', $props->toArray($user));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user, UserEditProps $props)
    {
        Gate::authorize('update', $user);

        return Inertia::render('security/users/Edit', $props->toArray($user));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user, UpdateUser $updateUser)
    {
        $updateUser($user, $request->validated());

        return redirect(route('users.index'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $user->delete();

        return redirect(route('users.index'));
    }

    public function forceDestroy(User $user)
    {
        Gate::authorize('forceDelete', $user);

        $user->forceDelete();

        return redirect(route('users.index'));
    }

    public function restore(User $user)
    {
        Gate::authorize('restore', $user);

        $user->restore();

        return redirect()->back();
    }

    public function enable(User $user, EnableUser $enableUser)
    {
        Gate::authorize('enable', $user);

        $enableUser($user);

        return redirect()->back();
    }

    public function disable(User $user, DisableUser $disableUser)
    {
        Gate::authorize('disable', $user);

        $disableUser($user);

        return redirect()->back();
    }

    public function resetPassword(User $user, ResetPassword $resetPassword)
    {
        Gate::authorize('resetPassword', $user);

        return redirect()->back()->with('message', $resetPassword($user));
    }

    public function resendActivation(User $user, ResendActivation $resendActivation)
    {
        Gate::authorize('resendActivation', $user);

        $resendActivation($user);

        return redirect()->back();
    }

    public function manuallyActivate(User $user, ManuallyActivateUser $manuallyActivate)
    {
        Gate::authorize('manuallyActivate', $user);

        $result = $manuallyActivate($user);

        return redirect()->back()->with('manualActivation', $result);
    }
}
