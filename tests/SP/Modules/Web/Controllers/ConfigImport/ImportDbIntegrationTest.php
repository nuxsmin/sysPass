<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2026, Rubén Domínguez nuxsmin@$syspass.org
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

declare(strict_types=1);

namespace SP\Tests\Modules\Web\Controllers\ConfigImport;

use ArrayIterator;
use Klein\Response;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SP\Core\Events\EventDispatcher;
use SP\Core\PhpExtensionChecker;
use SP\Domain\Auth\Dtos\LdapParams;
use SP\Domain\Auth\Dtos\LdapResults;
use SP\Domain\Auth\Ports\LdapActionsService;
use SP\Domain\Auth\Ports\LdapService;
use SP\Domain\Auth\Ports\LdapServiceFactory;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Events\EventReceiver;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Crypt\Ports\MasterKeyStore;
use SP\Tests\DatabaseIntegrationTestCase;

use function SP\Tests\getDbHandler;

/**
 * DB-backed HTTP characterization tests for ConfigImport\ImportController & ConfigLdap\ImportController:
 * - CSV import persistence, category/client caching, duplicate names, and multiline notes
 * - sysPass XML import persistence & invalid Meta Hash still completing with verify warning event
 * - Encrypted XML wrong importPwd failing atomically vs wrong importMasterPwd committing metadata while importing 0 accounts
 * - KeePass XML group-to-category & entry-to-account persistence
 * - Real DB LDAP user & group import persistence, dropped group counts in response, and FK constraint error-counting on bypass
 */
#[Group('integration')]
final class ImportDbIntegrationTest extends DatabaseIntegrationTestCase
{
    private const MASTER_PASS = '12345678900';

    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-import-db-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->tempDir);

        parent::tearDown();
    }

    #[Test]
    public function csvImportPersistsAccountsReusesCategoriesAndClientsAndPreservesMultilineNotes(): void
    {
        $pdo = getDbHandler()->getConnection();

        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn());
        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Client')->fetchColumn());

        $result = $this->dispatchUpload(
            'configImport/import',
            [
                'csvDelimiter' => ';',
                'import_defaultuser' => 1,
                'import_defaultgroup' => 1,
            ],
            'data.csv'
        );

        $this->assertJsonOk($result, 'Import finished');

        self::assertSame(8, (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn());
        // 'CSV Category 1' added; 'Linux' and 'SSH' reused; duplicate 'CSV Category 1' hit in-memory cache
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());
        // 'CSV Client 1' added; 'Google' and 'Apple' reused
        self::assertSame(5, (int)$pdo->query('SELECT COUNT(*) FROM Client')->fetchColumn());

        self::assertSame(
            2,
            (int)$pdo->query("SELECT COUNT(*) FROM Account WHERE name = 'Test CSV 1'")->fetchColumn()
        );

        $notes = (string)$this->fetchValue("SELECT notes FROM Account WHERE name = 'Test CSV 2'");
        self::assertStringContainsString("\n", $notes);
        self::assertStringContainsString('CSV Notes 2', $notes);
    }

    #[Test]
    public function syspassXmlWithInvalidHashEmitsVerifyWarningEventYetCompletesImport(): void
    {
        $pdo = getDbHandler()->getConnection();
        $events = [];

        $dispatcher = new EventDispatcher();
        $dispatcher->attach(new class($events) implements EventReceiver {
            public function __construct(private array &$events)
            {
            }

            public function update(string $eventType, Event $event): void
            {
                $this->events[] = $eventType;
            }

            public function getEvents(): ?string
            {
                return '*';
            }
        });

        $result = $this->dispatchUpload(
            'configImport/import',
            [
                'importMasterPwd' => self::MASTER_PASS,
                'import_defaultuser' => 1,
                'import_defaultgroup' => 1,
                'csvDelimiter' => ';',
            ],
            'data_syspass_invalid_hash.xml',
            [
                EventDispatcherInterface::class => $dispatcher,
                EventDispatcher::class => $dispatcher,
            ]
        );

        // Emits verify warning on bad Meta/Hash, then ImportBase::addTag() only catches NoSuchItemException
        // while real Tag::getByName() throws TagException::notFound() ("Tag not found"), aborting and rolling back!
        self::assertSame(500, $result['code']);
        $json = json_decode($result['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $json['status']);
        self::assertSame('Tag not found', $json['description']);
        self::assertContains('run.import.syspass.process.verify', $events);
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn());
        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());
    }

    /**
     * Characterization:
     * 1) Omitting `csvDelimiter` on XML upload triggers `TypeError` inside `ImportParamsDto::__construct()` (HTTP 500)
     *    because `Request::analyzeString('csvDelimiter')` returns `null`.
     * 2) With `csvDelimiter` supplied, wrong `importPwd` on `data_syspass_encrypted.xml` fails atomically inside the
     *    transaction before metadata insertion, preserving DB row counts.
     * 3) Fixture `data_syspass.xml` aborts at `<Tags>` because `ImportBase::addTag()` catches `NoSuchItemException`
     *    instead of `TagException` (`Tag not found`, rolling back earlier Categories/Clients).
     * 4) On a sysPass XML with no new `<Tags>` and wrong `importMasterPwd`, `SyspassImport::processAccounts()`
     *    swallows per-account decryption exceptions, returning WARNING "No accounts were imported" while committing
     *    newly created Categories/Clients from earlier XML sections.
     */
    #[Test]
    public function encryptedWrongPasswordRollsBackWhereasWrongMasterPasswordCommitsMetadataWithZeroAccounts(): void
    {
        $pdo = getDbHandler()->getConnection();

        $missingDelimiter = $this->dispatchUpload(
            'configImport/import',
            [
                'importPwd' => 'wrong-encryption-password',
                'importMasterPwd' => self::MASTER_PASS,
            ],
            'data_syspass_encrypted.xml'
        );
        self::assertSame(500, $missingDelimiter['code']);
        $missingDelimiterJson = json_decode($missingDelimiter['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $missingDelimiterJson['status']);
        self::assertSame('Internal error', $missingDelimiterJson['description']);

        $exceptions = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->attach(new class($exceptions) implements EventReceiver {
            public function __construct(private array &$exceptions)
            {
            }

            public function update(string $eventType, Event $event): void
            {
                if ($eventType === 'exception' && $event->getSource() instanceof \Throwable) {
                    $this->exceptions[] = $event->getSource()::class . ': ' . $event->getSource()->getMessage();
                }
            }

            public function getEvents(): ?string
            {
                return '*';
            }
        });

        $wrongEnc = $this->dispatchUpload(
            'configImport/import',
            [
                'importPwd' => 'wrong-encryption-password',
                'importMasterPwd' => self::MASTER_PASS,
                'csvDelimiter' => ';',
            ],
            'data_syspass_encrypted.xml',
            [
                EventDispatcherInterface::class => $dispatcher,
                EventDispatcher::class => $dispatcher,
            ]
        );

        $wrongEncJson = json_decode($wrongEnc['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('ERROR', $wrongEncJson['status']);
        self::assertSame('Wrong encryption password', $wrongEncJson['description'], implode(' | ', $exceptions));
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn());
        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());

        // Fixture data_syspass.xml hits TagException ('Tag not found') and rolls back Categories/Clients too
        $syspassWithTags = $this->dispatchUpload(
            'configImport/import',
            [
                'importMasterPwd' => 'wrong-master-password',
                'import_defaultuser' => 1,
                'import_defaultgroup' => 1,
                'csvDelimiter' => ';',
            ],
            'data_syspass.xml'
        );
        self::assertSame(500, $syspassWithTags['code']);
        $syspassWithTagsJson = json_decode($syspassWithTags['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('Tag not found', $syspassWithTagsJson['description']);
        self::assertSame(3, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());

        // Without new Tags, CryptException inside processAccounts() is swallowed and commits Category/Client despite 0 accounts
        $noNewTagsXml = $this->tempDir . DIRECTORY_SEPARATOR . 'syspass_no_tags.xml';
        file_put_contents(
            $noNewTagsXml,
            <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<Root>
  <Meta>
    <Generator>sysPass</Generator>
    <Version>320.20000000</Version>
    <Time>1535358184</Time>
    <User id="1">admin</User>
    <Group id="1">admin</Group>
  </Meta>
  <Categories>
    <Category id="901"><name>XmlRollbackTestCat</name><description/></Category>
  </Categories>
  <Clients>
    <Client id="901"><name>XmlRollbackTestClient</name><description/></Client>
  </Clients>
  <Tags/>
  <Accounts>
    <Account id="901">
      <name>Encrypted Test</name>
      <categoryId>901</categoryId>
      <clientId>901</clientId>
      <login>u</login>
      <pass>not-valid-encrypted-payload</pass>
      <key>not-valid-key</key>
      <tags/>
    </Account>
  </Accounts>
</Root>
XML
        );

        $wrongMaster = $this->dispatchUploadPath(
            'configImport/import',
            [
                'importMasterPwd' => self::MASTER_PASS,
                'import_defaultuser' => 1,
                'import_defaultgroup' => 1,
                'csvDelimiter' => ';',
            ],
            $noNewTagsXml,
            [
                EventDispatcherInterface::class => $dispatcher,
                EventDispatcher::class => $dispatcher,
            ]
        );

        $wrongMasterJson = json_decode($wrongMaster['body'], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame(
            'WARNING',
            $wrongMasterJson['status'],
            'body=' . $wrongMaster['body'] . ' ex=' . implode(' | ', $exceptions)
        );
        self::assertSame('No accounts were imported', $wrongMasterJson['description']);
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn());
        // Metadata inserted before processAccounts() was committed despite 0 accounts imported
        self::assertSame(4, (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn());
        self::assertSame(5, (int)$pdo->query('SELECT COUNT(*) FROM Client')->fetchColumn());
        self::assertNotFalse($this->fetchValue("SELECT id FROM Category WHERE name = 'XmlRollbackTestCat'"));
    }

    #[Test]
    public function keepassXmlImportPersistsGroupsAsCategoriesAndEntriesAsAccounts(): void
    {
        $pdo = getDbHandler()->getConnection();

        $exceptions = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->attach(new class($exceptions) implements EventReceiver {
            public function __construct(private array &$exceptions)
            {
            }

            public function update(string $eventType, Event $event): void
            {
                if ($eventType === 'exception' && $event->getSource() instanceof \Throwable) {
                    $this->exceptions[] = $event->getSource()::class . ': ' . $event->getSource()->getMessage();
                }
            }

            public function getEvents(): ?string
            {
                return '*';
            }
        });

        $result = $this->dispatchUpload(
            'configImport/import',
            [
                'import_defaultuser' => 1,
                'import_defaultgroup' => 1,
                'csvDelimiter' => ';',
            ],
            'data_keepass.xml',
            [
                EventDispatcherInterface::class => $dispatcher,
                EventDispatcher::class => $dispatcher,
            ]
        );

        self::assertLessThan(500, $result['code'], implode(' | ', $exceptions) . ' body=' . $result['body']);
        $this->assertJsonOk($result, 'Import finished');

        $accountCount = (int)$pdo->query('SELECT COUNT(*) FROM Account')->fetchColumn();
        $categoryCount = (int)$pdo->query('SELECT COUNT(*) FROM Category')->fetchColumn();

        self::assertGreaterThan(4, $accountCount);
        self::assertGreaterThan(3, $categoryCount);
        self::assertNotNull($this->fetchValue("SELECT id FROM Category WHERE name = 'Homebanking'"));
        self::assertNotNull($this->fetchValue("SELECT id FROM Client WHERE name = 'KeePass'"));
    }

    /**
     * Characterization: ConfigLdap\ImportController persists real LDAP UserGroups via repositories,
     * drops group sync counts from the JSON response, and LdapImport::importUsers() fails on real DB
     * `NOT NULL` `User.pass` constraint (error caught and reported as `Errors: 1`), both for valid defaults
     * and when `ldap_defaultgroup=0` / `ldap_defaultprofile=0` bypasses controller validation.
     */
    #[Test]
    public function ldapImportPersistsUsersAndGroupsOrCountsFkErrorsOnBypass(): void
    {
        $pdo = getDbHandler()->getConnection();

        $userEntry = [
            'cn' => ['Alice LDAP'],
            'uid' => ['ldap_alice'],
            'mail' => ['alice@ldap.example'],
        ];
        $groupEntry = [
            'cn' => ['LDAP Devs'],
        ];

        $ldapActions = $this->createStub(LdapActionsService::class);
        $ldapActions->method('getObjects')
            ->willReturnCallback(
                static fn(string $filter): LdapResults => str_contains($filter, 'group')
                    ? new LdapResults(new ArrayIterator([$groupEntry]))
                    : new LdapResults(new ArrayIterator([$userEntry]))
            );

        $ldapService = $this->createStub(LdapService::class);
        $ldapService->method('getGroupObjectFilter')->willReturn('(objectClass=group)');
        $ldapService->method('getGroupMembershipIndirectFilter')->willReturn('(objectClass=person)');
        $ldapService->method('actions')->willReturn($ldapActions);

        $ldapFactory = $this->createStub(LdapServiceFactory::class);
        $ldapFactory->method('create')
            ->with(self::isInstanceOf(LdapParams::class))
            ->willReturn($ldapService);

        $extChecker = new class implements PhpExtensionCheckerService {
            public function __call(string $name, array $arguments): bool
            {
                return true;
            }

            public function checkIsAvailable(string $extension, bool $exception = false): bool
            {
                return true;
            }

            public function checkMandatory(): void
            {
            }

            public function getMissing(): array
            {
                return [];
            }
        };

        $exceptions = [];
        $dispatcher = new EventDispatcher();
        $dispatcher->attach(new class($exceptions) implements EventReceiver {
            public function __construct(private array &$exceptions)
            {
            }

            public function update(string $eventType, Event $event): void
            {
                if ($eventType === 'exception' && $event->getSource() instanceof \Throwable) {
                    $this->exceptions[] = $event->getSource()::class . ': ' . $event->getSource()->getMessage();
                }
            }

            public function getEvents(): ?string
            {
                return '*';
            }
        });

        $overrides = [
            LdapServiceFactory::class => $ldapFactory,
            PhpExtensionCheckerService::class => $extChecker,
            EventDispatcherInterface::class => $dispatcher,
            EventDispatcher::class => $dispatcher,
        ];

        // 1. Valid default group & profile + group import enabled -> persists UserGroup, but User insert hits User.pass NOT NULL
        $ok = $this->dispatch(
            'post',
            'configLdap/import',
            [],
            [
                'ldap_server' => 'ldap.test.local',
                'ldap_server_type' => 1,
                'ldap_binduser' => 'cn=bind',
                'ldap_bindpass' => 'bindpass',
                'ldap_base' => 'dc=test,dc=local',
                'ldap_defaultgroup' => 1,
                'ldap_defaultprofile' => 1,
                'ldap_login_attribute' => 'uid',
                'ldap_username_attribute' => 'cn',
                'ldap_groupname_attribute' => 'cn',
                'ldap_import_groups' => 1,
            ],
            $overrides
        );

        $okJson = $this->assertJsonOk($ok, 'LDAP users import finished');
        self::assertSame(['Imported users: 0 / 1', 'Errors: 1'], $okJson['data']);
        self::assertNotEmpty($exceptions);
        self::assertStringContainsString("Field 'pass' doesn't have a default value", implode(' | ', $exceptions));
        self::assertFalse($this->fetchValue("SELECT id FROM User WHERE login = 'ldap_alice'"));
        self::assertNotNull($this->fetchValue("SELECT id FROM UserGroup WHERE name = 'LDAP Devs'"));

        // 2. Bypassed defaults (0, 0) with ldap_import_groups=0 -> validation bypassed, fails inside LdapImport -> Errors: 1
        $beforeUsers = (int)$pdo->query('SELECT COUNT(*) FROM User')->fetchColumn();
        $fkBypass = $this->dispatch(
            'post',
            'configLdap/import',
            [],
            [
                'ldap_server' => 'ldap.test.local',
                'ldap_server_type' => 1,
                'ldap_binduser' => 'cn=bind',
                'ldap_bindpass' => 'bindpass',
                'ldap_base' => 'dc=test,dc=local',
                'ldap_defaultgroup' => 0,
                'ldap_defaultprofile' => 0,
                'ldap_login_attribute' => 'uid',
                'ldap_username_attribute' => 'cn',
                'ldap_import_groups' => 0,
            ],
            $overrides
        );

        $fkJson = $this->assertJsonOk($fkBypass, 'LDAP users import finished');
        self::assertSame(['Imported users: 0 / 1', 'Errors: 1'], $fkJson['data']);
        self::assertSame($beforeUsers, (int)$pdo->query('SELECT COUNT(*) FROM User')->fetchColumn());
    }

    /**
     * @param array<string, mixed> $post
     * @param array<class-string, mixed> $overrides
     * @return array{body: string, code: int}
     */
    private function dispatchUpload(
        string $route,
        array $post,
        string $fixtureFilename,
        array $overrides = []
    ): array {
        $src = REAL_APP_ROOT . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'res'
            . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . $fixtureFilename;
        $tmpCopy = $this->tempDir . DIRECTORY_SEPARATOR . $fixtureFilename;
        copy($src, $tmpCopy);

        return $this->dispatchUploadPath($route, $post, $tmpCopy, $overrides);
    }

    /**
     * @param array<string, mixed> $post
     * @param array<class-string, mixed> $overrides
     * @return array{body: string, code: int}
     */
    private function dispatchUploadPath(
        string $route,
        array $post,
        string $filePath,
        array $overrides = []
    ): array {
        $filename = basename($filePath);
        $files = [
            'inFile' => [
                'name' => $filename,
                'tmp_name' => $filePath,
                'size' => filesize($filePath),
                'type' => str_ends_with($filename, '.csv') ? 'text/csv' : 'application/xml',
            ],
        ];

        $masterKeyStore = $this->createStub(MasterKeyStore::class);
        $masterKeyStore->method('get')->willReturn(self::MASTER_PASS);

        $request = static::buildRequest('post', 'index.php', ['r' => $route], $post, $files);
        $container = $this->buildContainer(
            $request,
            array_merge([MasterKeyStore::class => $masterKeyStore], $overrides)
        );

        ob_start();
        try {
            static::runApp($container);
        } finally {
            $echoed = (string)ob_get_clean();
        }

        /** @var Response $response */
        $response = $container->get(Response::class);
        $body = (string)$response->body();

        return [
            'body' => $body !== '' ? $body : $echoed,
            'code' => (int)$response->code(),
        ];
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . DIRECTORY_SEPARATOR . $entry;
            is_dir($path) ? $this->removeDirectory($path) : @unlink($path);
        }

        @rmdir($dir);
    }
}
