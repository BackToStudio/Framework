<?php

declare(strict_types=1);

/**
 * WP-CLI command registration for BackTo Framework.
 *
 * Include this file in your plugin/theme to register all scaffolding commands:
 *
 *     if (defined('WP_CLI') && WP_CLI) {
 *         require_once __DIR__ . '/vendor/backto/framework/src/Cli/cli-bootstrap.php';
 *     }
 */

if (!\class_exists('WP_CLI')) {
    return;
}

use BackTo\Framework\Cli\Command\HealthCheckCommand;
use BackTo\Framework\Cli\Command\MaintenanceCommand;
use BackTo\Framework\Cli\Command\MakePostTypeCommand;
use BackTo\Framework\Cli\Command\MakeTaxonomyCommand;
use BackTo\Framework\Cli\Command\MakeBlockCommand;
use BackTo\Framework\Cli\Command\MakeHookCommand;
use BackTo\Framework\Cli\Command\MakeRestRouteCommand;
use BackTo\Framework\Cli\Command\GenerateServerConfigCommand;
use BackTo\Framework\Cli\Command\QueueStatusCommand;

WP_CLI::add_command('make:post-type', MakePostTypeCommand::class, [
    'shortdesc' => 'Generate a PostType class.',
    'synopsis' => [
        ['type' => 'positional', 'name' => 'name', 'description' => 'The post type name (e.g. Event)', 'optional' => false],
        ['type' => 'assoc', 'name' => 'namespace', 'description' => 'PHP namespace', 'optional' => true, 'default' => 'App'],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory', 'optional' => true, 'default' => '.'],
        ['type' => 'flag', 'name' => 'force', 'description' => 'Overwrite existing file', 'optional' => true],
    ],
]);

WP_CLI::add_command('make:taxonomy', MakeTaxonomyCommand::class, [
    'shortdesc' => 'Generate a Taxonomy class.',
    'synopsis' => [
        ['type' => 'positional', 'name' => 'name', 'description' => 'The taxonomy name (e.g. EventCategory)', 'optional' => false],
        ['type' => 'assoc', 'name' => 'namespace', 'description' => 'PHP namespace', 'optional' => true, 'default' => 'App'],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory', 'optional' => true, 'default' => '.'],
        ['type' => 'assoc', 'name' => 'post-types', 'description' => 'Comma-separated post types', 'optional' => true],
        ['type' => 'flag', 'name' => 'force', 'description' => 'Overwrite existing file', 'optional' => true],
    ],
]);

WP_CLI::add_command('make:block', MakeBlockCommand::class, [
    'shortdesc' => 'Generate a Block class.',
    'synopsis' => [
        ['type' => 'positional', 'name' => 'name', 'description' => 'The block name (e.g. Hero)', 'optional' => false],
        ['type' => 'assoc', 'name' => 'namespace', 'description' => 'PHP namespace', 'optional' => true, 'default' => 'App'],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory', 'optional' => true, 'default' => '.'],
        ['type' => 'assoc', 'name' => 'block-namespace', 'description' => 'Block namespace prefix', 'optional' => true, 'default' => 'custom'],
        ['type' => 'flag', 'name' => 'force', 'description' => 'Overwrite existing file', 'optional' => true],
    ],
]);

WP_CLI::add_command('make:hook', MakeHookCommand::class, [
    'shortdesc' => 'Generate a Hook class.',
    'synopsis' => [
        ['type' => 'positional', 'name' => 'name', 'description' => 'The hook class name (e.g. RegisterSidebars)', 'optional' => false],
        ['type' => 'assoc', 'name' => 'namespace', 'description' => 'PHP namespace', 'optional' => true, 'default' => 'App'],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory', 'optional' => true, 'default' => '.'],
        ['type' => 'flag', 'name' => 'force', 'description' => 'Overwrite existing file', 'optional' => true],
    ],
]);

WP_CLI::add_command('make:rest-route', MakeRestRouteCommand::class, [
    'shortdesc' => 'Generate a REST Route class.',
    'synopsis' => [
        ['type' => 'positional', 'name' => 'name', 'description' => 'The route class name (e.g. GetEvents)', 'optional' => false],
        ['type' => 'assoc', 'name' => 'namespace', 'description' => 'PHP namespace', 'optional' => true, 'default' => 'App'],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory', 'optional' => true, 'default' => '.'],
        ['type' => 'assoc', 'name' => 'route-namespace', 'description' => 'REST API namespace', 'optional' => true, 'default' => 'app/v1'],
        ['type' => 'flag', 'name' => 'force', 'description' => 'Overwrite existing file', 'optional' => true],
    ],
]);

// --- Operations commands ---

WP_CLI::add_command('backto:health', HealthCheckCommand::class, [
    'shortdesc' => 'Run all health checks and display results.',
    'synopsis' => [
        ['type' => 'assoc', 'name' => 'format', 'description' => 'Output format', 'optional' => true, 'default' => 'table', 'options' => ['table', 'json', 'csv']],
    ],
]);

WP_CLI::add_command('backto:queue', QueueStatusCommand::class, [
    'shortdesc' => 'Display queue status overview.',
    'synopsis' => [
        ['type' => 'assoc', 'name' => 'format', 'description' => 'Output format', 'optional' => true, 'default' => 'table', 'options' => ['table', 'json', 'csv']],
    ],
]);

WP_CLI::add_command('backto:maintenance', MaintenanceCommand::class, [
    'shortdesc' => 'Run maintenance tasks (DB cleanup, queue rescue, metric purge).',
    'synopsis' => [
        ['type' => 'flag', 'name' => 'db-only', 'description' => 'Only run database cleanup', 'optional' => true],
        ['type' => 'flag', 'name' => 'purge-metrics', 'description' => 'Purge old metrics', 'optional' => true],
        ['type' => 'assoc', 'name' => 'days', 'description' => 'Days to retain for metric purge', 'optional' => true, 'default' => '30'],
        ['type' => 'flag', 'name' => 'optimize-tables', 'description' => 'Run OPTIMIZE TABLE', 'optional' => true],
    ],
]);

WP_CLI::add_command('backto:generate-server-config', GenerateServerConfigCommand::class, [
    'shortdesc' => 'Generate Nginx/Apache bot protection configuration.',
    'synopsis' => [
        ['type' => 'assoc', 'name' => 'server', 'description' => 'Web server type (nginx, apache, both)', 'optional' => true, 'default' => 'nginx', 'options' => ['nginx', 'apache', 'both']],
        ['type' => 'assoc', 'name' => 'output', 'description' => 'Output mode (stdout, file)', 'optional' => true, 'default' => 'stdout', 'options' => ['stdout', 'file']],
        ['type' => 'assoc', 'name' => 'dir', 'description' => 'Output directory when using --output=file', 'optional' => true, 'default' => '.'],
        ['type' => 'assoc', 'name' => 'blocked-ips', 'description' => 'Comma-separated IPs/CIDRs to block', 'optional' => true],
        ['type' => 'assoc', 'name' => 'extra-bots', 'description' => 'Additional User-Agent names to block', 'optional' => true],
    ],
]);
