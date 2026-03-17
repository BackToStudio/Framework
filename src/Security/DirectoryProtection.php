<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DirectoryProtection implements Hooks, ActivationHooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    private const HTACCESS_CONTENT = <<<'HTACCESS'
# Disable directory browsing
Options -Indexes

# Deny access to PHP files
<Files "*.php">
    <IfModule mod_authz_core.c>
        Require all denied
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order deny,allow
        Deny from all
    </IfModule>
</Files>

# Allow index.php (WordPress needs it for some operations)
<Files "index.php">
    <IfModule mod_authz_core.c>
        Require all granted
    </IfModule>
    <IfModule !mod_authz_core.c>
        Order allow,deny
        Allow from all
    </IfModule>
</Files>
HTACCESS;

    private const INDEX_CONTENT = "<?php\n// Silence is golden.\n";

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'directory_protection';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_init', [$this, 'ensureProtection']);
    }

    public function activate(): void
    {
        $this->ensureProtection();
    }

    public function ensureProtection(): void
    {
        $uploadDir = $this->getUploadDir();

        if ($uploadDir === null) {
            return;
        }

        $this->writeProtectionFile($uploadDir . '/.htaccess', self::HTACCESS_CONTENT);
        $this->writeProtectionFile($uploadDir . '/index.php', self::INDEX_CONTENT);
    }

    
    public function getProtectedPaths(): array
    {
        $uploadDir = $this->getUploadDir();

        if ($uploadDir === null) {
            return [];
        }

        return [
            $uploadDir . '/.htaccess',
            $uploadDir . '/index.php',
        ];
    }

    protected function writeProtectionFile(string $path, string $content): bool
    {
        if (file_exists($path)) {
            return true;
        }

        $dir = dirname($path);

        if (!is_dir($dir)) {
            return false;
        }

        $result = file_put_contents($path, $content);

        return $result !== false;
    }

    protected function getUploadDir(): ?string
    {
        if (!function_exists('wp_upload_dir')) {
            return null;
        }

        $uploadDir = wp_upload_dir();

        return $uploadDir['basedir'];
    }
}
