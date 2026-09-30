<?php

namespace App\Livewire\Auth;

use App\Models\Branch;
use App\Models\Gym;
use App\Models\User;
use App\Support\LoginFlow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** SaaS onboarding: a gym owner registers their gym and becomes its Gym Admin. */
#[Layout('layouts.guest'), Title('Create your gym')]
class Register extends Component
{
    public string $gym_name = '';
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register()
    {
        $data = $this->validate([
            'gym_name' => 'required|string|max:120',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190|unique:users,email',
            'phone' => 'nullable|string|max:30',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = DB::transaction(function () use ($data) {
            $slug = Str::slug($data['gym_name']);
            $gym = Gym::create([
                'name' => $data['gym_name'],
                'slug' => Gym::where('slug', $slug)->exists() ? $slug.'-'.Str::lower(Str::random(4)) : $slug,
                'email' => $data['email'],
                'phone' => $data['phone'],
                'trial_ends_at' => today()->addDays(14),
            ]);
            $branch = Branch::create(['gym_id' => $gym->id, 'name' => 'Main branch', 'code' => 'MAIN']);

            return User::create([
                'gym_id' => $gym->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'role' => 'gym_admin',
                'password' => $data['password'],
            ]);
        });

        return LoginFlow::complete($user);
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
