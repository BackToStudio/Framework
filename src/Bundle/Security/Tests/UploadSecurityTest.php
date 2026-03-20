<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\UploadSecurity;
use PHPUnit\Framework\TestCase;

class UploadSecurityTest extends TestCase
{
    private UploadSecurity $rule;

    protected function setUp(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->rule = new UploadSecurity($dispatcher);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('upload_security', $this->rule->getName());
    }

    public function testHooksRegistersFilters(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['upload_mimes', 'wp_handle_upload_prefilter']);
            });

        $rule = new UploadSecurity($dispatcher);
        $rule->hooks();
    }

    public function testRestrictMimeTypesRemovesDangerousExtensions(): void
    {
        $mimes = [
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png' => 'image/png',
            'php' => 'application/x-httpd-php',
            'exe' => 'application/x-msdownload',
        ];

        $result = $this->rule->restrictMimeTypes($mimes);

        $this->assertArrayHasKey('jpg|jpeg|jpe', $result);
        $this->assertArrayHasKey('png', $result);
        $this->assertArrayNotHasKey('php', $result);
        $this->assertArrayNotHasKey('exe', $result);
    }

    public function testRestrictMimeTypesRemovesCompoundKeysWithDangerousExtension(): void
    {
        $mimes = [
            'php|phtml' => 'application/x-httpd-php',
            'jpg|jpeg' => 'image/jpeg',
        ];

        $result = $this->rule->restrictMimeTypes($mimes);

        $this->assertArrayNotHasKey('php|phtml', $result);
        $this->assertArrayHasKey('jpg|jpeg', $result);
    }

    public function testHasDangerousExtension(): void
    {
        $this->assertTrue($this->rule->hasDangerousExtension('malware.php'));
        $this->assertTrue($this->rule->hasDangerousExtension('script.phtml'));
        $this->assertTrue($this->rule->hasDangerousExtension('HACK.EXE'));
        $this->assertFalse($this->rule->hasDangerousExtension('photo.jpg'));
        $this->assertFalse($this->rule->hasDangerousExtension('document.pdf'));
    }

    public function testHasDoubleExtension(): void
    {
        $this->assertTrue($this->rule->hasDoubleExtension('image.php.jpg'));
        $this->assertTrue($this->rule->hasDoubleExtension('file.exe.pdf'));
        $this->assertFalse($this->rule->hasDoubleExtension('photo.jpg'));
        $this->assertFalse($this->rule->hasDoubleExtension('archive.tar.gz'));
    }

    public function testValidateUploadBlocksDangerousFile(): void
    {
        $file = [
            'name' => 'shell.php',
            'type' => 'application/x-httpd-php',
            'tmp_name' => '/tmp/phpXXXX',
            'error' => UPLOAD_ERR_OK,
            'size' => 100,
        ];

        $result = $this->rule->validateUpload($file);

        $this->assertSame('This file type is not allowed for security reasons.', $result['error']);
    }

    public function testValidateUploadBlocksDoubleExtension(): void
    {
        $file = [
            'name' => 'image.php.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/phpXXXX',
            'error' => UPLOAD_ERR_OK,
            'size' => 100,
        ];

        $result = $this->rule->validateUpload($file);

        $this->assertSame('Files with multiple extensions are not allowed.', $result['error']);
    }

    public function testValidateUploadSkipsFileWithErrors(): void
    {
        $file = [
            'name' => 'file.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '',
            'error' => UPLOAD_ERR_NO_FILE,
            'size' => 0,
        ];

        $result = $this->rule->validateUpload($file);

        $this->assertSame(UPLOAD_ERR_NO_FILE, $result['error']);
    }

    public function testValidateMagicBytesWithValidJpeg(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, "\xFF\xD8\xFF\xE0" . str_repeat('x', 100));

        $this->assertTrue($this->rule->validateMagicBytes($tmpFile, 'photo.jpg'));

        unlink($tmpFile);
    }

    public function testValidateMagicBytesWithInvalidJpeg(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, '<?php echo "hacked"; ?>');

        $this->assertFalse($this->rule->validateMagicBytes($tmpFile, 'photo.jpg'));

        unlink($tmpFile);
    }

    public function testValidateMagicBytesWithValidPng(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, "\x89PNG\r\n\x1a\n" . str_repeat('x', 100));

        $this->assertTrue($this->rule->validateMagicBytes($tmpFile, 'image.png'));

        unlink($tmpFile);
    }

    public function testValidateMagicBytesSkipsUnknownExtensions(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, 'any content');

        $this->assertTrue($this->rule->validateMagicBytes($tmpFile, 'data.csv'));

        unlink($tmpFile);
    }

    public function testValidateUploadChecksMagicBytes(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, '<?php echo "hacked"; ?>');

        $file = [
            'name' => 'photo.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 100,
        ];

        $result = $this->rule->validateUpload($file);

        $this->assertSame('File content does not match its extension.', $result['error']);

        unlink($tmpFile);
    }

    public function testValidateMagicBytesRejectsEmptyFile(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tmpFile, '');

        $this->assertFalse($this->rule->validateMagicBytes($tmpFile, 'photo.jpg'));
        $this->assertFalse($this->rule->validateMagicBytes($tmpFile, 'image.png'));
        $this->assertFalse($this->rule->validateMagicBytes($tmpFile, 'doc.pdf'));

        unlink($tmpFile);
    }

    public function testIsSvgSafeBlocksScriptInCdata(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">'
             . '<![CDATA[<script>alert("xss")</script>]]></svg>';
        file_put_contents($tmpFile, $svg);

        $this->assertFalse($this->rule->isSvgSafe($tmpFile));

        unlink($tmpFile);
    }

    public function testIsSvgSafeBlocksEventHandlerInCdata(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">'
             . '<![CDATA[<div onload="alert(1)">]]></svg>';
        file_put_contents($tmpFile, $svg);

        $this->assertFalse($this->rule->isSvgSafe($tmpFile));

        unlink($tmpFile);
    }

    public function testIsSvgSafeBlocksJavascriptUriInCdata(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">'
             . '<![CDATA[<a href="javascript:alert(1)">click</a>]]></svg>';
        file_put_contents($tmpFile, $svg);

        $this->assertFalse($this->rule->isSvgSafe($tmpFile));

        unlink($tmpFile);
    }

    public function testIsSvgSafeAllowsSafeSvg(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_');
        $svg = '<?xml version="1.0"?><svg xmlns="http://www.w3.org/2000/svg">'
             . '<rect width="100" height="100" fill="red"/></svg>';
        file_put_contents($tmpFile, $svg);

        $this->assertTrue($this->rule->isSvgSafe($tmpFile));

        unlink($tmpFile);
    }
}
