<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Helper;

class ServerHelperTest extends TestCase
{
    private array $sampleServers = [
        ['id' => 5, 'name' => 'cp4.lax1.test.local'],
        ['id' => 11, 'name' => 'cp11.ams1.test.local'],
        ['id' => 15, 'name' => 'cp15.waw1.test.local'],
    ];

    public function testGenerateSecurePassword()
    {
        $length = 16;
        $string = Helper::generateSecurePassword($length);
        $this->assertEquals($length, strlen($string));
        $this->assertMatchesRegularExpression('/[a-z]/', $string);
        $this->assertMatchesRegularExpression('/[A-Z]/', $string);
        $this->assertMatchesRegularExpression('/[0-9]/', $string);
        $this->assertMatchesRegularExpression('/[!@#$%^&*()\\-_=+\\[\\]{};:,.<>?]/', $string);
    }

    public function testParseServerLocationValue()
    {
        $this->assertSame(11, Helper::parseServerLocationValue('11'));
        $this->assertSame(11, Helper::parseServerLocationValue('11|cp11.ams1.test.local'));
        $this->assertSame(11, Helper::parseServerLocationValue('11|Amsterdam, NL'));
        $this->assertNull(Helper::parseServerLocationValue('ams|Amsterdam'));
        $this->assertNull(Helper::parseServerLocationValue('Amsterdam, NL'));
        $this->assertNull(Helper::parseServerLocationValue('0'));
        $this->assertNull(Helper::parseServerLocationValue(''));
        $this->assertNull(Helper::parseServerLocationValue(null));
    }

    public function testGetServerLocationFromNumericValues()
    {
        $this->assertSame(11, Helper::getServerLocation([
            'configoptions' => ['server_location' => '11'],
            'customfields' => [],
        ]));

        $this->assertSame(11, Helper::getServerLocation([
            'configoptions' => ['location' => '11|cp11.ams1.test.local'],
            'customfields' => [],
        ]));

        $this->assertSame(11, Helper::getServerLocation([
            'configoptions' => [],
            'customfields' => ['Location' => '11|Amsterdam, NL'],
        ]));
    }

    public function testGetServerLocationPrefersServerLocationOverLocation()
    {
        $this->assertSame(5, Helper::getServerLocation([
            'configoptions' => [
                'server_location' => '5',
                'location' => '11|Amsterdam, NL',
            ],
            'customfields' => [],
        ]));
    }

    public function testGetServerLocationReturnsNullForGeoCodes()
    {
        $this->assertNull(Helper::getServerLocation([
            'configoptions' => ['location' => 'ams|Amsterdam, NL'],
            'customfields' => [],
        ]));
    }

    public function testGetServerLocationResolvesCityLabelWithServers()
    {
        $this->assertSame(11, Helper::getServerLocation([
            'configoptions' => ['server_location' => 'Amsterdam, NL'],
            'customfields' => [],
        ], $this->sampleServers));

        $this->assertSame(15, Helper::getServerLocation([
            'configoptions' => ['location' => 'Warsaw, Poland'],
            'customfields' => [],
        ], $this->sampleServers));

        $this->assertSame(5, Helper::getServerLocation([
            'configoptions' => ['server_location' => 'Los Angeles, USA'],
            'customfields' => [],
        ], $this->sampleServers));
    }

    public function testResolveServerLocationFromLabelMatchesHostname()
    {
        $this->assertSame(11, Helper::resolveServerLocationFromLabel('cp11.ams1.test.local', $this->sampleServers));
        $this->assertSame(11, Helper::resolveServerLocationFromLabel('11|cp11.ams1.test.local', $this->sampleServers));
    }

    public function testGetHostingAccountConfigExcludesLocationFields()
    {
        $config = Helper::getHostingAccountConfig([
            'configoption11' => false,
            'customfields' => [
                'location' => 'Amsterdam, NL',
                'server_location' => '11',
            ],
            'configoptions' => [
                'sites' => '3',
                'whm_package' => 'premium|Premium',
            ],
        ]);

        $this->assertArrayNotHasKey('location', $config);
        $this->assertArrayNotHasKey('Location', $config);
        $this->assertArrayNotHasKey('server_location', $config);
        $this->assertArrayNotHasKey('sites', $config);
        $this->assertSame('premium', $config['whm_package']);
    }

    public function testGetHostingAccountConfigMapsGeoAffinityForWpCloudCodes()
    {
        $config = Helper::getHostingAccountConfig([
            'configoption11' => false,
            'customfields' => [
                'location' => 'ams|Amsterdam, NL',
            ],
            'configoptions' => [],
        ]);

        $this->assertArrayNotHasKey('location', $config);
        $this->assertSame('ams', $config['geo_affinity']);
    }
}
