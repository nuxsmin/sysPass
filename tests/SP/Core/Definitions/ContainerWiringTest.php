<?php

declare(strict_types=1);

namespace SP\Tests\Core\Definitions;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SP\Tests\IntegrationTestCase;
use Throwable;

/**
 * Verifies that every Domain port has a working container binding.
 *
 * Ports that cannot be resolved yet are listed explicitly with the reason.
 * The list is a ratchet: adding a port without a binding fails the test, and
 * fixing a listed port fails until its entry is removed.
 */
#[Group('integration')]
final class ContainerWiringTest extends IntegrationTestCase
{
    private const PORTS_PATH = '/lib/SP/Domain';

    /**
     * Known unresolved ports, grouped by reason.
     *
     * @var array<string, string>
     */
    private const EXPECTED_UNRESOLVED = [
        // Module-specific adapters need request/base-url data or a module binding.
        'SP\Domain\Account\Ports\AccountAdapter' => 'module-specific baseUrl',
        'SP\Domain\Api\Ports\ApiRequestService' => 'API module binding',
        'SP\Domain\Api\Ports\ApiService' => 'API module binding',
        'SP\Domain\Api\Ports\HelpProvider' => 'module-specific help implementations',
        'SP\Domain\Category\Ports\CategoryAdapter' => 'module-specific baseUrl',
        'SP\Domain\Client\Ports\ClientAdapter' => 'module-specific baseUrl',
        'SP\Domain\CustomField\Ports\CustomFieldAdapter' => 'module-specific baseUrl',
        'SP\Domain\Html\Ports\MinifyService' => 'web module binding (MinifyJs/MinifyCss)',
        'SP\Domain\Http\Ports\JsonResponseService' => 'web/API response adapter',
        'SP\Domain\Plugin\Ports\Plugin' => 'plugin runtime',
        'SP\Domain\Plugin\Ports\PluginDataStorage' => 'plugin runtime',
        'SP\Domain\Plugin\Ports\PluginOperationInterface' => 'plugin runtime',
        'SP\Domain\Plugin\Ports\PluginUpgraderInterface' => 'plugin runtime',

        // Ports constructed with a runtime path or request data.
        'SP\Domain\Auth\Ports\LdapAuthService' => 'request-dependent',
        'SP\Domain\Auth\Ports\LdapService' => 'request-dependent',
        'SP\Domain\Auth\Ports\LoginAuthHandlerService' => 'request-dependent',
        'SP\Domain\Auth\Ports\LoginMasterPassService' => 'request-dependent',
        'SP\Domain\Auth\Ports\LoginService' => 'request-dependent',
        'SP\Domain\Auth\Ports\LoginUserService' => 'request-dependent',
        'SP\Domain\Crypt\Ports\SecureSessionService' => 'runtime cache path',
        'SP\Domain\File\Ports\ArchiveHandlerInterface' => 'runtime path',
        'SP\Domain\File\Ports\DirectoryHandlerService' => 'runtime path',
        'SP\Domain\File\Ports\FileHandlerInterface' => 'runtime path',
        'SP\Domain\Install\Ports\InstallerService' => 'request-dependent',
        'SP\Domain\Notification\Ports\MailerInterface' => 'request/config-dependent',
        'SP\Domain\Storage\Ports\XmlFileStorageService' => 'runtime path',
        'SP\Domain\Storage\Ports\YamlFileStorageService' => 'runtime path',

        // Marker, value object or multi-implementation ports, not single services.
        'SP\Domain\Account\Ports\AccountSearchConstants' => 'constants interface',
        'SP\Domain\Common\Ports\Dto' => 'value object interface',
        'SP\Domain\Database\Ports\QueryDataInterface' => 'value object interface',
        'SP\Domain\Export\Ports\XmlExportEntityService' => 'abstract/multiple implementations',
        'SP\Domain\Import\Ports\ItemsImportService' => 'multiple implementations',
        'SP\Domain\ItemPreset\Ports\ItemPresetInterface' => 'value object interface',
        'SP\Domain\ItemPreset\Ports\PresetInterface' => 'marker interface',
        'SP\Domain\Log\Ports\FileHandlerProvider' => 'marker interface',
        'SP\Domain\Log\Ports\ProviderInterface' => 'marker interface',

        // Broken wildcard bindings: the target class does not exist (P9-3).
        'SP\Domain\Auth\Ports\UpgradeAuthTokenService' => 'stale binding',
        'SP\Domain\CustomField\Ports\UpgradeCustomFieldDataService' => 'stale binding',
        'SP\Domain\CustomField\Ports\UpgradeCustomFieldDefinitionService' => 'stale binding',
        'SP\Domain\Export\Ports\BackupFileHelperService' => 'stale binding',
        'SP\Domain\Import\Ports\XmlImportService' => 'stale binding',
        'SP\Domain\Plugin\Ports\UpgradePluginService' => 'stale binding',
        'SP\Domain\Upgrade\Ports\UpgradeConfigService' => 'stale binding',
        'SP\Domain\Upgrade\Ports\UpgradeHandlerService' => 'stale binding',
        'SP\Domain\Upgrade\Ports\UpgradeService' => 'unresolved concrete service',
    ];

    #[Test]
    public function everyPortResolvesOrIsDocumented(): void
    {
        $container = $this->buildContainer(self::buildRequest('get', 'index.php'));
        $ports = $this->portInterfaces();

        self::assertNotEmpty($ports);

        $unexpectedErrors = [];
        $resolvedExpectedPorts = [];

        foreach ($ports as $port) {
            try {
                $container->get($port);

                if (isset(self::EXPECTED_UNRESOLVED[$port])) {
                    $resolvedExpectedPorts[] = $port;
                }
            } catch (Throwable $e) {
                if (!isset(self::EXPECTED_UNRESOLVED[$port])) {
                    $unexpectedErrors[$port] = $e->getMessage();
                }
            }
        }

        self::assertSame(
            [],
            $resolvedExpectedPorts,
            sprintf(
                "These ports now resolve. Remove them from EXPECTED_UNRESOLVED:\n%s",
                implode("\n", $resolvedExpectedPorts)
            )
        );

        self::assertSame(
            [],
            $unexpectedErrors,
            sprintf(
                "The container cannot resolve %d of %d Domain ports:\n%s",
                count($unexpectedErrors),
                count($ports),
                $this->formatErrors($unexpectedErrors)
            )
        );
    }

    /**
     * @return list<string>
     */
    private function portInterfaces(): array
    {
        $root = dirname(__DIR__, 4);
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $root . self::PORTS_PATH,
                RecursiveDirectoryIterator::SKIP_DOTS
            )
        );

        $ports = [];

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php'
                || !str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'Ports' . DIRECTORY_SEPARATOR)
            ) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($root) + 1, -4);
            $relative = preg_replace('#^lib/#', '', $relative);
            $interface = str_replace(DIRECTORY_SEPARATOR, '\\', $relative);

            if (interface_exists($interface)) {
                $ports[] = $interface;
            }
        }

        sort($ports);

        return $ports;
    }

    /**
     * @param array<string, string> $errors
     */
    private function formatErrors(array $errors): string
    {
        $lines = [];

        foreach ($errors as $port => $message) {
            $lines[] = sprintf('%s: %s', $port, $message);
        }

        return implode("\n", $lines);
    }
}
