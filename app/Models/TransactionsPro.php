<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\EDD\Models;

use BeycanPress\CryptoPay\Models\AbstractTransaction;

class TransactionsPro extends AbstractTransaction
{
    public string $addon = 'edd';

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct('edd_transaction');
    }
}
