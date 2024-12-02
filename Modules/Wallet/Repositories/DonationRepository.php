<?php
namespace Modules\Wallet\App\Repositories;

use App\Models\Donation;
use App\Repositories\Contracts\DonationRepositoryInterface;
use Modules\Wallet\App\Models\Donation as ModelsDonation;
use Modules\Wallet\App\Repositories\Contracts\DonationRepositoryInterface as ContractsDonationRepositoryInterface;

class DonationRepository implements ContractsDonationRepositoryInterface
{
    public function createDonation(array $data)
    {
        return ModelsDonation::create($data);
    }
}
