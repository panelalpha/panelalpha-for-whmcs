<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Addon\PanelAlpha\Helper;

class InvoiceUserBindingTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/support/LocalApiStub.php';
    }

    protected function setUp(): void
    {
        \LocalApiTestFixture::reset();
    }

    protected function tearDown(): void
    {
        \CapsuleTestFixture::$selectHandler = null;
        parent::tearDown();
    }

    public function testGetPayInvoiceUrlReturnsNullWhenPanelUserDoesNotMatch(): void
    {
        $invoiceQueryBindings = null;
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) use (&$invoiceQueryBindings) {
            if (stripos($sql, 'tblinvoiceitems') !== false && stripos($sql, 'User ID') !== false) {
                $invoiceQueryBindings = $bindings;

                return [];
            }

            return [];
        };

        $url = Helper::getPayInvoiceUrl(9002, 55);

        $this->assertSame([55, 9002], $invoiceQueryBindings);
        $this->assertNull($url);
        $this->assertSame([], \LocalApiTestFixture::$calls);
    }

    public function testGetPayInvoiceUrlReturnsSsoUrlWhenPanelUserMatches(): void
    {
        $invoiceQueryBindings = null;
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) use (&$invoiceQueryBindings) {
            if (stripos($sql, 'tblinvoiceitems') !== false && stripos($sql, 'User ID') !== false) {
                $invoiceQueryBindings = $bindings;

                return [(object) ['userid' => 2]];
            }

            return [];
        };

        $url = Helper::getPayInvoiceUrl(9002, 55);

        $this->assertSame([55, 9002], $invoiceQueryBindings);
        $this->assertSame(
            'https://whmcs.example/sso/viewinvoice.php?id=9002',
            $url
        );
        $this->assertCount(1, \LocalApiTestFixture::$calls);
        $this->assertSame('CreateSsoToken', \LocalApiTestFixture::$calls[0]['command']);
        $this->assertSame(2, \LocalApiTestFixture::$calls[0]['data']['client_id']);
    }

    public function testGetPayInvoiceUrlLegacyModeLooksUpInvoiceByIdOnly(): void
    {
        $invoiceQueryBindings = null;
        $invoiceQuerySql = '';
        \CapsuleTestFixture::$selectHandler = function (string $sql, array $bindings) use (&$invoiceQueryBindings, &$invoiceQuerySql) {
            if (stripos($sql, 'tblinvoiceitems') !== false) {
                $invoiceQueryBindings = $bindings;
                $invoiceQuerySql = $sql;

                return [(object) ['userid' => 2]];
            }

            return [];
        };

        $url = Helper::getPayInvoiceUrl(9002);

        $this->assertSame([9002], $invoiceQueryBindings);
        $this->assertStringNotContainsString('User ID', $invoiceQuerySql);
        $this->assertSame('https://whmcs.example/sso/viewinvoice.php?id=9002', $url);
    }

    public function testApiStrictModeReturns404WhenUserIdIsInvalid(): void
    {
        $output = $this->runApiActionInSubprocess([
            'action' => 'get_pay_invoice_url',
            'invoice_id' => '9002',
            'user_id' => 'abc',
        ]);

        $this->assertStringContainsString('Not Found', $output);
    }

    public function testApiStrictModeDownloadReturns404WhenUserIdIsInvalid(): void
    {
        $output = $this->runApiActionInSubprocess([
            'action' => 'download_invoice',
            'invoice_id' => '9002',
            'user_id' => 'abc',
        ]);

        $this->assertStringContainsString('Not Found', $output);
    }

    private function runApiActionInSubprocess(array $request): string
    {
        $projectRoot = dirname(__DIR__, 2);
        $initPath = $projectRoot . '/init.php';
        $createdInit = false;
        if (!is_file($initPath)) {
            file_put_contents($initPath, '<?php if (!defined(\'ROOTDIR\')) { define(\'ROOTDIR\', __DIR__); }');
            $createdInit = true;
        }

        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $localApiStub = dirname(__DIR__) . '/support/LocalApiStub.php';
        $apiPath = $projectRoot . '/modules/addons/panelalpha/api.php';
        $code = sprintf(
            'require %s; require %s; \LocalApiTestFixture::reset(); \WHMCS\Module\Addon\PanelAlpha\Helper::$config = [\'api_token\' => \'t\', \'allow_from\' => \'*\']; $_SERVER[\'REQUEST_METHOD\'] = \'POST\'; $_SERVER[\'REMOTE_ADDR\'] = \'127.0.0.1\'; $_SERVER[\'HTTP_API_TOKEN\'] = \'t\'; $_REQUEST = %s; include %s;',
            var_export($bootstrap, true),
            var_export($localApiStub, true),
            var_export($request, true),
            var_export($apiPath, true)
        );
        $command = escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1';
        $output = shell_exec($command);

        if ($createdInit) {
            unlink($initPath);
        }

        return is_string($output) ? $output : '';
    }
}
