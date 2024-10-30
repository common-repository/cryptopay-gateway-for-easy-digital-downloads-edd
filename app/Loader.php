<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\EDD;

use BeycanPress\CryptoPay\Integrator\Hook;
use BeycanPress\CryptoPay\Integrator\Helpers;

class Loader
{
    /**
     * Loader constructor.
     */
    public function __construct()
    {
        if (Helpers::exists()) {
            new Gateways\GatewayPro();
        }

        if (Helpers::liteExists()) {
            new Gateways\GatewayLite();
        }

        Helpers::registerIntegration('edd');

        // add transaction page
        Helpers::createTransactionPage(
            esc_html__('EDD Transactions', 'edd-cryptopay'),
            'edd',
            10,
            [
                'orderId' => function ($tx) {
                    return Helpers::run('view', 'components/link', [
                        'url' => sprintf(admin_url('edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=%d'), $tx->orderId), // @phpcs:ignore
                        // translators: %d: order id
                        'text' => sprintf(esc_html__('View order #%d', 'gf-cryptopay'), $tx->orderId)
                    ]);
                }
            ]
        );

        // payment api process
        Hook::addAction('payment_finished_edd', [$this, 'paymentFinished']);
        Hook::addFilter('payment_redirect_urls_edd', [$this, 'paymentRedirectUrls']);
    }

    /**
     * @param object $data
     * @return void
     */
    public function paymentFinished(object $data): void
    {
        if ($data->getStatus()) {
            edd_update_payment_status($data->getOrder()->getId(), 'complete');
        } else {
            edd_update_payment_status($data->getOrder()->getId(), 'failed');
        }
    }

    /**
     * @param object $data
     * @return array<string,string>
     */
    public function paymentRedirectUrls(object $data): array
    {
        return [
            'success' => edd_get_confirmation_page_uri(),
            'failed' => edd_get_receipt_page_uri($data->getOrder()->getId())
        ];
    }
}
