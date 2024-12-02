<?php
namespace Modules\Wallet\App\Repositories;

use App\Models\AppUsers;
use App\Repositories\Contracts\AppUserRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Modules\AppUser\App\Models\AppUsers as ModelsAppUsers;
use Modules\Wallet\App\Repositories\Contracts\AppUserRepositoryInterface as ContractsAppUserRepositoryInterface;

class AppUserRepository implements ContractsAppUserRepositoryInterface
{
    public function getAuthenticatedUser()
    {
        return Auth::guard('app_users')->user();
    }

    public function updateWalletBalance($userId, $amount)
    {
        $user = ModelsAppUsers::find($userId);
        $user->wallet_balance += $amount;
        $user->save();
        return $user;
    }
}

