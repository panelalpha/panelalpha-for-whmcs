<?php

use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Models\ConfigurableOption;
use WHMCS\Module\Server\PanelAlpha\Models\Product;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\Session;

/**
 * Checks that the "sites" option in the cart fits the trial service being upgraded.
 *
 * The selected number of sites may not be lower than the sites already on the trial.
 * When the product sets the number of sites automatically on upgrade, it must be equal.
 * The callables exist so tests can replace database and API lookups.
 *
 * @param array<string, mixed> $vars                     WHMCS hook parameters.
 * @param callable|null        $findProduct              fn(int $productId): ?Product
 * @param callable|null        $findConfigOption         fn(int $optionId): ?ConfigurableOption
 * @param callable|null        $fetchTrialServiceDetails fn(int $trialServiceId): array
 * @return string|null Error message for the client, or null when the cart is valid.
 */
function panelalpha_validate_trial_cart_number_of_sites(
    array $vars,
    ?callable $findProduct = null,
    ?callable $findConfigOption = null,
    ?callable $fetchTrialServiceDetails = null
): ?string {
    $findProduct ??= static function (int $productId) {
        return Product::find($productId);
    };
    $findConfigOption ??= static function (int $optionId) {
        return ConfigurableOption::find($optionId);
    };
    $fetchTrialServiceDetails ??= static function (int $trialServiceId): array {
        $server = null;

        $trialReferer = Session::get('panelalpha_trial_referer');
        if ($trialReferer) {
            $hostname = parse_url($trialReferer, PHP_URL_HOST);
            if ($hostname) {
                $server = Server::getServerByHostname($hostname);
            }
        }
        if ($server === null) {
            $server = Server::where('type', 'panelalpha')->first();
        }
        if ($server === null) {
            throw new \Exception('No PanelAlpha server found');
        }

        $api = PanelAlphaApi::fromModel($server);

        return $api->getService($trialServiceId);
    };

    $trialServiceId = Session::get('panelalpha_trial_service_id');

    if (!$trialServiceId || empty($vars['configoption'])) {
        return null;
    }

    try {
        $serviceDetails = $fetchTrialServiceDetails((int)$trialServiceId);
        $numberOfInstancesInService = (int)$serviceDetails['instances_count'];
    } catch (\Throwable $e) {
        logActivity("PanelAlpha ERROR: Couldn't validate `number of sites` configurable option. " . $e->getMessage());

        return "We couldn't verify the number of sites on your trial. Please try again.";
    }
    Session::set('panelalpha_trial_service_instance_count', $numberOfInstancesInService);

    $sitesConfigurableOptionValue = null;
    foreach ($vars['configoption'] as $id => $value) {
        $configOption = $findConfigOption((int)$id);
        if (!$configOption) {
            continue;
        }
        if (str_contains($configOption->optionname, 'sites')) {
            $sitesConfigurableOptionValue = (int)$value;
            break;
        }
    }
    if ($sitesConfigurableOptionValue === null) {
        return null;
    }

    if ($numberOfInstancesInService > $sitesConfigurableOptionValue) {
        return "Your trial service has more sites ({$numberOfInstancesInService}) than the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
    }

    $selectedProduct = null;
    foreach (($_SESSION['cart']['products'] ?? []) as $product) {
        $product = $findProduct((int)$product['pid']);
        if (!$product || $product->servertype !== 'panelalpha') {
            continue;
        }
        $selectedProduct = $product;
        break;
    }
    if ($selectedProduct === null) {
        return null;
    }

    if (
        $selectedProduct->hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption()
        && $sitesConfigurableOptionValue !== $numberOfInstancesInService
    ) {
        return "Your trial service must have the same number of sites ({$numberOfInstancesInService}) as the Number of Sites you selected ({$sitesConfigurableOptionValue}).";
    }

    return null;
}
