<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/support/WhmcsSessionStub.php';

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Mock WHMCS Database Capsule if needed
if (!class_exists('WHMCS\Database\Capsule')) {
    class CapsuleTestFixture
    {
        /** @var array|null */
        public static $queue = null;

        /** @var callable|null */
        public static $selectHandler = null;
    }

    class CapsuleQueryBuilder
    {
        public function join($table, $one, $op = null, $two = null)
        {
            return $this;
        }

        public function where($col, $op = null, $val = null)
        {
            return $this;
        }

        public function select($columns)
        {
            return $this;
        }

        public function get()
        {
            if (CapsuleTestFixture::$queue !== null) {
                return CapsuleTestFixture::$queue;
            }

            return [];
        }

        public function update($data)
        {
            return true;
        }

        public function insert($data)
        {
            return true;
        }

        public function value($col)
        {
            return null;
        }
    }

    class Capsule
    {
        public static function table($table)
        {
            return new CapsuleQueryBuilder();
        }

        public static function where($col, $op = null, $val = null)
        {
            return new CapsuleQueryBuilder();
        }

        public static function get()
        {
            return [];
        }

        public static function first()
        {
            return null;
        }

        public static function update($data)
        {
            return true;
        }

        public static function insert($data)
        {
            return true;
        }

        public static function join($table, $one, $op = null, $two = null)
        {
            return new CapsuleQueryBuilder();
        }

        public static function select($columns, $bindings = [])
        {
            if (is_string($columns) && is_callable(CapsuleTestFixture::$selectHandler)) {
                return (CapsuleTestFixture::$selectHandler)($columns, $bindings);
            }

            if (is_string($columns)) {
                return [];
            }

            return new CapsuleQueryBuilder();
        }
    }
    class_alias('Capsule', 'WHMCS\Database\Capsule');
}

// Mock WHMCS UsageBilling classes
if (!interface_exists('WHMCS\UsageBilling\Contracts\Metrics\MetricInterface')) {
    interface MetricInterface {
        const TYPE_SNAPSHOT = 'snapshot';
        const TYPE_PERIOD_MONTH = 'period_month';
    }
    class_alias('MetricInterface', 'WHMCS\UsageBilling\Contracts\Metrics\MetricInterface');
}

if (!interface_exists('WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface')) {
    interface ProviderInterface {}
    class_alias('ProviderInterface', 'WHMCS\UsageBilling\Contracts\Metrics\ProviderInterface');
}

if (!class_exists('WHMCS\UsageBilling\Metrics\Usage')) {
    class Usage {
        /** @var mixed */
        public $value;

        public function __construct($value)
        {
            $this->value = $value;
        }
    }
    class_alias('Usage', 'WHMCS\UsageBilling\Metrics\Usage');
}

if (!class_exists('WHMCS\UsageBilling\Metrics\Metric')) {
    class Metric {
        /** @var string */
        private $systemName;

        /** @var Usage|null */
        private $usage;

        public function __construct($systemName, $name, $type, $unit)
        {
            $this->systemName = $systemName;
        }

        public function systemName(): string
        {
            return $this->systemName;
        }

        public function withUsage(Usage $usage): self
        {
            $clone = clone $this;
            $clone->usage = $usage;

            return $clone;
        }

        public function usage(): ?Usage
        {
            return $this->usage;
        }
    }
    class_alias('Metric', 'WHMCS\UsageBilling\Metrics\Metric');
}

if (!class_exists('WHMCS\UsageBilling\Metrics\Units\WholeNumber')) {
    class WholeNumber { public function __construct($id) {} }
    class_alias('WholeNumber', 'WHMCS\UsageBilling\Metrics\Units\WholeNumber');
}

if (!class_exists('WHMCS\UsageBilling\Metrics\Units\GigaBytes')) {
    class GigaBytes { public function __construct($id) {} }
    class_alias('GigaBytes', 'WHMCS\UsageBilling\Metrics\Units\GigaBytes');
}

if (!class_exists('WHMCS\UsageBilling\Metrics\Units\MegaBytes')) {
    class MegaBytes { public function __construct($id) {} }
    class_alias('MegaBytes', 'WHMCS\UsageBilling\Metrics\Units\MegaBytes');
}

if (!function_exists('logModuleCall')) {
    function logModuleCall($module, $action, $request, $response, $data = null, $variablesToReplace = []) {}
}

if (!function_exists('logActivity')) {
    function logActivity($message)
    {
    }
}

if (!function_exists('formatCurrency')) {
    function formatCurrency($amount, $currencyId)
    {
        return 'CUR' . $currencyId . ':' . $amount;
    }
}
