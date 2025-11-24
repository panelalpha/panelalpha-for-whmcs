<?php

require_once __DIR__ . '/../vendor/autoload.php';

if (!defined('WHMCS')) {
    define('WHMCS', true);
}

// Mock WHMCS Database Capsule if needed
if (!class_exists('WHMCS\Database\Capsule')) {
    class Capsule {
        public static function table($table) { return new self; }
        public static function where($col, $op = null, $val = null) { return new self; }
        public static function get() { return []; }
        public static function first() { return null; }
        public static function update($data) { return true; }
        public static function insert($data) { return true; }
        public static function join($table, $one, $op, $two) { return new self; }
        public static function select($columns) { return new self; }
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

if (!class_exists('WHMCS\UsageBilling\Metrics\Metric')) {
    class Metric {
        public function __construct($id, $name, $type, $unit) {}
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
