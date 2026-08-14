<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;

class DashboardController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        $user = auth()->user();
        $user->loadMissing('assignedRoles');

        return redirect()->route($user->preferredDashboardRoute());
    }
}
