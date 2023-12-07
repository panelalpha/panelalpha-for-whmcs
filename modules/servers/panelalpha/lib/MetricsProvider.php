<?php

namespace WHMCS\Module\Server\PanelAlpha;

use GuzzleHttp\Exception\GuzzleException;
use WHMCS\Module\Server\PanelAlpha\Models\Server;
use WHMCS\UsageBilling\Contracts\Metrics\MetricInterface;
use WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface;
use WHMCS\UsageBilling\Metrics\Metric;
use WHMCS\UsageBilling\Metrics\Units\Accounts;
use WHMCS\UsageBilling\Metrics\Units\WholeNumber;
use WHMCS\UsageBilling\Metrics\Usage;

class MetricsProvider implements ProviderInterface
{
    private $moduleParams = [];

    public function __construct($moduleParams)
    {
        $this->moduleParams = $moduleParams;
    }

    public function metrics()
    {
        return [
            new Metric(
                'active_instances',
                'Active Instances',
                MetricInterface::TYPE_SNAPSHOT,
                new WholeNumber('active-instances')
            ),
        ];
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function usage(): array
    {
        $server = Server::findOrFail($this->moduleParams['serverid']);
        $connection = new PanelAlphaClient($server->toArray());
        $services = $connection->getInstancesServices();
        $usage = [];
        foreach ($services as $id => $count) {
            $data = [
                'active_instances' => $count
            ];
            $usage[$id] = $this->wrapUserData($data);
        }

        return $usage;
    }

    /**
     * @param $panelalphaServiceId
     * @return array
     * @throws \Exception
     */
    public function tenantUsage($panelalphaServiceId): array
    {
        if (!$panelalphaServiceId) {
            return [];
        }
        $server = Server::findOrFail($this->moduleParams['serverid']);
        $connection = new PanelAlphaClient($server->toArray());
        $data = $connection->getInstancesAssignedToService($panelalphaServiceId);

        $data = [
            'active_instances' => $data['active_instances']
        ];

        return $this->wrapUserData($data);
    }

    /**
     * @param $data
     * @return array
     */
    private function wrapUserData($data): array
    {
        $wrapped = [];
        foreach ($this->metrics() as $metric) {
            $key = $metric->systemName();
            if ($data[$key]) {
                $value = $data[$key];
                $metric = $metric->withUsage(
                    new Usage($value)
                );
            }

            $wrapped[] = $metric;
        }

        return $wrapped;
    }
}