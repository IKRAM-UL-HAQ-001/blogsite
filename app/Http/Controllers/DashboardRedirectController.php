<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardRedirectController extends Controller
{
    public function __invoke(): RedirectResponse|View
    {
        $user = auth()->user();

        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('editor')) {
            return view('dashboard.editor');
        }

        return view('dashboard.user');
    }
}
