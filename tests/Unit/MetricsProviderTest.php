<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use WHMCS\Module\Server\PanelAlpha\MetricsProvider;
use WHMCS\UsageBilling\Contracts\Metrics\MetricInterface;

class MetricsProviderTest extends TestCase
{
    public function testMetricsReturnsArrayOfMetrics()
    {
        $provider = new MetricsProvider([]);
        $metrics = $provider->metrics();

        $this->assertIsArray($metrics);
        $this->assertCount(5, $metrics);
        
        foreach ($metrics as $metric) {
            $this->assertInstanceOf('WHMCS\UsageBilling\Metrics\Metric', $metric);
        }
    }
}
