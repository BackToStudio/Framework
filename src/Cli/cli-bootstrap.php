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

use BackTo\Framework\Cli\Command\MakePostTypeCommand;
use BackTo\Framework\Cli\Command\MakeTaxonomyCommand;
use BackTo\Framework\Cli\Command\MakeBlockCommand;
use BackTo\Framework\Cli\Command\MakeHookCommand;
use BackTo\Framework\Cli\Command\MakeRestRouteCommand;

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
