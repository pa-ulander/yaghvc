<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Get a deployment workflow step by name.
 *
 * @return array<string, mixed>
 */
function deployWorkflowStep(string $name): array
{
    $workflow = Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/deploy.yml');

    foreach ($workflow['jobs']['deploy']['steps'] as $step) {
        if (($step['name'] ?? null) === $name) {
            return $step;
        }
    }

    throw new RuntimeException("Deployment step not found: {$name}");
}

/**
 * Get the shell commands for a deployment workflow step.
 */
function deployWorkflowCommands(string $name): string
{
    return deployWorkflowStep($name)['run'];
}

/**
 * Run a deployment step using isolated files and test values for its secrets.
 *
 * @param  array<string, string>  $environment
 */
function runDeployWorkflowCommands(string $commands, string $directory, array $environment = []): Process
{
    $workflow = Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/deploy.yml');
    $deploymentPath = $environment['SERVER_PATH'] ?? $directory . '/current deployment';
    $commands = strtr($commands, [
        '${{ secrets.SERVER_USER }}' => 'deployer',
        '${{ secrets.SERVER_HOST }}' => 'deploy.example.test',
        '${{ secrets.SERVER_PATH }}' => $deploymentPath,
        '${{ github.run_id }}' => '123456',
        '${{ github.run_attempt }}' => '1',
        '${{ env.COMPOSER_COMMAND }}' => $workflow['env']['COMPOSER_COMMAND'],
        '${{ env.PHP_COMMAND }}' => $workflow['env']['PHP_COMMAND'],
    ]);

    $process = new Process(
        ['bash', '-e', '-o', 'pipefail', '-c', $commands],
        dirname(__DIR__, 2),
        array_merge([
            'HOME' => $directory . '/home',
            'PATH' => $directory . '/bin:' . (string) getenv('PATH'),
            'SERVER_HOST' => 'deploy.example.test',
            'SERVER_USER' => 'deployer',
            'SERVER_PATH' => $deploymentPath,
            'GITHUB_RUN_ID' => '123456',
            'GITHUB_RUN_ATTEMPT' => '1',
            'SYSTEM_RSYNC' => (new ExecutableFinder())->find('rsync') ?? '/nonexistent/rsync',
            'EXECUTE_LOCAL_RSYNC' => '0',
            'SSH_ARGUMENTS_FILE' => $directory . '/ssh_arguments',
            'RSYNC_ARGUMENTS_FILE' => $directory . '/rsync_arguments',
        ], $environment),
    );
    $process->run();

    return $process;
}

/**
 * Return a syntactically valid public key for known-hosts test entries.
 */
function deployTestPublicKey(): string
{
    $key = pack('N', 11) . 'ssh-ed25519' . pack('N', 32) . str_repeat("\0", 32);

    return 'ssh-ed25519 ' . base64_encode($key);
}

beforeEach(function () {
    $this->deployTestDirectory = sys_get_temp_dir() . '/yagvc-deploy-test-' . bin2hex(random_bytes(8));
    $files = new Filesystem();
    $files->makeDirectory($this->deployTestDirectory . '/home/.ssh', 0700, true);
    $files->makeDirectory($this->deployTestDirectory . '/bin', 0700, true);

    $ssh = <<<'SH'
#!/bin/sh
printf '%s\n' "$@" > "$SSH_ARGUMENTS_FILE"
exec /bin/sh -s
SH;
    file_put_contents($this->deployTestDirectory . '/bin/ssh', $ssh);
    chmod($this->deployTestDirectory . '/bin/ssh', 0700);

    $rsync = <<<'SH'
#!/bin/sh
printf '%s\n' "$@" > "$RSYNC_ARGUMENTS_FILE"
if [ "${FAIL_RSYNC:-0}" = "1" ]; then
    exit 35
fi
if [ "${EXECUTE_LOCAL_RSYNC:-0}" = "1" ]; then
    exec "$SYSTEM_RSYNC" "$@"
fi
SH;
    file_put_contents($this->deployTestDirectory . '/bin/rsync', $rsync);
    chmod($this->deployTestDirectory . '/bin/rsync', 0700);
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->deployTestDirectory);
});

it('uses the Composer platform PHP version for web requests', function () {
    $root = dirname(__DIR__, 2);
    $composer = json_decode(file_get_contents($root . '/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    [$major, $minor] = explode('.', $composer['config']['platform']['php']);
    $htaccess = file_get_contents($root . '/public_html/.htaccess');

    preg_match_all('/^\s*AddType\s+application\/x-httpd-php(\d+)\s+\.php\s*$/m', $htaccess, $matches);

    expect($matches[1])->toBe([$major . $minor]);
});

it('fails deployment when the public health check fails', function (int $curlExitCode) {
    $curl = <<<'SH'
#!/bin/sh
printf '%s\n' "$@" > "$CURL_ARGUMENTS_FILE"
exit "$CURL_EXIT_CODE"
SH;
    file_put_contents($this->deployTestDirectory . '/bin/curl', $curl);
    chmod($this->deployTestDirectory . '/bin/curl', 0700);

    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Verify production health'),
        $this->deployTestDirectory,
        [
            'DEPLOY_HEALTHCHECK_URL' => 'https://deploy.example.test/up',
            'CURL_ARGUMENTS_FILE' => $this->deployTestDirectory . '/curl_arguments',
            'CURL_EXIT_CODE' => (string) $curlExitCode,
        ],
    );

    expect($process->getExitCode())->toBe($curlExitCode);
    $arguments = file($this->deployTestDirectory . '/curl_arguments', FILE_IGNORE_NEW_LINES);
    expect($arguments)
        ->toContain('--fail')
        ->toContain('--show-error')
        ->toContain('--retry')
        ->toContain('https://deploy.example.test/up');

    $workflow = Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/deploy.yml');
    $steps = array_column($workflow['jobs']['deploy']['steps'], 'name');
    expect(array_search('Verify production health', $steps, true))
        ->toBeGreaterThan(array_search('Post-deployment tasks', $steps, true))
        ->toBeLessThan(array_search('Rollback on failure', $steps, true));
})->with([
    'healthy application' => [0],
    'unhealthy application' => [22],
]);

it('rejects a known-hosts entry for a different deployment host', function () {
    if ((new ExecutableFinder())->find('ssh-keygen') === null) {
        $this->markTestSkipped('OpenSSH is required to validate host keys.');
    }

    file_put_contents(
        $this->deployTestDirectory . '/home/.ssh/known_hosts',
        "old.example.test " . deployTestPublicKey() . "\n",
    );

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Validate SSH host key'), $this->deployTestDirectory);

    expect($process->getExitCode())->not->toBe(0);
    expect($process->getOutput())->toContain('::error::')->toContain('KNOWN_HOSTS')->toContain('SERVER_HOST');
});

it('accepts a matching plain or hashed deployment host key', function (bool $hashed) {
    if ((new ExecutableFinder())->find('ssh-keygen') === null) {
        $this->markTestSkipped('OpenSSH is required to validate host keys.');
    }

    $knownHosts = $this->deployTestDirectory . '/home/.ssh/known_hosts';
    file_put_contents($knownHosts, "deploy.example.test " . deployTestPublicKey() . "\n");

    if ($hashed) {
        (new Process(['ssh-keygen', '-q', '-H', '-f', $knownHosts]))->mustRun();
    }

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Validate SSH host key'), $this->deployTestDirectory);

    expect($process->isSuccessful())->toBeTrue();
})->with([
    'plain' => [false],
    'hashed' => [true],
]);

it('rejects malformed matching host keys', function () {
    if ((new ExecutableFinder())->find('ssh-keygen') === null) {
        $this->markTestSkipped('OpenSSH is required to validate host keys.');
    }

    file_put_contents(
        $this->deployTestDirectory . '/home/.ssh/known_hosts',
        "deploy.example.test ssh-ed25519 invalid\n",
    );

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Validate SSH host key'), $this->deployTestDirectory);

    expect($process->getExitCode())->not->toBe(0);
    expect($process->getOutput())->toContain('::error::')->toContain('KNOWN_HOSTS');
});

it('reports an empty deployment host before connecting', function () {
    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Validate SSH host key'),
        $this->deployTestDirectory,
        ['SERVER_HOST' => ''],
    );

    expect($process->getExitCode())->not->toBe(0);
    expect($process->getOutput())->toContain('::error::')->toContain('SERVER_HOST');
});

it('backs up the current deployment using remote shell variables', function () {
    if ((new ExecutableFinder())->find('rsync') === null) {
        $this->markTestSkipped('Rsync is required to verify deployment backups.');
    }

    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    mkdir($deploymentPath);
    file_put_contents($deploymentPath . '/index.php', 'existing deployment');

    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Backup current deployment'),
        $this->deployTestDirectory,
        ['EXECUTE_LOCAL_RSYNC' => '1'],
    );

    expect($process->isSuccessful())->toBeTrue();
    $backupPath = $deploymentPath . '_backup_123456_1';
    expect(file_get_contents($backupPath . '/files/index.php'))->toBe('existing deployment');
    expect(file_exists($backupPath . '/complete'))->toBeTrue();
    expect(file_get_contents($this->deployTestDirectory . '/ssh_arguments'))
        ->toContain('-T')
        ->toContain('BatchMode=yes')
        ->toContain('StrictHostKeyChecking=yes');
});

it('keeps the backup outside a deployment path with a trailing slash', function () {
    if ((new ExecutableFinder())->find('rsync') === null) {
        $this->markTestSkipped('Rsync is required to verify deployment backups.');
    }

    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    mkdir($deploymentPath);
    file_put_contents($deploymentPath . '/index.php', 'existing deployment');

    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Backup current deployment'),
        $this->deployTestDirectory,
        ['SERVER_PATH' => $deploymentPath . '/', 'EXECUTE_LOCAL_RSYNC' => '1'],
    );

    expect($process->isSuccessful())->toBeTrue();
    $backupPath = $deploymentPath . '_backup_123456_1';
    expect(file_get_contents($backupPath . '/files/index.php'))->toBe('existing deployment');
});

it('stops when the remote backup cannot be created', function () {
    $process = runDeployWorkflowCommands(deployWorkflowCommands('Backup current deployment'), $this->deployTestDirectory);

    expect($process->getExitCode())->not->toBe(0);
    expect(file_exists($this->deployTestDirectory . '/current deployment_backup_123456_1'))->toBeFalse();
});

it('restores deployment files after a failed deployment without rolling back storage or the database', function () {
    if ((new ExecutableFinder())->find('rsync') === null) {
        $this->markTestSkipped('Rsync is required to verify deployment rollback.');
    }

    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    (new Filesystem())->makeDirectory($deploymentPath . '/storage/app/public', 0700, true);
    file_put_contents($deploymentPath . '/index.php', 'previous deployment');
    file_put_contents($deploymentPath . '/storage/app/public/upload.txt', 'existing upload');

    $backup = runDeployWorkflowCommands(
        deployWorkflowCommands('Backup current deployment'),
        $this->deployTestDirectory,
        ['EXECUTE_LOCAL_RSYNC' => '1'],
    );
    expect($backup->isSuccessful())->toBeTrue();

    file_put_contents($deploymentPath . '/index.php', 'partially deployed code');
    file_put_contents($deploymentPath . '/partial.php', 'partial deployment');

    $rollback = runDeployWorkflowCommands(
        deployWorkflowCommands('Rollback on failure'),
        $this->deployTestDirectory,
        ['EXECUTE_LOCAL_RSYNC' => '1'],
    );

    expect($rollback->isSuccessful())->toBeTrue();
    expect(file_get_contents($deploymentPath . '/index.php'))->toBe('previous deployment');
    expect(file_exists($deploymentPath . '/partial.php'))->toBeFalse();
    expect(file_get_contents($deploymentPath . '/storage/app/public/upload.txt'))->toBe('existing upload');
    expect(deployWorkflowStep('Rollback on failure')['if'])->toBe('failure() && steps.backup.outcome == \'success\'');
    expect($rollback->getOutput())->not->toContain('migrate:rollback');
});

it('refuses to roll back when a complete backup is unavailable', function () {
    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    mkdir($deploymentPath);
    file_put_contents($deploymentPath . '/index.php', 'deployment remains untouched');

    $rollback = runDeployWorkflowCommands(
        deployWorkflowCommands('Rollback on failure'),
        $this->deployTestDirectory,
    );

    expect($rollback->isSuccessful())->toBeFalse();
    expect(file_get_contents($deploymentPath . '/index.php'))->toBe('deployment remains untouched');
    expect($rollback->getErrorOutput())->toContain('complete backup');
});

it('stops post-deployment tasks when composer fails', function () {
    mkdir($this->deployTestDirectory . '/current deployment');
    file_put_contents($this->deployTestDirectory . '/bin/composer', "#!/bin/sh\nexit 12\n");
    chmod($this->deployTestDirectory . '/bin/composer', 0700);
    file_put_contents($this->deployTestDirectory . '/bin/php', "#!/bin/sh\n: > \"\$PHP_INVOKED_FILE\"\n");
    chmod($this->deployTestDirectory . '/bin/php', 0700);

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Post-deployment tasks'), $this->deployTestDirectory);

    expect($process->getExitCode())->toBe(12);
    expect(file_exists($this->deployTestDirectory . '/php_invoked'))->toBeFalse();
});

it('requires strict SSH host verification for rsync as well', function () {
    $process = runDeployWorkflowCommands(deployWorkflowCommands('Deploy to Webhost'), $this->deployTestDirectory);

    expect($process->isSuccessful())->toBeTrue();
    $arguments = file($this->deployTestDirectory . '/rsync_arguments', FILE_IGNORE_NEW_LINES);
    $sshIndex = array_search('-e', $arguments, true);
    expect($sshIndex)->not->toBeFalse();
    expect($arguments[$sshIndex + 1])->toContain('BatchMode=yes')->toContain('StrictHostKeyChecking=yes');
    expect(end($arguments))->toBe('deployer@deploy.example.test:' . $this->deployTestDirectory . '/current deployment');
});

it('preserves server storage during rsync while still removing obsolete code', function (bool $runnerStorage, bool $sharedStorage) {
    $rsync = (new ExecutableFinder())->find('rsync');
    if ($rsync === null) {
        $this->markTestSkipped('Rsync is required to verify deployment deletion rules.');
    }

    $files = new Filesystem();
    $source = $this->deployTestDirectory . '/checkout';
    $destination = $this->deployTestDirectory . '/current deployment';
    $files->makeDirectory($source . '/bootstrap/cache', 0700, true);
    $files->makeDirectory($source . '/app/storage', 0700, true);
    file_put_contents($source . '/index.php', 'new code');
    file_put_contents($source . '/app/storage/service.php', 'application code');
    file_put_contents($source . '/bootstrap/cache/.gitignore', "*\n");
    if ($sharedStorage) {
        $files->ensureDirectoryExists($destination);
        $files->ensureDirectoryExists($this->deployTestDirectory . '/shared storage');
        symlink($this->deployTestDirectory . '/shared storage', $destination . '/storage');
    }

    $runtimeFiles = [
        'storage/app/public/upload.txt' => 'existing upload',
        'storage/framework/sessions/session-id' => 'existing session',
        'storage/framework/cache/data/cache-key' => 'existing cache',
        'storage/logs/laravel.log' => 'existing log',
    ];
    foreach ($runtimeFiles as $path => $contents) {
        $files->ensureDirectoryExists(dirname($destination . '/' . $path));
        file_put_contents($destination . '/' . $path, $contents);
        if ($runnerStorage) {
            $files->ensureDirectoryExists(dirname($source . '/' . $path));
            file_put_contents($source . '/' . $path, 'runner data');
        }
    }
    $files->ensureDirectoryExists($destination . '/bootstrap/cache');
    file_put_contents($destination . '/bootstrap/cache/config.php', 'old generated config');
    file_put_contents($destination . '/obsolete.php', 'obsolete code');

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Deploy to Webhost'), $this->deployTestDirectory);
    expect($process->isSuccessful())->toBeTrue();
    $arguments = file($this->deployTestDirectory . '/rsync_arguments', FILE_IGNORE_NEW_LINES);
    $arguments[count($arguments) - 2] = $source . '/';
    $arguments[count($arguments) - 1] = $destination . '/';
    (new Process([$rsync, ...$arguments]))->mustRun();

    foreach ($runtimeFiles as $path => $contents) {
        expect(file_get_contents($destination . '/' . $path))->toBe($contents);
    }
    expect(file_get_contents($destination . '/index.php'))->toBe('new code');
    expect(file_get_contents($destination . '/app/storage/service.php'))->toBe('application code');
    expect(file_exists($destination . '/obsolete.php'))->toBeFalse();
    expect(file_exists($destination . '/bootstrap/cache/config.php'))->toBeFalse();
    expect(is_link($destination . '/storage'))->toBe($sharedStorage);
})->with([
    'storage absent from checkout' => [false, false],
    'storage present in checkout' => [true, false],
    'shared storage symlink' => [false, true],
]);

it('creates runtime directories before composer and can run repeatedly', function (bool $existingStorage) {
    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    $files = new Filesystem();
    $files->makeDirectory($deploymentPath, 0700, true);
    if ($existingStorage) {
        $files->makeDirectory($deploymentPath . '/storage/app/public', 0700, true);
        file_put_contents($deploymentPath . '/storage/app/public/upload.txt', 'existing upload');
    }

    $composer = <<<'SH'
#!/bin/sh
set -e
for directory in storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; do
    if [ ! -d "$directory" ] || [ ! -w "$directory" ]; then
        printf 'Missing or unwritable runtime directory: %s\n' "$directory" >&2
        exit 23
    fi
done
printf '%s\n' "$*" >> "$COMPOSER_ARGUMENTS_FILE"
SH;
    file_put_contents($this->deployTestDirectory . '/bin/composer', $composer);
    chmod($this->deployTestDirectory . '/bin/composer', 0700);
    file_put_contents($this->deployTestDirectory . '/bin/php', "#!/bin/sh\nexit 0\n");
    chmod($this->deployTestDirectory . '/bin/php', 0700);

    for ($run = 0; $run < 2; $run++) {
        $process = runDeployWorkflowCommands(
            deployWorkflowCommands('Post-deployment tasks'),
            $this->deployTestDirectory,
            ['COMPOSER_ARGUMENTS_FILE' => $this->deployTestDirectory . '/composer_arguments'],
        );
        expect($process->isSuccessful())->toBeTrue();
        expect($process->getErrorOutput())->toBe('');
    }

    $arguments = file($this->deployTestDirectory . '/composer_arguments', FILE_IGNORE_NEW_LINES);
    expect($arguments)->toHaveCount(4);
    expect($arguments[0])->toStartWith('update ');
    expect($arguments[1])->toBe('dumpautoload');
    if ($existingStorage) {
        expect(file_get_contents($deploymentPath . '/storage/app/public/upload.txt'))->toBe('existing upload');
    }
})->with([
    'missing storage' => [false],
    'existing storage' => [true],
]);

it('stops deployment when a runtime directory cannot be created', function () {
    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    (new Filesystem())->makeDirectory($deploymentPath . '/storage', 0700, true);
    file_put_contents($deploymentPath . '/storage/framework', 'not a directory');
    file_put_contents($this->deployTestDirectory . '/bin/composer', "#!/bin/sh\n: > \"\$COMPOSER_INVOKED_FILE\"\n");
    chmod($this->deployTestDirectory . '/bin/composer', 0700);
    file_put_contents($this->deployTestDirectory . '/bin/php', "#!/bin/sh\nexit 0\n");
    chmod($this->deployTestDirectory . '/bin/php', 0700);

    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Post-deployment tasks'),
        $this->deployTestDirectory,
        ['COMPOSER_INVOKED_FILE' => $this->deployTestDirectory . '/composer_invoked'],
    );

    expect($process->getExitCode())->not->toBe(0);
    expect(file_exists($this->deployTestDirectory . '/composer_invoked'))->toBeFalse();
});
