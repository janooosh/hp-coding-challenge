<?php

namespace App\Policies;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BrandPolicy
{
    use HandlesAuthorization;

    /**
     * Determines whether the user can edit products for the brand.
     *
     * @param \App\Models\User $user
     * @param \App\Models\Brand $brand
     * @return bool
     */
    public function editProducts(User $user, Brand $brand):bool
    {
        return $user->brands->contains($brand);
    }
}
