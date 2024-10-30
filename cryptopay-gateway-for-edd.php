<?php

declare(strict_types=1);

defined('ABSPATH') || exit;

// @phpcs:disable PSR1.Files.SideEffects
// @phpcs:disable PSR12.Files.FileHeader
// @phpcs:disable Generic.Files.InlineHTML
// @phpcs:disable Generic.Files.LineLength

/**
 * Plugin Name: CryptoPay Gateway for Easy Digital Downloads (EDD)
 * Version:     1.0.1
 * Plugin URI:  https://beycanpress.com/cryptopay/
 * Description: Adds Cryptocurrency payment gateway (CryptoPay) for Easy Digital Downloads (EDD).
 * Author:      BeycanPress LLC
 * Author URI:  https://beycanpress.com
 * License:     GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: edd-cryptopay
 * Tags: Bitcoin, Ethereum, Crypto, Payments, Easy Digital Downloads (EDD)
 * Requires at least: 5.0
 * Tested up to: 6.6
 * Requires PHP: 8.1
*/

// Autoload
require_once __DIR__ . '/vendor/autoload.php';

define('EDD_CRYPTOPAY_FILE', __FILE__);
define('EDD_CRYPTOPAY_VERSION', '1.0.0');
define('EDD_CRYPTOPAY_KEY', basename(__DIR__));
define('EDD_CRYPTOPAY_URL', plugin_dir_url(__FILE__));
define('EDD_CRYPTOPAY_DIR', plugin_dir_path(__FILE__));
define('EDD_CRYPTOPAY_SLUG', plugin_basename(__FILE__));

use BeycanPress\CryptoPay\Integrator\Helpers;

Helpers::registerModel(BeycanPress\CryptoPay\EDD\Models\TransactionsPro::class);
Helpers::registerLiteModel(BeycanPress\CryptoPay\EDD\Models\TransactionsLite::class);

load_plugin_textdomain('edd-cryptopay', false, basename(__DIR__) . '/languages');

add_action('plugins_loaded', function (): void {
    Helpers::registerModel(BeycanPress\CryptoPay\EDD\Models\TransactionsPro::class);
    Helpers::registerLiteModel(BeycanPress\CryptoPay\EDD\Models\TransactionsLite::class);

    if (!defined('EDD_PLUGIN_BASE')) {
        Helpers::requirePluginMessage('Easy Digital Downloads (EDD)', 'https://wordpress.org/plugins/easy-digital-downloads/');
    } elseif (Helpers::bothExists()) {
        new BeycanPress\CryptoPay\EDD\Loader();
    } else {
        Helpers::requireCryptoPayMessage('Easy Digital Downloads (EDD)');
    }
});
