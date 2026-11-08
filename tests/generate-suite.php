<?php

$repoRoot = dirname(__DIR__);
$unitDir = $repoRoot . '/tests/Unit';
$expected = require $repoRoot . '/tests/expected-suite.php';
$seed = isset($_SERVER['SEED']) ? (int) $_SERVER['SEED'] : (isset($argv[1]) ? (int) $argv[1] : 20261108);
$outFile = isset($argv[2]) ? $argv[2] : ($repoRoot . '/phpunit.xml');

if (!isset($expected['files'], $expected['testCount']) || !is_array($expected['files'])) {
    fwrite(STDERR, "invalid expected-suite.php\n");
    exit(1);
}

$files = $expected['files'];
sort($files);
$absolute = array();
foreach ($files as $file) {
    $path = $unitDir . '/' . $file;
    if (!is_file($path)) {
        fwrite(STDERR, "missing test file: $path\n");
        exit(1);
    }
    $absolute[] = $path;
}

srand($seed);
shuffle($absolute);

$xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
$xml .= '<phpunit bootstrap="' . $repoRoot . '/tests/bootstrap.php"' . "\n";
$xml .= '         colors="true"' . "\n";
$xml .= '         convertErrorsToExceptions="true"' . "\n";
$xml .= '         convertNoticesToExceptions="true"' . "\n";
$xml .= '         convertWarningsToExceptions="true"' . "\n";
$xml .= '         verbose="true">' . "\n";
$xml .= '  <php>' . "\n";
$xml .= '    <ini name="error_reporting" value="-1"/>' . "\n";
$xml .= '    <ini name="display_errors" value="1"/>' . "\n";
$xml .= '    <ini name="display_startup_errors" value="1"/>' . "\n";
$xml .= '  </php>' . "\n";
$xml .= '  <testsuites>' . "\n";
$xml .= '    <testsuite name="module-tests">' . "\n";
foreach ($absolute as $path) {
    $xml .= '      <file>' . htmlspecialchars($path, ENT_QUOTES, 'UTF-8') . "</file>\n";
}
$xml .= "    </testsuite>\n";
$xml .= "  </testsuites>\n";
$xml .= "</phpunit>\n";

file_put_contents($outFile, $xml);
fwrite(STDOUT, 'generated ' . count($absolute) . ' files; expected tests ' . (int) $expected['testCount'] . "\n");
