<?php

namespace App\Policies;

use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    public function delete(User $user, Goal $goal): bool
    {
        return $user->canAccessSite($goal->site);
    }
}
