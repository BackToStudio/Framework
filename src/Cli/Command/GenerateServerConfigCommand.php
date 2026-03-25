<?php

declare(strict_types=1);

namespace BackTo\Framework\Cli\Command;

use BackTo\Framework\Cli\Contracts\CliOutputInterface;
use BackTo\Framework\Bundle\Security\Network\ServerConfigGenerator;

/**
 * WP-CLI command: wp backto:generate-server-config
 *
 * Generates Nginx or Apache configuration snippets for bot protection.
 * The generated files block malicious traffic at the web-server level,
 * before PHP is invoked.
 */
final class GenerateServerConfigCommand
{
    private readonly ServerConfigGenerator $generator;
    private readonly CliOutputInterface $output;

    public function __construct(ServerConfigGenerator $generator, CliOutputInterface $output)
    {
        $this->generator = $generator;
        $this->output = $output;
    }

    /**
     * Generate server configuration for bot protection.
     *
     * ## OPTIONS
     *
     * [--server=<server>]
     * : Web server type.
     * ---
     * default: nginx
     * options:
     *   - nginx
     *   - apache
     *   - both
     * ---
     *
     * [--output=<output>]
     * : Output mode: 'stdout' prints to terminal, 'file' writes to disk.
     * ---
     * default: stdout
     * options:
     *   - stdout
     *   - file
     * ---
     *
     * [--dir=<dir>]
     * : Output directory when using --output=file.
     * ---
     * default: .
     * ---
     *
     * [--blocked-ips=<ips>]
     * : Comma-separated IP addresses or CIDR ranges to block.
     *
     * [--extra-bots=<bots>]
     * : Comma-separated User-Agent names to block in addition to defaults.
     *
     * @param array<int, string> $args
     * @param array<string, string> $assocArgs
     */
    public function __invoke(array $args, array $assocArgs): void
    {
        $server = $assocArgs['server'] ?? 'nginx';
        $outputMode = $assocArgs['output'] ?? 'stdout';
        $dir = rtrim($assocArgs['dir'] ?? '.', '/');

        if (isset($assocArgs['blocked-ips'])) {
            $ips = array_filter(array_map('trim', explode(',', $assocArgs['blocked-ips'])));
            $this->generator->setBlockedIps($ips);
        }

        if (isset($assocArgs['extra-bots'])) {
            $bots = array_filter(array_map('trim', explode(',', $assocArgs['extra-bots'])));
            $this->generator->addBlockedUserAgents($bots);
        }

        $generated = [];

        if ($server === 'nginx' || $server === 'both') {
            $generated['nginx'] = [
                'content' => $this->generator->generateNginx(),
                'filename' => 'backto-bot-protection.conf',
            ];
        }

        if ($server === 'apache' || $server === 'both') {
            $generated['apache'] = [
                'content' => $this->generator->generateApache(),
                'filename' => '.htaccess-bot-protection',
            ];
        }

        foreach ($generated as $type => $data) {
            if ($outputMode === 'file') {
                $path = $dir . '/' . $data['filename'];

                if ($type === 'nginx') {
                    $written = $this->generator->writeNginx($path);
                } else {
                    $written = $this->generator->writeApache($path);
                }

                if ($written) {
                    $this->output->success(sprintf('%s config written to %s', ucfirst($type), $path));
                } else {
                    $this->output->error(sprintf('Failed to write %s config to %s', $type, $path));
                }
            } else {
                \WP_CLI::line(sprintf('# --- %s configuration ---', strtoupper($type)));
                \WP_CLI::line('');
                \WP_CLI::line($data['content']);
            }
        }
    }
}
