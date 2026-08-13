<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Certificate;
use App\Models\User;

class CertificatePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if (! $user->is_active) {
            return false;
        }

        return $user->hasRole(Role::Admin->value) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Certificate $certificate): bool
    {
        if ($certificate->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole(Role::Manager->value)) {
            return in_array(
                $certificate->user->department_id,
                $user->visibleDepartmentIds(),
                true,
            );
        }

        return false;
    }

    /**
     * Downloading the PDF. Same rule as viewing — the file is served from a
     * private disk through a controller, so this is the only gate on it.
     */
    public function download(User $user, Certificate $certificate): bool
    {
        return $this->view($user, $certificate);
    }

    public function delete(User $user, Certificate $certificate): bool
    {
        return false;
    }
}
