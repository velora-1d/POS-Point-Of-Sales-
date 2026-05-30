<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $outlet = Outlet::query()->first() ?? Outlet::query()->create([
            'id' => (string) Str::uuid(),
            'name' => 'Outlet Default',
            'is_active' => true,
            'settings' => [],
        ]);

        $role = Role::query()
            ->where('outlet_id', $outlet->id)
            ->orderByRaw("case when type = 'owner' then 0 else 1 end")
            ->first()
            ?? Role::query()->create([
                'id' => (string) Str::uuid(),
                'outlet_id' => $outlet->id,
                'name' => 'Owner',
                'type' => 'owner',
                'is_active' => true,
            ]);

        $user = User::create([
            'outlet_id' => $outlet->id,
            'role_id' => $role->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => null,
            'password_hash' => Hash::make($request->password),
            'approval_pin' => null,
            'is_active' => true,
            'join_date' => now(),
            'email_verified_at' => null,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
