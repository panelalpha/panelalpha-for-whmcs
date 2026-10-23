<?php

namespace WHMCS\Module\Server\PanelAlpha;

use WHMCS\Module\Server\PanelAlpha\Apis\PanelAlphaApi;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\UsageBilling\Contracts\Metrics\MetricInterface;
use WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface;
use WHMCS\UsageBilling\Metrics\Metric;
use WHMCS\UsageBilling\Metrics\Units\GigaBytes;
use WHMCS\UsageBilling\Metrics\Units\MegaBytes;
use WHMCS\UsageBilling\Metrics\Units\WholeNumber;
use WHMCS\UsageBilling\Metrics\Usage;

/**
 * Usage billing metrics for PanelAlpha services.
 */
class MetricsProvider implements ProviderInterface
{
    /** @var array WHMCS module params; "serverid" selects the server to query. */
    private $moduleParams = [];

    /**
     * @param array $moduleParams WHMCS module params.
     */
    public function __construct($moduleParams)
    {
        $this->moduleParams = $moduleParams;
    }

    /**
     * List the metrics this module reports.
     *
     * @return Metric[]
     */
    public function metrics()
    {
        return [
            new Metric(
                'active_instances',
                'Active Instances',
                MetricInterface::TYPE_SNAPSHOT,
                new WholeNumber('active-instances'),
            ),
            new Metric(
                'remote_backups_size',
                'Remote Backups Size',
                MetricInterface::TYPE_SNAPSHOT,
                new GigaBytes('remote_backups_size'),
            ),
            new Metric(
                'disk_usage',
                'Disk Usage Size',
                MetricInterface::TYPE_SNAPSHOT,
                new GigaBytes('disk_usage'),
            ),
            new Metric(
                'visitors',
                'Visitors',
                MetricInterface::TYPE_PERIOD_MONTH,
                new WholeNumber('visitors'),
            ),
            new Metric(
                'bandwidth',
                'Bandwidth',
                MetricInterface::TYPE_PERIOD_MONTH,
                new MegaBytes('bandwidth'),
            ),
        ];
    }

    /**
     * Get usage of all services on the server.
     *
     * @return array<int|string, Metric[]> Metrics keyed by PanelAlpha service ID.
     * @throws \Exception
     */
    public function usage(): array
    {
        $server = Server::findOrFail($this->moduleParams['serverid']);
        $connection = new PanelAlphaApi($server->toArray());
        $data = $connection->getServicesStats();

        $usage = [];
        foreach ($data as $serviceId => $stats) {
            $serviceData = [
                'active_instances' => $stats['active_instances'],
                'remote_backups_size' => $stats['remote_backups_size'],
                'disk_usage' => $stats['disk_usage'],
                'visitors' => $stats['visitors'],
                'bandwidth' => $stats['bandwidth'],
            ];
            $usage[$serviceId] = $this->wrapUserData($serviceData);
        }

        return $usage;
    }

    /**
     * Get usage of one service.
     *
     * @param int|string|null $panelalphaServiceId PanelAlpha service ID; empty returns no metrics.
     * @return Metric[]
     * @throws \Exception
     */
    public function tenantUsage($panelalphaServiceId): array
    {
        if (!$panelalphaServiceId) {
            return [];
        }
        $server = Server::findOrFail($this->moduleParams['serverid']);
        $connection = new PanelAlphaApi($server->toArray());
        $data = $connection->getServiceStats($panelalphaServiceId);

        $data = [
            'active_instances' => $data['active_instances'],
            'remote_backups_size' => $data['remote_backups_size'],
            'disk_usage' => $data['disk_usage'],
            'visitors' => $data['visitors'],
            'bandwidth' => $data['bandwidth'],
        ];

        return $this->wrapUserData($data);
    }

    /**
     * Attach usage values to the metrics. Metrics with a missing or null value get no usage.
     *
     * @param array $data Usage values keyed by metric system name.
     * @return Metric[]
     */
    private function wrapUserData($data): array
    {
        $wrapped = [];
        foreach ($this->metrics() as $metric) {
            $key = $metric->systemName();
            if (array_key_exists($key, $data) && $data[$key] !== null) {
                $metric = $metric->withUsage(
                    new Usage($data[$key])
                );
            }

            $wrapped[] = $metric;
        }

        return $wrapped;
    }
}
