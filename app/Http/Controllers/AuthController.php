<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole(Auth::user());
        }

        return view('auth.login');
    }

    /**
     * Handle authentication attempt
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our clinical staff records.',
        ])->onlyInput('email');
    }

    /**
     * Log user out
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Redirect users to their respective portal based on role
     */
    protected function redirectByRole($user): RedirectResponse
    {
        return match ($user->role) {
            UserRole::Doctor       => redirect()->route('portal.doctor'),
            UserRole::Receptionist => redirect()->route('portal.reception'),
            UserRole::Patient      => redirect()->route('portal.patient'),
            UserRole::Admin        => redirect()->route('portal.reception'),
            default                => redirect()->route('home'),
        };
    }
}
