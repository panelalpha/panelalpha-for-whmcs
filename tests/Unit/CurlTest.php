<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\Curl;

class CurlTest extends TestCase
{
    public function testDefaultOptionsIncludeConnectAndTotalTimeouts(): void
    {
        $curl = new Curl();

        $this->assertSame(10, $curl->defaultOptions[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame(60, $curl->defaultOptions[CURLOPT_TIMEOUT]);
    }

    public function testSetTimeoutUpdatesConnectAndTotalTimeouts(): void
    {
        $curl = new Curl();
        $curl->setTimeout(5);

        $this->assertSame(5, $curl->defaultOptions[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame(5, $curl->defaultOptions[CURLOPT_TIMEOUT]);
    }

    public function testSetRequestTimeoutKeepsConnectTimeout(): void
    {
        $curl = new Curl();
        $curl->setRequestTimeout(180);

        $this->assertSame(10, $curl->defaultOptions[CURLOPT_CONNECTTIMEOUT]);
        $this->assertSame(180, $curl->getRequestTimeout());
    }
}
