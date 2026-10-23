<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TrialCartValidationTest extends TestCase
{
    /** @var array<int, object> */
    private array $products = [];

    protected function setUp(): void
    {
        parent::setUp();

        require_once __DIR__ . '/../../modules/addons/panelalpha/lib/TrialCartValidation.php';

        \WHMCS\Session::resetForTest();
        $_SESSION = ['cart' => ['products' => []]];
        $this->products = [];
    }

    private function stubSession(int $trialServiceId, int $instanceCount): void
    {
        \WHMCS\Session::set('panelalpha_trial_service_id', $trialServiceId);
        \WHMCS\Session::set('panelalpha_trial_service_instance_count', $instanceCount);
    }

    private function makeProduct(string $servertype, bool $autoSetSites): object
    {
        return new class ($servertype, $autoSetSites) {
            public string $servertype;

            private bool $autoSetSites;

            public function __construct(string $servertype, bool $autoSetSites)
            {
                $this->servertype = $servertype;
                $this->autoSetSites = $autoSetSites;
            }

            public function hasAutomaticallySetNumberOfSitesOnUpgradeFromTrialOption(): bool
            {
                return $this->autoSetSites;
            }
        };
    }

    private function registerProduct(int $id, string $servertype, bool $autoSetSites): void
    {
        $this->products[$id] = $this->makeProduct($servertype, $autoSetSites);
    }

    private function findProduct(int $id): ?object
    {
        return $this->products[$id] ?? null;
    }

    private function findSitesOption(int $id): object
    {
        return (object)['optionname' => 'sites|Number of Sites'];
    }

    private function panelCount(int $count): callable
    {
        return fn (int $trialServiceId) => ['instances_count' => $count];
    }

    public function testSelectsPanelAlphaProductWhenFirstCartItemIsNotPanelAlpha(): void
    {
        $this->stubSession(42, 3);
        $_SESSION['cart']['products'] = [
            ['pid' => 1],
            ['pid' => 2],
        ];
        $this->registerProduct(1, 'cpanel', false);
        $this->registerProduct(2, 'panelalpha', true);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 5]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            $this->panelCount(3)
        );

        $this->assertSame(
            'Your trial service must have the same number of sites (3) as the Number of Sites you selected (5).',
            $result
        );
    }

    public function testTooFewSitesReturnsMoreSitesError(): void
    {
        $this->stubSession(42, 5);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 1]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            $this->panelCount(5)
        );

        $this->assertSame(
            'Your trial service has more sites (5) than the Number of Sites you selected (1).',
            $result
        );
    }

    public function testAutoSetMismatchReturnsExistingError(): void
    {
        $this->stubSession(42, 4);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', true);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 5]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            $this->panelCount(4)
        );

        $this->assertSame(
            'Your trial service must have the same number of sites (4) as the Number of Sites you selected (5).',
            $result
        );
    }

    public function testValidSelectionReturnsNoError(): void
    {
        $this->stubSession(42, 3);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', true);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 3]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            $this->panelCount(3)
        );

        $this->assertNull($result);
    }

    public function testIgnoresSessionInstanceCountWhenPanelReportsMoreSites(): void
    {
        \WHMCS\Session::set('panelalpha_trial_service_id', 42);
        \WHMCS\Session::set('panelalpha_trial_service_instance_count', 1);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 1]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            fn (int $trialServiceId) => ['instances_count' => 5]
        );

        $this->assertSame(
            'Your trial service has more sites (5) than the Number of Sites you selected (1).',
            $result
        );
    }

    public function testPanelLookupFailureReturnsCustomerFacingError(): void
    {
        \WHMCS\Session::set('panelalpha_trial_service_id', 42);
        \WHMCS\Session::set('panelalpha_trial_service_instance_count', 1);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 1]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            fn (int $trialServiceId) => throw new \Exception('API down')
        );

        $this->assertSame(
            "We couldn't verify the number of sites on your trial. Please try again.",
            $result
        );
    }

    public function testProductWithoutSitesOptionDoesNotBlockCart(): void
    {
        $this->stubSession(42, 3);
        $_SESSION['cart']['products'] = [['pid' => 1]];
        $this->registerProduct(1, 'cpanel', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 5]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => (object)['optionname' => 'disk|Disk space'],
            $this->panelCount(3)
        );

        $this->assertNull($result);
    }

    public function testCartWithoutPanelAlphaProductDoesNotBlockCart(): void
    {
        $this->stubSession(42, 3);
        $_SESSION['cart']['products'] = [['pid' => 1]];
        $this->registerProduct(1, 'cpanel', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 5]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            $this->panelCount(3)
        );

        $this->assertNull($result);
    }

    public function testPanelLookupTypeErrorFailsClosed(): void
    {
        $this->stubSession(42, 1);
        $_SESSION['cart']['products'] = [['pid' => 2]];
        $this->registerProduct(2, 'panelalpha', false);

        $result = panelalpha_validate_trial_cart_number_of_sites(
            ['configoption' => [10 => 1]],
            fn (int $id) => $this->findProduct($id),
            fn (int $id) => $this->findSitesOption($id),
            function (int $trialServiceId): array {
                throw new \TypeError('Return value must be of type array, null returned');
            }
        );

        $this->assertSame(
            "We couldn't verify the number of sites on your trial. Please try again.",
            $result
        );
    }
}
