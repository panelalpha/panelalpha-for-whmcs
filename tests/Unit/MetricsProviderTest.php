<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

    public function testWrapUserDataIncludesZeroAndOmitsMissingKey(): void
    {
        $provider = new MetricsProvider([]);
        $method = (new ReflectionClass($provider))->getMethod('wrapUserData');
        $method->setAccessible(true);

        $wrapped = $method->invoke($provider, [
            'active_instances' => 0,
            'remote_backups_size' => 1.5,
            'disk_usage' => 2,
            'bandwidth' => 100,
        ]);

        $byName = [];
        foreach ($wrapped as $metric) {
            $byName[$metric->systemName()] = $metric;
        }

        $this->assertNotNull($byName['active_instances']->usage());
        $this->assertSame(0, $byName['active_instances']->usage()->value);

        $this->assertNull($byName['visitors']->usage());
    }
}
