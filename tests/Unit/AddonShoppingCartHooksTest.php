<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AddonShoppingCartHooksTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     * @return array<string, mixed>
     */
    private function runHookCallbacks(array $config): array
    {
        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $stubs = dirname(__DIR__) . '/support/AddonHooksCartValidationStubs.php';
        $hooks = dirname(__DIR__, 2) . '/modules/addons/panelalpha/hooks.php';

        $code = sprintf(
            'require %s; require %s;'
            . '$hookCallbacks = [];'
            . 'function add_hook($name, $priority, $callback) { global $hookCallbacks; $hookCallbacks[$name] = $callback; }'
            . 'require %s;'
            . '\WHMCS\Session::resetForTest();'
            . '$sessionConfig = %s;'
            . '$_SESSION = $sessionConfig;'
            . 'if (isset($sessionConfig[\'panelalpha_trial_service_id\'])) {'
            . '\WHMCS\Session::set(\'panelalpha_trial_service_id\', $sessionConfig[\'panelalpha_trial_service_id\']);'
            . '}'
            . 'if (isset($sessionConfig[\'panelalpha_trial_service_instance_count\'])) {'
            . '\WHMCS\Session::set(\'panelalpha_trial_service_instance_count\', $sessionConfig[\'panelalpha_trial_service_instance_count\']);'
            . '}'
            . '$productConfig = %s;'
            . '\WHMCS\Module\Server\PanelAlpha\Models\Product::$products = [];'
            . 'foreach ($productConfig as $id => $meta) {'
            . '$product = new class($meta) {'
            . 'public string $servertype; private array $meta;'
            . 'public function __construct(array $meta) { $this->meta = $meta; $this->servertype = $meta[\'servertype\']; }'
            . 'public function hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption(): bool { return (bool) ($this->meta[\'autoSet\'] ?? false); }'
            . '};'
            . '\WHMCS\Module\Server\PanelAlpha\Models\Product::$products[(int) $id] = $product;'
            . '}'
            . '$panelMode = %s;'
            . 'if ($panelMode === \'throw\') {'
            . '\WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi::$getServiceHandler = static function (int $trialServiceId): array {'
            . 'throw new \\Exception(\'API down\');'
            . '};'
            . '} else {'
            . '$instanceCount = (int) $panelMode;'
            . '\WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi::$getServiceHandler = static function (int $trialServiceId) use ($instanceCount): array {'
            . 'return [\'instances_count\' => $instanceCount];'
            . '};'
            . '}'
            . '$vars = %s;'
            . '$results = [];'
            . 'foreach ([\'ShoppingCartValidateProductUpdate\', \'ShoppingCartValidateCheckout\'] as $hookName) {'
            . '$results[$hookName] = $hookCallbacks[$hookName]($vars);'
            . '}'
            . 'echo json_encode($results);',
            var_export($bootstrap, true),
            var_export($stubs, true),
            var_export($hooks, true),
            var_export($config['session'] ?? [], true),
            var_export($config['products'] ?? [], true),
            var_export((string) ($config['panelMode'] ?? '3'), true),
            var_export($config['vars'] ?? [], true)
        );

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1');
        $this->assertIsString($output);
        if (!preg_match('/(\{.*\})\s*$/s', trim($output), $matches)) {
            $this->fail('Hook subprocess did not emit JSON: ' . $output);
        }
        $decoded = json_decode($matches[1], true);
        $this->assertIsArray($decoded);

        return $decoded;
    }

    public function testShoppingCartValidateHooksUsePanelAlphaProductWhenFirstCartLineIsNotPanelAlpha(): void
    {
        $results = $this->runHookCallbacks([
            'session' => [
                'panelalpha_trial_service_id' => 42,
                'cart' => [
                    'products' => [
                        ['pid' => 1],
                        ['pid' => 2],
                    ],
                ],
            ],
            'products' => [
                1 => ['servertype' => 'cpanel', 'autoSet' => false],
                2 => ['servertype' => 'panelalpha', 'autoSet' => true],
            ],
            'panelMode' => '3',
            'vars' => [
                'configoption' => [
                    10 => 5,
                ],
            ],
        ]);

        $expected = 'Your trial service must have the same number of sites (3) as the Number of Sites you selected (5).';
        $this->assertSame($expected, $results['ShoppingCartValidateProductUpdate']);
        $this->assertSame($expected, $results['ShoppingCartValidateCheckout']);
    }

    public function testShoppingCartValidateHooksReturnCustomerFacingErrorWhenPanelLookupFails(): void
    {
        $results = $this->runHookCallbacks([
            'panelMode' => 'throw',
            'session' => [
                'panelalpha_trial_service_id' => 42,
                'cart' => [
                    'products' => [
                        ['pid' => 2],
                    ],
                ],
            ],
            'products' => [
                2 => ['servertype' => 'panelalpha', 'autoSet' => false],
            ],
            'vars' => [
                'configoption' => [
                    10 => 1,
                ],
            ],
        ]);

        $expected = "We couldn't verify the number of sites on your trial. Please try again.";
        $this->assertSame($expected, $results['ShoppingCartValidateProductUpdate']);
        $this->assertSame($expected, $results['ShoppingCartValidateCheckout']);
    }
}
