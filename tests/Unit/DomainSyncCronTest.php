<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class DomainSyncCronTest extends TestCase
{
    public function testServiceWithEmptyDomainBeyondFirstBatchIsSynced(): void
    {
        $projectRoot = dirname(__DIR__, 2);
        $bootstrap = dirname(__DIR__) . '/bootstrap.php';
        $hooks = $projectRoot . '/modules/servers/panelalpha/hooks.php';

        $stubs = <<<'PHP'
namespace WHMCS\Module\Server\PanelAlpha\Models {
    class CronQuery
    {
        public array $rows;
        public function __construct(array $rows) { $this->rows = $rows; }
        public function panelalpha(): self { return $this; }
        public function where($column) {
            if ($column instanceof \Closure) {
                $group = new CronGroup();
                $column($group);
                $this->rows = array_values(array_filter($this->rows, [$group, 'matches']));
            }
            return $this;
        }
        public function orderBy($column): self { return $this; }
        public function limit(int $limit): self { $this->rows = array_slice($this->rows, 0, $limit); return $this; }
        public function get() { return new CronRows($this->rows); }
    }

    class CronRows
    {
        private array $rows;
        public function __construct(array $rows) { $this->rows = $rows; }
        public function each(callable $callback): self { array_map($callback, $this->rows); return $this; }
    }

    class CronGroup
    {
        private array $values = [];
        public function whereNull($column): self { $this->values[] = null; return $this; }
        public function orWhere($column, $value): self { $this->values[] = $value; return $this; }
        public function matches($row): bool { return in_array($row->domain, $this->values, true); }
    }

    class CronServer {}

    class CronProduct
    {
        public function getServer() { return new CronServer(); }
    }

    class Service
    {
        public $id;
        public $domain;
        public $panelalphaServiceId = 1;
        public $product;
        public static array $rows = [];
        public static array $saved = [];
        public static function active() { return new CronQuery(self::$rows); }
        public function save() { self::$saved[] = $this->id; }
    }
}

namespace WHMCS\Module\Server\PanelAlpha\Apis {
    class PanelAlphaApi
    {
        public static function fromModel($server) { return new self(); }
        public function getService($id) { return ['domain' => 'synced.example.com']; }
    }
}

namespace {
    $GLOBALS['cronHook'] = null;
    function add_hook($name, $priority, $callback) {
        if ($name === 'AfterCronJob') {
            $GLOBALS['cronHook'] = $callback;
        }
    }
}
PHP;

        $code = sprintf(
            'require %s; eval(%s); require %s;'
            . 'for ($i = 1; $i <= 30; $i++) { $s = new \WHMCS\Module\Server\PanelAlpha\Models\Service();'
            . '$s->id = $i; $s->domain = "site$i.example.com"; $s->product = new \WHMCS\Module\Server\PanelAlpha\Models\CronProduct();'
            . '\WHMCS\Module\Server\PanelAlpha\Models\Service::$rows[] = $s; }'
            . '$s = new \WHMCS\Module\Server\PanelAlpha\Models\Service(); $s->id = 31; $s->domain = "";'
            . '$s->product = new \WHMCS\Module\Server\PanelAlpha\Models\CronProduct();'
            . '\WHMCS\Module\Server\PanelAlpha\Models\Service::$rows[] = $s;'
            . '($GLOBALS["cronHook"])([]);'
            . 'echo json_encode(["saved" => \WHMCS\Module\Server\PanelAlpha\Models\Service::$saved]);',
            var_export($bootstrap, true),
            var_export($stubs, true),
            var_export($hooks, true)
        );

        $output = shell_exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code) . ' 2>&1');
        $this->assertIsString($output);
        $this->assertMatchesRegularExpression('/(\{.*\})\s*$/s', trim($output), $output);
        preg_match('/(\{.*\})\s*$/s', trim($output), $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertSame([31], $decoded['saved'], $output);
    }
}
