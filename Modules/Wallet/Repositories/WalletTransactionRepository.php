<?php
namespace Modules\Wallet\App\Repositories;

use App\Models\WalletTransaction;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Modules\Wallet\App\Models\WalletTransaction as ModelsWalletTransaction;
use Modules\Wallet\App\Repositories\Contracts\WalletTransactionRepositoryInterface as ContractsWalletTransactionRepositoryInterface;

class WalletTransactionRepository implements ContractsWalletTransactionRepositoryInterface
{
    public function createTransaction(array $data)
    {
        return ModelsWalletTransaction::create($data);
    }
}

