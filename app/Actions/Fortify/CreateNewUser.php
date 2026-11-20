<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            ...$this->profileRules(),
            'department_id' => ['nullable', 'exists:departments,id', Rule::requiredIf(fn () => array_key_exists('position_id', $input) && filled($input['position_id']))],
            'position_id' => [
                'nullable',
                Rule::requiredIf(fn () => array_key_exists('department_id', $input) && filled($input['department_id'])),
                Rule::exists('positions', 'id')->where(fn ($query) => $query->where('department_id', $input['department_id'] ?? null)),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'department_id' => $input['department_id'] ?? null,
                'position_id' => $input['position_id'] ?? null,
                'email_verified_at' => now(),
            ]);

            $role = Role::firstOrCreate(['name' => 'Karyawan']);
            $user->roles()->sync([$role->id]);

            return $user;
        });
    }
}
