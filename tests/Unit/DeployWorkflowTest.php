<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * Get the shell commands for a deployment workflow step.
 */
function deployWorkflowCommands(string $name): string
{
    $workflow = Yaml::parseFile(dirname(__DIR__, 2) . '/.github/workflows/deploy.yml');

    foreach ($workflow['jobs']['deploy']['steps'] as $step) {
        if (($step['name'] ?? null) === $name && isset($step['run'])) {
            return $step['run'];
        }
    }

    throw new RuntimeException("Deployment step not found: {$name}");
}

/**
 * Run a deployment step using isolated files and test values for its secrets.
 *
 * @param  array<string, string>  $environment
 */
function runDeployWorkflowCommands(string $commands, string $directory, array $environment = []): Process
{
    $deploymentPath = $environment['SERVER_PATH'] ?? $directory . '/current deployment';
    $commands = strtr($commands, [
        '${{ secrets.SERVER_USER }}' => 'deployer',
        '${{ secrets.SERVER_HOST }}' => 'deploy.example.test',
        '${{ secrets.SERVER_PATH }}' => $deploymentPath,
        '${{ env.COMPOSER_COMMAND }}' => 'composer install --no-dev',
        '${{ env.PHP_COMMAND }}' => 'php',
        '/tmp/last_backup_path' => escapeshellarg($directory . '/last_backup_path'),
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
            'SSH_ARGUMENTS_FILE' => $directory . '/ssh_arguments',
            'RSYNC_ARGUMENTS_FILE' => $directory . '/rsync_arguments',
            'PHP_INVOKED_FILE' => $directory . '/php_invoked',
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
});

afterEach(function () {
    (new Filesystem())->deleteDirectory($this->deployTestDirectory);
});

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
    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    mkdir($deploymentPath);
    file_put_contents($deploymentPath . '/index.php', 'existing deployment');

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Backup current deployment'), $this->deployTestDirectory);

    expect($process->isSuccessful())->toBeTrue();
    $backupPath = trim(file_get_contents($this->deployTestDirectory . '/last_backup_path'));
    expect($backupPath)->toMatch('/^' . preg_quote($deploymentPath, '/') . '_backup_\d{8}_\d{6}$/');
    expect(file_get_contents($backupPath . '/current deployment/index.php'))->toBe('existing deployment');
    expect(file_get_contents($this->deployTestDirectory . '/ssh_arguments'))
        ->toContain('-T')
        ->toContain('BatchMode=yes')
        ->toContain('StrictHostKeyChecking=yes');
});

it('keeps the backup outside a deployment path with a trailing slash', function () {
    $deploymentPath = $this->deployTestDirectory . '/current deployment';
    mkdir($deploymentPath);
    file_put_contents($deploymentPath . '/index.php', 'existing deployment');

    $process = runDeployWorkflowCommands(
        deployWorkflowCommands('Backup current deployment'),
        $this->deployTestDirectory,
        ['SERVER_PATH' => $deploymentPath . '/'],
    );

    expect($process->isSuccessful())->toBeTrue();
    $backupPath = trim(file_get_contents($this->deployTestDirectory . '/last_backup_path'));
    expect($backupPath)->toMatch('/^' . preg_quote($deploymentPath, '/') . '_backup_\d{8}_\d{6}$/');
    expect(file_get_contents($backupPath . '/current deployment/index.php'))->toBe('existing deployment');
});

it('stops when the remote backup cannot be created', function () {
    $process = runDeployWorkflowCommands(deployWorkflowCommands('Backup current deployment'), $this->deployTestDirectory);

    expect($process->getExitCode())->not->toBe(0);
    expect(file_exists($this->deployTestDirectory . '/last_backup_path'))->toBeFalse();
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
    $rsync = <<<'SH'
#!/bin/sh
printf '%s\n' "$@" > "$RSYNC_ARGUMENTS_FILE"
SH;
    file_put_contents($this->deployTestDirectory . '/bin/rsync', $rsync);
    chmod($this->deployTestDirectory . '/bin/rsync', 0700);

    $process = runDeployWorkflowCommands(deployWorkflowCommands('Deploy to Webhost'), $this->deployTestDirectory);

    expect($process->isSuccessful())->toBeTrue();
    $arguments = file($this->deployTestDirectory . '/rsync_arguments', FILE_IGNORE_NEW_LINES);
    $sshIndex = array_search('-e', $arguments, true);
    expect($sshIndex)->not->toBeFalse();
    expect($arguments[$sshIndex + 1])->toContain('BatchMode=yes')->toContain('StrictHostKeyChecking=yes');
    expect(end($arguments))->toBe('deployer@deploy.example.test:' . $this->deployTestDirectory . '/current deployment');
});
