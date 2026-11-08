<?php

class GuardScriptTest extends SupplierListTestCase
{
    private $sentinelPaths = array();

    private static $guardFiles = array(
        'index.php',
        'tests/doubles/index.php',
        'tests/index.php',
        'tests/phpstan/index.php',
        'tests/Unit/index.php',
        'translations/index.php',
        'views/index.php',
        'views/templates/_partials/index.php',
        'views/templates/hook/index.php',
        'views/templates/index.php',
    );

    protected function tearDown()
    {
        foreach ($this->sentinelPaths as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
        $this->sentinelPaths = array();
        parent::tearDown();
    }

    /**
     * @dataProvider guardFileProvider
     */
    public function testGuardScriptRedirectsWithoutOutput($relativePath)
    {
        $absolute = $this->repoRoot() . '/' . $relativePath;
        $this->assertFileExists($absolute);
        $sentinel = tempnam(sys_get_temp_dir(), 'guard-sentinel-');
        $this->assertInternalType('string', $sentinel);
        $this->sentinelPaths[] = $sentinel;

        $runner = $this->repoRoot() . '/tests/Unit/guard-child.php';
        $cmd = 'php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($runner) . ' '
            . escapeshellarg($absolute) . ' '
            . escapeshellarg($sentinel);

        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($cmd, $descriptorSpec, $pipes, $this->repoRoot());
        $this->assertInternalType('resource', $process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $this->assertNoPhpDiagnostic($stdout . $stderr);
        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
        $this->assertFileExists($sentinel);
        $content = file_get_contents($sentinel);
        $this->assertInternalType('string', $content);
        $this->assertSame('shutdown', $content);

        $tokens = token_get_all(file_get_contents($absolute));
        $hasHeader = false;
        $hasExit = false;
        foreach ($tokens as $token) {
            if (is_array($token) && $token[0] === T_STRING && $token[1] === 'header') {
                $hasHeader = true;
            }
            if (is_array($token) && $token[0] === T_EXIT) {
                $hasExit = true;
            }
        }
        $this->assertTrue($hasHeader);
        $this->assertTrue($hasExit);
        $this->assertContains("header('Location: ../');", file_get_contents($absolute));
    }

    public function guardFileProvider()
    {
        $sets = array();
        foreach (self::$guardFiles as $file) {
            $sets[] = array($file);
        }

        return $sets;
    }

    public function testKnownGuardListMatchesRepository()
    {
        $found = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->repoRoot(), FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->getFilename() !== 'index.php') {
                continue;
            }
            $path = $file->getPathname();
            if (strpos($path, '/.git/') !== false || strpos($path, '/vendor/') !== false) {
                continue;
            }
            $relative = substr($path, strlen($this->repoRoot()) + 1);
            $found[] = str_replace('\\', '/', $relative);
        }
        sort($found);
        $expected = self::$guardFiles;
        sort($expected);
        $this->assertSame($expected, $found);
    }

    public function testModuleExitsCleanlyWhenPsVersionIsUndefined()
    {
        $runner = $this->repoRoot() . '/tests/Unit/module-load-child.php';
        $cmd = 'php -d error_reporting=-1 -d display_errors=1 -d display_startup_errors=1 -d log_errors=0 '
            . escapeshellarg($runner);
        $descriptorSpec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );
        $process = proc_open($cmd, $descriptorSpec, $pipes, $this->repoRoot());
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $combined = $stdout . $stderr;
        $this->assertNoPhpDiagnostic($combined);
        $this->assertNotContains('AFTER', $combined);
        $this->assertSame(0, $exitCode);
        $this->assertSame('', $stdout);
        $this->assertSame('', $stderr);
    }
}
