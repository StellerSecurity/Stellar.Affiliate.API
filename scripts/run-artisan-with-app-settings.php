<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$settingsPath = $argv[1] ?? '';
$command = array_slice($argv, 2);
$allowed = [
    ['migrate', '--force', '--no-interaction'],
    ['affiliate:process-payouts', '--provider-check'],
    ['affiliate:process-payouts', '--preview'],
];

if (! in_array($command, $allowed, true) || ! is_file($settingsPath)) {
    fwrite(STDERR, "Invalid production Artisan invocation.\n");
    exit(64);
}

$settings = json_decode((string) file_get_contents($settingsPath), true, flags: JSON_THROW_ON_ERROR);
if (! is_array($settings)) {
    fwrite(STDERR, "Invalid Azure App Settings payload.\n");
    exit(65);
}

foreach ($settings as $setting) {
    $name = $setting['name'] ?? null;
    $value = $setting['value'] ?? null;
    if (! is_string($name) || ! preg_match('/^[A-Z][A-Z0-9_]*$/', $name) || ! is_string($value)) {
        continue;
    }

    putenv($name.'='.$value);
    $_ENV[$name] = $value;
    $_SERVER[$name] = $value;
}

chdir($root);
$argv = array_merge([$root.'/artisan'], $command);
$argc = count($argv);
$_SERVER['argv'] = $argv;
$_SERVER['argc'] = $argc;

require $root.'/artisan';
