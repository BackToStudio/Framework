<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Tests;

use BackTo\Framework\PostMeta\Entity\PostMetaStructure;
use PHPUnit\Framework\TestCase;

class PostMetaStructureRegistrationArgsTest extends TestCase
{
    public function testToRegistrationArgsReturnsBasicArgs(): void
    {
        $structure = new PostMetaStructure();
        $structure->setMetaKey('test_meta');
        $structure->setObjectSubtype('post');
        $structure->setType('string');
        $structure->setLabel('Test Meta');
        $structure->setDescription('A test meta field');
        $structure->setSingle(true);
        $structure->showInRest();

        $args = $structure->toRegistrationArgs();

        $this->assertSame('post', $args['object_subtype']);
        $this->assertSame('string', $args['type']);
        $this->assertSame('Test Meta', $args['label']);
        $this->assertSame('A test meta field', $args['description']);
        $this->assertTrue($args['single']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertFalse($args['revisions_enabled']);
    }

    public function testToRegistrationArgsIncludesDefaultWhenSet(): void
    {
        $structure = new PostMetaStructure();
        $structure->setMetaKey('color');
        $structure->setDefault('blue');

        $args = $structure->toRegistrationArgs();

        $this->assertArrayHasKey('default', $args);
        $this->assertSame('blue', $args['default']);
    }

    public function testToRegistrationArgsExcludesDefaultWhenNull(): void
    {
        $structure = new PostMetaStructure();
        $structure->setMetaKey('color');

        $args = $structure->toRegistrationArgs();

        $this->assertArrayNotHasKey('default', $args);
    }

    public function testToRegistrationArgsIncludesSanitizeCallback(): void
    {
        $callback = static fn ($value) => sanitize_text_field($value);

        $structure = new PostMetaStructure();
        $structure->setMetaKey('title');
        $structure->setSanitizeCallback($callback);

        $args = $structure->toRegistrationArgs();

        $this->assertArrayHasKey('sanitize_callback', $args);
        $this->assertSame($callback, $args['sanitize_callback']);
    }

    public function testToRegistrationArgsExcludesSanitizeCallbackWhenNull(): void
    {
        $structure = new PostMetaStructure();
        $structure->setMetaKey('title');

        $args = $structure->toRegistrationArgs();

        $this->assertArrayNotHasKey('sanitize_callback', $args);
    }

    public function testToRegistrationArgsIncludesAuthCallback(): void
    {
        $callback = static fn () => current_user_can('edit_posts');

        $structure = new PostMetaStructure();
        $structure->setMetaKey('secret');
        $structure->setAuthCallback($callback);

        $args = $structure->toRegistrationArgs();

        $this->assertArrayHasKey('auth_callback', $args);
        $this->assertSame($callback, $args['auth_callback']);
    }

    public function testToRegistrationArgsWithRevisionsEnabled(): void
    {
        $structure = new PostMetaStructure();
        $structure->setMetaKey('versioned');
        $structure->setRevisionsEnabled(true);

        $args = $structure->toRegistrationArgs();

        $this->assertTrue($args['revisions_enabled']);
    }

    public function testToRegistrationArgsWithAllOptions(): void
    {
        $sanitize = static fn ($v) => $v;
        $auth = static fn () => true;

        $structure = new PostMetaStructure();
        $structure->setMetaKey('full_meta');
        $structure->setObjectSubtype('page');
        $structure->setType('integer');
        $structure->setLabel('Full Meta');
        $structure->setDescription('Complete meta field');
        $structure->setSingle(false);
        $structure->showInRest();
        $structure->setRevisionsEnabled(true);
        $structure->setDefault(42);
        $structure->setSanitizeCallback($sanitize);
        $structure->setAuthCallback($auth);

        $args = $structure->toRegistrationArgs();

        $this->assertSame('page', $args['object_subtype']);
        $this->assertSame('integer', $args['type']);
        $this->assertSame('Full Meta', $args['label']);
        $this->assertSame('Complete meta field', $args['description']);
        $this->assertFalse($args['single']);
        $this->assertTrue($args['show_in_rest']);
        $this->assertTrue($args['revisions_enabled']);
        $this->assertSame(42, $args['default']);
        $this->assertSame($sanitize, $args['sanitize_callback']);
        $this->assertSame($auth, $args['auth_callback']);
    }
}
