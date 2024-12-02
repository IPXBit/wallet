<?php
namespace Modules\Wallet\App\Services;

use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\WalletTransactionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\App\Repositories\Contracts\AppUserRepositoryInterface;
use Modules\Wallet\App\Repositories\Contracts\WalletTransactionRepositoryInterface as ContractsWalletTransactionRepositoryInterface;

class WalletService
{
    protected $userRepository;
    protected $transactionRepository;

    public function __construct(
        AppUserRepositoryInterface $userRepository,
        ContractsWalletTransactionRepositoryInterface $transactionRepository
    ) {
        $this->userRepository = $userRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function getUserBalance()
    {
        $user = $this->userRepository->getAuthenticatedUser();
        return $user->wallet_balance;
    }

    public function addBalance($amount, $transactionData)
    {
        DB::transaction(function () use ($amount, $transactionData) {
            $user = $this->userRepository->getAuthenticatedUser();
            $this->userRepository->updateWalletBalance($user->id, $amount);
            $transactionData['app_user_id'] = $user->id;
            $transactionData['credit'] = $amount;
            $this->transactionRepository->createTransaction($transactionData);
        });
    }

    public function transferBalance($recipientId, $amount, $transactionData)
    {
        DB::transaction(function () use ($recipientId, $amount, $transactionData) {
            $sender = $this->userRepository->getAuthenticatedUser();
            $this->userRepository->updateWalletBalance($sender->id, -$amount);
            $transactionData['app_user_id'] = $sender->id;
            $transactionData['debit'] = $amount;
            $transactionData['transaction_type'] = 'deduction';
            $this->transactionRepository->createTransaction($transactionData);

            $this->userRepository->updateWalletBalance($recipientId, $amount);
            $transactionData['app_user_id'] = $recipientId;
            $transactionData['credit'] = $amount;
            $transactionData['transaction_type'] = 'add';
            $this->transactionRepository->createTransaction($transactionData);
        });
    }
}
