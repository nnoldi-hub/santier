<?php

namespace App\Policies;

use App\Models\MaterialInvoice;
use App\Models\User;

class MaterialInvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MaterialInvoice $materialInvoice): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, MaterialInvoice $materialInvoice): bool
    {
        return true;
    }

    public function delete(User $user, MaterialInvoice $materialInvoice): bool
    {
        return true;
    }
}
