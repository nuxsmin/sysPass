<?php

declare(strict_types=1);
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2024, Rubén Domínguez nuxsmin@$syspass.org
 *
 * This file is part of sysPass.
 *
 * sysPass is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysPass is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Domain\Config\Legacy;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use SP\Domain\Common\Adapters\SerializedPayloadReader;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Legacy\LegacyConfigData;
use SP\Domain\Config\Legacy\LegacyConfigDataMapper;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Tests\Stubs\DurablePayloadFixtures;

/**
 * Class LegacyConfigDataMapperTest
 */
#[Group('unitary')]
final class LegacyConfigDataMapperTest extends TestCase
{
    private const LEGACY_PROPERTIES = [
        'accountCount',
        'accountExpireEnabled',
        'accountExpireTime',
        'accountFullGroupAccess',
        'accountLink',
        'accountPassToImage',
        'applicationUrl',
        'appVersion',
        'authBasicAutoLoginEnabled',
        'authBasicDomain',
        'authBasicEnabled',
        'backup_hash',
        'checknotices',
        'checkUpdates',
        'configDate',
        'configHash',
        'configSaver',
        'configVersion',
        'databaseVersion',
        'dbHost',
        'dbName',
        'dbPass',
        'dbPort',
        'dbSocket',
        'dbUser',
        'debug',
        'demoEnabled',
        'dokuwikiEnabled',
        'dokuwikiNamespace',
        'dokuwikiPass',
        'dokuwikiUrl',
        'dokuwikiUrlBase',
        'dokuwikiUser',
        'encryptSession',
        'export_hash',
        'filesAllowedExts',
        'filesAllowedMime',
        'filesAllowedSize',
        'filesEnabled',
        'globalSearch',
        'httpsEnabled',
        'installed',
        'ldapAds',
        'ldapBase',
        'ldapBindPass',
        'ldapBindUser',
        'ldapDefaultGroup',
        'ldapDefaultProfile',
        'ldapEnabled',
        'ldapGroup',
        'ldapProxyUser',
        'ldapServer',
        'ldapTlsEnabled',
        'ldapType',
        'logEnabled',
        'logEvents',
        'mailAuthenabled',
        'mailEnabled',
        'mailEvents',
        'mailFrom',
        'mailPass',
        'mailPort',
        'mailRecipients',
        'mailRequestsEnabled',
        'mailSecurity',
        'mailServer',
        'mailUser',
        'maintenance',
        'passwordSalt',
        'proxyEnabled',
        'proxyPass',
        'proxyPort',
        'proxyServer',
        'proxyUser',
        'publinksEnabled',
        'publinksImageEnabled',
        'publinksMaxTime',
        'publinksMaxViews',
        'resultsAsCards',
        'sessionTimeout',
        'siteLang',
        'siteTheme',
        'ssoDefaultGroup',
        'ssoDefaultProfile',
        'syslogEnabled',
        'syslogPort',
        'syslogRemoteEnabled',
        'syslogServer',
        'upgradeKey',
        'wikiEnabled',
        'wikiFilter',
        'wikiPageurl',
        'wikiSearchurl',
    ];

    #[Test]
    public function everyLegacyPropertyIsRepresentedAndMapped(): void
    {
        $declared = (new LegacyConfigData())->toArray();
        $ignored = ['configHash'];

        foreach (self::LEGACY_PROPERTIES as $property) {
            self::assertArrayHasKey($property, $declared);

            $direct = LegacyConfigDataMapper::DIRECT[$property] ?? null;
            $mapped = LegacyConfigDataMapper::SETTERS[$property] ?? null;

            if ($direct !== null) {
                self::assertSame($property, $direct);
            } elseif ($mapped !== null) {
                self::assertContains(
                    $mapped[0],
                    (new \ReflectionClass(ConfigDataInterface::class))->getConstants(),
                    sprintf('Mapping does not use a ConfigDataInterface constant: %s', $property)
                );
                self::assertTrue(
                    method_exists(ConfigData::class, $mapped[1]),
                    sprintf('Setter does not exist for %s', $property)
                );
            } else {
                self::assertTrue(
                    in_array($property, LegacyConfigDataMapper::DROPPED, true)
                    || in_array($property, LegacyConfigDataMapper::DROPPED, true)
                    || in_array($property, $ignored, true),
                    sprintf('Legacy property is not mapped, dropped, or ignored: %s', $property)
                );
            }
        }
    }

    #[Test]
    public function mapsTheAuthenticLegacyConfigDataFixture(): void
    {
        [$config, $result] = self::mapFixture();

        self::assertSame('syspass-db-dev', $config->getDbHost());
        self::assertSame(3306, $config->getDbPort());
        self::assertSame(['PDF', 'JPG', 'PNG', 'TXT'], $config->getFilesAllowedExts());
        self::assertSame(['application/pdf', 'image/png'], $config->getFilesAllowedMime());
        self::assertSame('c9142988b9d91a375ca83387f0593c7a12eaf5a2', $config->getBackupHash());
        self::assertSame('export-hash', $config->getExportHash());
        self::assertTrue($config->isCheckNotices());
        self::assertSame(1, $config->getLdapType());
        self::assertSame(25, $config->getMailPort());
        self::assertSame('material-blue', $config->getSiteTheme());
        self::assertSame(['ldapAds', 'ldapProxyUser'], $result['dropped']);
        self::assertSame([], $result['unknown']);
    }

    #[Test]
    public function mapsAliasesAndCastsValues(): void
    {
        [$config] = self::map([
            'dbPort' => '3307',
            'dokuwikiEnabled' => 1,
            'checknotices' => 1,
            'backup_hash' => 'backup-hash',
            'export_hash' => 'export-hash',
            'filesAllowedExts' => 'PDF',
        ]);

        self::assertSame(3307, $config->getDbPort());
        self::assertTrue($config->isDokuwikiEnabled());
        self::assertTrue($config->isCheckNotices());
        self::assertSame('backup-hash', $config->getBackupHash());
        self::assertSame('export-hash', $config->getExportHash());
        self::assertSame(['PDF'], $config->getFilesAllowedExts());
    }

    #[Test]
    public function reportsDroppedAndUnknownProperties(): void
    {
        [, $result] = self::map([
            'dbHost' => 'localhost',
            'ldapAds' => true,
            'ldapProxyUser' => 'proxy-user',
            'ldapUserattr' => 'uid',
            'unknownProperty' => 'value',
        ]);

        self::assertSame(['ldapAds', 'ldapProxyUser'], $result['dropped']);
        self::assertSame(['ldapUserattr', 'unknownProperty'], $result['unknown']);
    }

    #[Test]
    public function usesTheLegacyDefaultsForMissingProperties(): void
    {
        [$config] = self::map(['dbHost' => 'localhost']);

        self::assertSame(25, $config->getMailPort());
        self::assertSame(8080, $config->getProxyPort());
        self::assertSame(3306, $config->getDbPort());
        self::assertSame(12, $config->getAccountCount());
        self::assertSame(300, $config->getSessionTimeout());
        self::assertSame('material-blue', $config->getSiteTheme());
        self::assertSame([], $config->getFilesAllowedExts());
        self::assertTrue($config->isFilesEnabled());
    }

    /**
     * @param array<string, mixed> $properties
     * @return array{ConfigData, array{dropped: string[], unknown: string[]}}
     */
    private static function map(array $properties): array
    {
        $legacy = SerializedPayloadReader::read(
            self::legacyPayload($properties),
            LegacyConfigData::class
        );

        self::assertInstanceOf(LegacyConfigData::class, $legacy);

        $config = new ConfigData();

        return [$config, LegacyConfigDataMapper::map($legacy, $config)];
    }

    /**
     * @return array{ConfigData, array{dropped: string[], unknown: string[]}}
     */
    private static function mapFixture(): array
    {
        $legacy = SerializedPayloadReader::read(
            DurablePayloadFixtures::configData(),
            LegacyConfigData::class
        );

        self::assertInstanceOf(LegacyConfigData::class, $legacy);

        $config = new ConfigData();

        return [$config, LegacyConfigDataMapper::map($legacy, $config)];
    }

    /**
     * @param array<string, mixed> $properties
     */
    private static function legacyPayload(array $properties): string
    {
        $body = '';

        foreach ($properties as $name => $value) {
            $key = "\0SP\\Config\\ConfigData\0" . $name;
            $body .= 's:' . strlen($key) . ':"' . $key . '";' . serialize($value);
        }

        return 'O:20:"SP\Config\ConfigData":' . count($properties) . ':{' . $body . '}';
    }
}
