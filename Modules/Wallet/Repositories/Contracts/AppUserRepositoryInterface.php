<?php
namespace Modules\Wallet\App\Repositories\Contracts;

interface AppUserRepositoryInterface
{
    public function getAuthenticatedUser();
    public function updateWalletBalance($userId, $amount);
}
