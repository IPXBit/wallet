<?php
namespace Modules\Wallet\App\Repositories\Contracts;

interface DonationRepositoryInterface
{
    public function createDonation(array $data);
}
