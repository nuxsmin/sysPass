<?php
/*
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

namespace SP\Tests\Domain\Import\Services;

use DOMDocument;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use SP\Core\Messages\TextFormatter;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Common\Ports\Repository;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventDispatcherInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\Exceptions\NoSuchItemException;
use SP\Domain\Crypt\Adapters\HashServiceAdapter;
use SP\Domain\Crypt\Ports\HashService;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Domain\Import\Dtos\CsvImportParamsDto;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Ports\ImportStrategyService;
use SP\Domain\Import\Services\CsvImport;
use SP\Domain\Import\Services\Import;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportHelper;
use SP\Domain\Import\Services\ImportStrategy;
use SP\Domain\Import\Services\SyspassImport;
use SP\Domain\Import\Services\XmlFile;
use SP\Domain\Tag\Ports\TagService;
use SP\Infrastructure\File\FileHandler;
use SP\Tests\UnitaryTestCase;
use TypeError;
use UnhandledMatchError;

/**
 * Boundary characterization tests for Import strategy, Syspass XML integrity/decryption/tags,
 * ImportBase Master Password validation, and CsvImport/Import transaction atomicity.
 */
#[Group('unitary')]
final class ImportStrategyBoundaryTest extends UnitaryTestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'syspass-import-unit-' . bin2hex(random_bytes(6));
        mkdir($this->tempDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->tempDir) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..') {
                @unlink($this->tempDir . DIRECTORY_SEPARATOR . $entry);
            }
        }
        @rmdir($this->tempDir);

        parent::tearDown();
    }

    public static function allowedButUnhandledMimeProvider(): array
    {
        return [
            'spreadsheetml' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'ms-excel' => ['application/vnd.ms-excel'],
            'text/x-csv' => ['text/x-csv'],
        ];
    }

    /**
     * Characterization: ImportStrategy::ALLOWED_MIME accepts .xlsx, .xls, and text/x-csv,
     * yet fileTypeFactory()'s `match` expression lacks branches for them, throwing UnhandledMatchError.
     */
    #[Test]
    #[DataProvider('allowedButUnhandledMimeProvider')]
    public function allowedMimeWithoutFactoryBranchThrowsUnhandledMatchError(string $mimeType): void
    {
        $fileHandler = $this->createStub(FileHandlerInterface::class);
        $fileHandler->method('getFileType')->willReturn($mimeType);

        $strategy = new ImportStrategy(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(),
            $this->createStub(CryptInterface::class),
            new XmlFile(),
            new HashServiceAdapter()
        );

        $params = new ImportParamsDto($fileHandler, 1, 1);

        $this->expectException(UnhandledMatchError::class);
        $strategy->buildImport($params);
    }

    /**
     * Characterization: SyspassImport::checkIntegrity() emits `run.import.syspass.process.verify` on invalid
     * XML signature hash and continues importing categories, clients, and accounts instead of aborting.
     */
    #[Test]
    public function syspassImportContinuesOnTamperedIntegrityHash(): void
    {
        $xml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Root>
  <Meta>
    <Generator>sysPass</Generator>
    <Version>3200</Version>
    <Hash>0000000000000000000000000000000000000000</Hash>
  </Meta>
  <Categories>
    <Category id="10"><name>Web</name><description></description></Category>
  </Categories>
  <Clients>
    <Client id="20"><name>Acme</name><description></description></Client>
  </Clients>
  <Tags></Tags>
  <Accounts>
    <Account>
      <name>Portal</name>
      <categoryId>10</categoryId>
      <clientId>20</clientId>
      <login>alice</login>
      <pass>plain-or-cipher</pass>
      <key></key>
      <url>https://acme.example</url>
      <notes></notes>
      <tags></tags>
    </Account>
  </Accounts>
</Root>
XML;

        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xml);

        $events = [];
        $formatter = new TextFormatter();
        $dispatcher = $this->createStub(EventDispatcherInterface::class);
        $dispatcher->method('notify')->willReturnCallback(
            static function (string $name, Event $event) use (&$events, $formatter): void {
                $events[] = [
                    'name' => $name,
                    'desc' => $event->getEventMessage()?->getDescription($formatter, false),
                ];
            }
        );

        $categoryService = $this->createStub(CategoryService::class);
        $categoryService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $categoryService->method('create')->willReturn(101);

        $clientService = $this->createStub(ClientService::class);
        $clientService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $clientService->method('create')->willReturn(202);

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::once())->method('create')->willReturn(303);

        $importHelper = $this->makeImportHelper($accountService, $categoryService, $clientService);

        $importer = new SyspassImport(
            $this->config,
            $dispatcher,
            $this->context,
            $importHelper,
            $this->createStub(CryptInterface::class),
            new HashServiceAdapter(),
            $doc
        );

        $importer->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1));

        self::assertSame(1, $importer->getCounter());
        $verifyEvents = array_values(array_filter(
            $events,
            static fn(array $e): bool => $e['name'] === 'run.import.syspass.process.verify'
        ));
        self::assertCount(1, $verifyEvents);
        self::assertStringContainsString('Error while checking integrity hash', (string)$verifyEvents[0]['desc']);
    }

    #[Test]
    public function versionedMasterHashDecryptsImportedCredential(): void
    {
        $crypt = new \SP\Domain\Core\Crypt\Crypt();
        $key = $crypt->makeSecuredKey('synthetic master');
        $ciphertext = $crypt->encrypt('synthetic credential', $key, 'synthetic master');
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML('<Root><Meta><Generator>sysPass</Generator><Version>3200</Version></Meta>'
            . '<Categories><Category id="1"><name>Cat</name><description/></Category></Categories>'
            . '<Clients><Client id="2"><name>Cli</name><description/></Client></Clients>'
            . '<Accounts><Account><name>Entry</name><categoryId>1</categoryId><clientId>2</clientId>'
            . '<login>user</login><pass>' . htmlspecialchars($ciphertext, ENT_XML1) . '</pass><key>'
            . htmlspecialchars($key, ENT_XML1) . '</key><tags/></Account></Accounts></Root>');
        $category = $this->createStub(CategoryService::class);
        $category->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $category->method('create')->willReturn(11);
        $client = $this->createStub(ClientService::class);
        $client->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $client->method('create')->willReturn(22);
        $config = $this->createStub(ConfigService::class);
        $config->method('getByParam')->willReturn(\SP\Domain\Crypt\Services\Hash::hashMasterKey('synthetic master'));
        $account = $this->createMock(AccountService::class);
        $account->expects(self::once())->method('create')->with(self::callback(
            static fn(AccountCreateDto $dto): bool => $dto->pass === 'synthetic credential' && $dto->key === ''
        ))->willReturn(99);
        $importer = new SyspassImport($this->config, $this->eventDispatcherStub, $this->context,
            $this->makeImportHelper($account, $category, $client, $this->createStub(TagService::class), $config),
            $crypt, new HashServiceAdapter(), $doc);
        $importer->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, null, 'synthetic master'));
        self::assertSame(1, $importer->getCounter());
    }

    /**
     * Characterization:
     * 1) A mismatched master password must not create an account.
     * 2) When `<Account>` omits `<tags>`, SyspassImport::processAccounts() passes null $data['tags'] into
     *    AccountCreateDto::withTags(array $tags), raising uncaught TypeError (not caught by catch(Exception)).
     * 3) Pretty-printed XML parsed without `preserveWhiteSpace=false` fails CallbackFilterIterator parameter
     *    type `(DOMElement $element)` with TypeError when encountering DOMText whitespace nodes.
     */
    #[Test]
    public function syspassImportRejectsMismatchedMasterPasswordAndMissingTagsOrWhitespaceThrowsTypeError(): void
    {
        $xmlWithTags = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Root>
  <Meta><Generator>sysPass</Generator><Version>3200</Version></Meta>
  <Categories><Category id="1"><name>Cat</name><description></description></Category></Categories>
  <Clients><Client id="2"><name>Cli</name><description></description></Client></Clients>
  <Accounts>
    <Account>
      <name>EncryptedForeign</name>
      <categoryId>1</categoryId>
      <clientId>2</clientId>
      <login>bob</login>
      <pass>FOREIGN_CIPHERTEXT</pass>
      <key>FOREIGN_KEY</key>
      <url></url>
      <notes></notes>
      <tags></tags>
    </Account>
  </Accounts>
</Root>
XML;
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->loadXML($xmlWithTags);

        $categoryService = $this->createStub(CategoryService::class);
        $categoryService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $categoryService->method('create')->willReturn(11);

        $clientService = $this->createStub(ClientService::class);
        $clientService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $clientService->method('create')->willReturn(22);

        $configService = $this->createStub(ConfigService::class);
        $configService->method('getByParam')->with('masterPwd')->willReturn('db-master-hash');

        $hashService = $this->createStub(HashService::class);
        $hashService->method('checkMasterKey')->willReturn(false); // imported MP != DB MP

        $crypt = $this->createMock(CryptInterface::class);
        $crypt->expects(self::never())->method('decrypt');

        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::never())->method('create');

        $importer = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(
                $accountService,
                $categoryService,
                $clientService,
                $this->createStub(TagService::class),
                $configService
            ),
            $crypt,
            $hashService,
            $doc
        );

        try {
            $importer->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, null, 'foreign-mp'));
            self::fail('Expected invalid master password to fail');
        } catch (ImportException $e) {
            self::assertSame(0, $importer->getCounter());
        }

        // Missing <tags> child triggers TypeError uncaught by `catch (Exception)`
        $xmlWithoutTags = str_replace('<tags></tags>', '', $xmlWithTags);
        $docNoTags = new DOMDocument();
        $docNoTags->preserveWhiteSpace = false;
        $docNoTags->loadXML($xmlWithoutTags);

        $importerNoTags = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(
                $this->createStub(AccountService::class),
                $categoryService,
                $clientService,
                $this->createStub(TagService::class),
                $configService
            ),
            $crypt,
            $hashService,
            $docNoTags
        );

        try {
            @$importerNoTags->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1));
            self::fail('Expected TypeError when <tags> element is absent');
        } catch (TypeError $e) {
            self::assertStringContainsString('withTags', $e->getMessage());
        }

        // Default DOMDocument (preserveWhiteSpace=true) triggers DOMText TypeError in CallbackFilterIterator
        $docWithWhitespace = new DOMDocument();
        $docWithWhitespace->loadXML($xmlWithTags);
        $importerWhitespace = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(
                $this->createStub(AccountService::class),
                $categoryService,
                $clientService
            ),
            $crypt,
            $hashService,
            $docWithWhitespace
        );

        $this->expectException(TypeError::class);
        $importerWhitespace->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1));
    }

    /**
     * Characterization:
     * 1) SyspassImport::processEncrypted() throws ImportException('Encryption password not set') ONLY when
     *    $importParams->getPassword() === ''; with a non-empty password, CryptException inside processEncrypted()
     *    is caught and skipped (`continue`), returning counter=0 without throwing.
     * 2) On unencrypted XML, checkIntegrity() uses `$importParams->getPassword() ?? sha1(passwordSalt)`, so
     *    passing '' (as returned by Request::analyzeEncrypted('importPwd')) bypasses the sha1(passwordSalt) default.
     */
    #[Test]
    public function syspassImportProcessEncryptedSwallowsCryptExceptionAndUsesEmptyStringKey(): void
    {
        $encryptedXml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Root>
  <Meta><Generator>sysPass</Generator><Version>3200</Version><Hash>deadbeef</Hash></Meta>
  <Encrypted>
    <Data key="k1">CIPHER_NODE</Data>
  </Encrypted>
</Root>
XML;
        $encryptedDoc = new DOMDocument();
        $encryptedDoc->preserveWhiteSpace = false;
        $encryptedDoc->loadXML($encryptedXml);

        $crypt = $this->createMock(CryptInterface::class);
        $crypt->expects(self::once())
            ->method('decrypt')
            ->willThrowException(new CryptException('bad cipher'));

        $encryptedImporter = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(),
            $crypt,
            $this->createStub(HashService::class),
            $encryptedDoc
        );

        $encryptedImporter->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, 'wrong-pass'));
        self::assertSame(0, $encryptedImporter->getCounter());
        // processEncrypted() removed the <Encrypted> node even though CryptException skipped decryption
        self::assertSame(0, $encryptedDoc->getElementsByTagName('Encrypted')->length);

        // Empty-string password fails fast on a fresh encrypted XML document
        $freshEncryptedDoc = new DOMDocument();
        $freshEncryptedDoc->preserveWhiteSpace = false;
        $freshEncryptedDoc->loadXML($encryptedXml);
        $freshEncryptedImporter = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(),
            $this->createStub(CryptInterface::class),
            $this->createStub(HashService::class),
            $freshEncryptedDoc
        );
        try {
            $freshEncryptedImporter->doImport(
                new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, '')
            );
            self::fail('Expected ImportException when encrypted password is empty string');
        } catch (ImportException $e) {
            self::assertSame('Encryption password not set', $e->getMessage());
        }

        // Signed unencrypted XML with '' password passes '' instead of sha1(passwordSalt) to HashService::checkMessage()
        $plainXml = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<Root>
  <Meta><Generator>sysPass</Generator><Version>3200</Version><Hash sign="sig123">deadbeef</Hash></Meta>
  <Categories></Categories><Clients></Clients><Tags></Tags><Accounts></Accounts>
</Root>
XML;
        $plainDoc = new DOMDocument();
        $plainDoc->preserveWhiteSpace = false;
        $plainDoc->loadXML($plainXml);

        $checkedMessageKey = null;
        $hashService = $this->createStub(HashService::class);
        $hashService->method('checkMessage')->willReturnCallback(
            static function (string $message, string $key, string $mac) use (&$checkedMessageKey): bool {
                $checkedMessageKey = $key;

                return false;
            }
        );

        $plainImporter = new SyspassImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(),
            $this->createStub(CryptInterface::class),
            $hashService,
            $plainDoc
        );

        $plainImporter->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, ''));
        self::assertSame('', $checkedMessageKey);

        $plainImporter->doImport(new ImportParamsDto($this->createStub(FileHandlerInterface::class), 1, 1, null));
        self::assertSame(sha1($this->config->getConfigData()->getPasswordSalt()), $checkedMessageKey);
    }

    /**
     * Characterization:
     * - CsvImport with real FileHandler skips blank `[null]` lines, aborts immediately on wrong field count
     *   (outside try/catch), yet catches per-row exceptions (missing client or AccountService failure) so valid
     *   rows still execute inside Import::doImport()'s `transactionAware()` without triggering rollback.
     */
    #[Test]
    public function csvImportAndTransactionAtomicityPartialCommitCharacterization(): void
    {
        $csvFile = $this->tempDir . DIRECTORY_SEPARATOR . 'partial.csv';
        file_put_contents(
            $csvFile,
            "Acc1;Cli1;Cat1;https://1.example;u1;p1;n1\n\n"
            . "AccBad;;Cat2;https://bad.example;u2;p2;n2\n"
            . "Acc2;Cli1;Cat1;https://2.example;u3;p3;n3\n"
        );

        $categoryService = $this->createStub(CategoryService::class);
        $categoryService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $categoryService->method('create')->willReturn(10);

        $clientService = $this->createStub(ClientService::class);
        $clientService->method('getByName')->willThrowException(NoSuchItemException::error('missing'));
        $clientService->method('create')->willReturn(20);

        $createdNames = [];
        $accountService = $this->createMock(AccountService::class);
        $accountService->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(static function (AccountCreateDto $dto) use (&$createdNames): int {
                $createdNames[] = $dto->name;
                if ($dto->name === 'Acc2') {
                    throw new RuntimeException('db write failure on Acc2');
                }

                return 1;
            });

        $csvImport = new CsvImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper($accountService, $categoryService, $clientService),
            $this->createStub(CryptInterface::class),
            new HashServiceAdapter(),
            new FileHandler($csvFile)
        );

        $committed = false;
        $repository = $this->createMock(Repository::class);
        $repository->expects(self::once())
            ->method('transactionAware')
            ->willReturnCallback(static function (callable $fn) use (&$committed) {
                $res = $fn();
                $committed = true; // no exception propagated -> transaction commits partial import

                return $res;
            });

        $strategy = $this->createStub(ImportStrategyService::class);
        $strategy->method('buildImport')->willReturn($csvImport);

        $import = new Import(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $strategy,
            $repository
        );

        $params = new CsvImportParamsDto(new FileHandler($csvFile), 1, 1);
        $res = $import->doImport($params);

        self::assertTrue($committed);
        self::assertSame(['Acc1', 'Acc2'], $createdNames);
        self::assertSame(1, $res->getCounter());

        // Malformed column count line throws outside try/catch and aborts
        $badCols = $this->tempDir . DIRECTORY_SEPARATOR . 'bad_cols.csv';
        file_put_contents($badCols, "only;two;cols\n");
        $csvImportBadCols = new CsvImport(
            $this->config,
            $this->eventDispatcherStub,
            $this->context,
            $this->makeImportHelper(),
            $this->createStub(CryptInterface::class),
            new HashServiceAdapter(),
            new FileHandler($badCols)
        );

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('Wrong number of fields (3)');
        $csvImportBadCols->doImport(new CsvImportParamsDto(new FileHandler($badCols), 1, 1));
    }

    private function makeImportHelper(
        ?AccountService $accountService = null,
        ?CategoryService $categoryService = null,
        ?ClientService $clientService = null,
        ?TagService $tagService = null,
        ?ConfigService $configService = null
    ): ImportHelper {
        return new ImportHelper(
            $accountService ?? $this->createStub(AccountService::class),
            $categoryService ?? $this->createStub(CategoryService::class),
            $clientService ?? $this->createStub(ClientService::class),
            $tagService ?? $this->createStub(TagService::class),
            $configService ?? $this->createStub(ConfigService::class)
        );
    }
}
