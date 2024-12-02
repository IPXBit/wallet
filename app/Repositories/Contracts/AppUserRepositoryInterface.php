<?php
namespace App\Repositories\Contracts;

interface AppUserRepositoryInterface
{
    public function getAuthenticatedUser();
    public function updateWalletBalance($userId, $amount);
}
