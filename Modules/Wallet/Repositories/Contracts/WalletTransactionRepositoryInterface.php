<?php
namespace Modules\Wallet\App\Repositories\Contracts;

interface WalletTransactionRepositoryInterface
{
    public function createTransaction(array $data);
}
