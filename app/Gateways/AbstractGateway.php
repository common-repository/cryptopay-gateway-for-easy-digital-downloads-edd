<?php

declare(strict_types=1);

namespace BeycanPress\CryptoPay\EDD\Gateways;

use EDD\Orders\Order;

abstract class AbstractGateway
{
    /**
     * Loader constructor.
     * @param string $id
     * @param string $name
     */
    public function __construct(private readonly string $id, private readonly string $name)
    {
        add_action('edd_' . $this->id . '_cc_form', '__return_false');
        add_filter('edd_payment_gateways', [$this, 'registerGateway']);
        add_action('edd_pre_process_purchase', [$this, 'isConfigured']);
        add_action('edd_gateway_' . $this->id, [$this, 'processPayment']);
        add_filter('edd_accepted_payment_icons', [$this, 'paymentIcons']);
        add_action('edd_order_receipt_after_table', [$this, 'receipt']);
    }

    /**
     * @param array<string,mixed> $gateways
     * @return array<string,mixed>
     */
    public function registerGateway(array $gateways): array
    {
        return array_merge($gateways, [
            $this->id => [
                'admin_label' => $this->name,
                'checkout_label' => $this->name
            ],
        ]);
    }

    /**
     * @param array<string,mixed> $icons
     * @return array<string,mixed>
     */
    public function paymentIcons(array $icons): array
    {
        return array_merge($icons, [
            EDD_CRYPTOPAY_URL . 'assets/images/icon.png' => 'CryptoPay'
        ]);

        return $icons;
    }

    /**
     * @return void
     */
    public function isConfigured(): void
    {
        $isEnabled     = edd_is_gateway_active($this->id);
        $chosenGateway = edd_get_chosen_gateway();

        if ($this->id === $chosenGateway && !$isEnabled) {
            edd_set_error(
                $this->id . '_gateway_not_configured',
                /* translators: %s: Payment Gateway Name */
                sprintf(__('%s payment gateway is not setup.', 'edd-cryptopay'), $this->name)
            );
        }
    }

    /**
     * @param array<string,mixed> $purchaseData
     * @return void
     */
    public function processPayment(array $purchaseData): void
    {
        $paymentData = [
            'status'       => 'pending',
            'gateway'      => $this->id,
            'currency'     => edd_get_currency(),
            'price'        => $purchaseData['price'],
            'date'         => $purchaseData['date'],
            'downloads'    => $purchaseData['downloads'],
            'user_info'    => $purchaseData['user_info'],
            'user_email'   => $purchaseData['user_email'],
            'cart_details' => $purchaseData['cart_details'],
            'purchase_key' => $purchaseData['purchase_key'],
        ];

        $paymentId = edd_insert_payment($paymentData);

        if (false === $paymentId) {
            edd_record_gateway_error(
                'Payment Error',
                sprintf(
                    'Payment creation failed before sending buyer to %. Payment data: %s',
                    $this->name,
                    wp_json_encode($paymentData)
                ),
                $paymentId
            );

            edd_send_back_to_checkout('?payment-mode=' . $this->id);
        } else {
            try {
                $transactionId = 'EDD-' . $paymentId . '-' . uniqid();
                edd_set_payment_transaction_id($paymentId, $transactionId);
                wp_redirect(edd_get_receipt_page_uri($paymentId));
            } catch (\Exception $e) {
                edd_set_error(
                    $this->id . '_error',
                    sprintf(
                        /* translators: %s: Error Message */
                        __('Unexpected error in payment gateway! Error: %s', 'edd-cryptopay'),
                        $e->getMessage()
                    )
                );

                edd_send_back_to_checkout('?payment-mode=' . $this->id);
            }
        }
    }

    /**
     * @param Order $order
     * @return void
     */
    abstract public function receipt(Order $order): void;
}
