<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function view(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }

    public function update(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }
}
