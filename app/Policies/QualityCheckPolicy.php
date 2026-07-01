<?php

namespace App\Policies;

use App\Models\QualityCheck;
use App\Models\User;

class QualityCheckPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, QualityCheck $qualityCheck): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, QualityCheck $qualityCheck): bool
    {
        return true;
    }

    public function delete(User $user, QualityCheck $qualityCheck): bool
    {
        return true;
    }
}
