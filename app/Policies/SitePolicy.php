<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function view(User $user, Site $site): bool
    {
        return $user->canAccessSite($site);
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdmin();
    }
}
