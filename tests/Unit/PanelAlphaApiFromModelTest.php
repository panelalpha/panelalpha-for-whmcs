<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;

class PanelAlphaApiFromModelTest extends TestCase
{
    /**
     * WHMCS 9.1+ keeps the access hash encrypted; the module must send the decrypted token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testUsesDecryptedAccessHashWhenWhmcsProvidesIt()
    {
        $this->defineServerWithDecrypt();

        $api = PanelAlphaApi::fromModel($this->serverModel(['id' => 7, 'accesshash' => '2$encrypted-value']));

        $this->assertSame('plain-token', $this->sentToken($api));
        $this->assertSame([7], \WHMCS\Product\Server::$calls);
    }

    /**
     * Older WHMCS versions store the token as plain text and have no decrypt helper.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testKeepsStoredAccessHashOnOlderWhmcs()
    {
        $api = PanelAlphaApi::fromModel($this->serverModel(['id' => 7, 'accesshash' => 'plain-token']));

        $this->assertSame('plain-token', $this->sentToken($api));
    }

    /**
     * Places that pass a tblservers row straight to the constructor (product config, metrics, ListAccounts).
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testDecryptsServerRowPassedToConstructor()
    {
        $this->defineServerWithDecrypt();

        $api = new PanelAlphaApi(['id' => 3, 'hostname' => 'https://panel.example.com', 'accesshash' => '2$encrypted-value']);

        $this->assertSame('plain-token', $this->sentToken($api));
    }

    /**
     * Module params from WHMCS already hold the decrypted token.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testLeavesWhmcsModuleParamsAlone()
    {
        $this->defineServerWithDecrypt();

        $api = new PanelAlphaApi(['serverid' => 3, 'serverhostname' => 'panel.example.com', 'serveraccesshash' => 'whmcs-token']);

        $this->assertSame('whmcs-token', $this->sentToken($api));
        $this->assertSame([], \WHMCS\Product\Server::$calls);
    }

    private function defineServerWithDecrypt(): void
    {
        eval('namespace WHMCS\Product; class Server {
            public static $calls = [];
            public static function getDecryptedAccessHash(int $serverId) {
                self::$calls[] = $serverId;
                return "plain-token";
            }
        }');
    }

    private function serverModel(array $attributes): object
    {
        return new class ($attributes + ['hostname' => 'https://panel.example.com', 'port' => '8443', 'secure' => '']) {
            private $attributes;

            public function __construct(array $attributes)
            {
                $this->attributes = $attributes;
            }

            public function toArray(): array
            {
                return $this->attributes;
            }
        };
    }

    private function sentToken(PanelAlphaApi $api): string
    {
        $request = (new ReflectionClass($api))->getProperty('request');
        $request->setAccessible(true);
        $token = (new ReflectionClass($request->getValue($api)))->getProperty('token');
        $token->setAccessible(true);

        return $token->getValue($request->getValue($api));
    }
}
