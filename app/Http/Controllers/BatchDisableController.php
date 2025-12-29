<?php

namespace App\Http\Controllers;

use App\Actions\Security\BatchDisableUser;
use Illuminate\Http\Request;

class BatchDisableController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $resource)
    {
        switch ($resource)
        {
            case 'users':
                $result = BatchDisableUser::execute($request->all());
                return redirect(route('users.index'))->with('message', $result);

            default:
                # code...
                break;
        }
    }
}
