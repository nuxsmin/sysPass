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

namespace SP\Tests\Domain\Import\Services;

use DOMDocument;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use RuntimeException;
use SP\Core\Crypt\Crypt;
use SP\Domain\Account\Dtos\AccountCreateDto;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Category\Models\Category;
use SP\Domain\Category\Ports\CategoryService;
use SP\Domain\Client\Models\Client;
use SP\Domain\Client\Ports\ClientService;
use SP\Domain\Config\Ports\ConfigService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Import\Dtos\ImportParamsDto;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportHelper;
use SP\Domain\Import\Services\SyspassImport;
use SP\Domain\Tag\Models\Tag;
use SP\Domain\Tag\Ports\TagService;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;
use SP\Tests\UnitaryTestCase;

/**
 * Class SyspassImportTest
 *
 */
#[Group('unitary')]
class SyspassImportTest extends UnitaryTestCase
{

    private const SYSPASS_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                 'data_syspass.xml';

    private const SYSPASS_ENCRYPTED_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                           'data_syspass_encrypted.xml';

    private AccountService|MockObject  $accountService;
    private MockObject|CategoryService $categoryService;
    private ClientService|MockObject   $clientService;
    private TagService|MockObject      $tagService;
    private CryptInterface|MockObject  $crypt;
    private SyspassImport              $sysPassImport;
    private ConfigService|MockObject   $configService;

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithNoMasterPassword()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->clientService
            ->expects(self::exactly(4))
            ->method('create')
            ->with(self::callback(static fn(Client $client) => !empty($client->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 3)));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     empty($category->getDescription())
                )
            )
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 4)));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Debian'], ['JBoss'], ['MySQL'], ['server'], ['SSH'], ['www'])
            )
            ->willThrowException(NoSuchItemException::error('test'));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('create')
            ->with(self::callback(static fn(Tag $tag) => !empty($tag->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 6)));

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $accountCounter = new InvokedCount(5);

        $this->accountService
            ->expects($accountCounter)
            ->method('create')
            ->with(
                self::callback(function (AccountCreateDto $dto) use ($accountCounter) {
                    return $dto->clientId > 0
                           && $dto->categoryId > 0
                           && $dto->userId === 100
                           && $dto->userGroupId === 200
                           && !empty($dto->pass)
                           && !empty($dto->key)
                           && $this->getCommonAccountMatcher($accountCounter, $dto);
                })
            );

        $out = $this->sysPassImport->doImport($importParamsDto);

        $this->assertEquals(5, $out->getCounter());
    }

    /**
     * @param InvokedCount $accountCounter
     * @param AccountCreateDto $dto
     * @return bool
     */
    protected function getCommonAccountMatcher(InvokedCount $accountCounter, AccountCreateDto $dto): bool
    {
        $tagsCount = count(array_filter($dto->tags ?? [], static fn($value) => is_int($value)));

        return match ($accountCounter->numberOfInvocations()) {
            1 => $tagsCount === 3
                 && $dto->name === 'Google'
                 && $dto->login === 'admin'
                 && $dto->url === 'https://google.com'
                 && empty($dto->notes),
            2 => $tagsCount === 3
                 && $dto->name === 'Google'
                 && $dto->login === 'admin'
                 && $dto->url === 'https://google.com'
                 && $dto->notes === 'blablacar',
            3 => $tagsCount === 0
                 && $dto->name === 'Test CSV 1'
                 && $dto->login === 'csv_login1'
                 && $dto->url === 'http://test.me'
                 && $dto->notes === 'CSV Notes',
            4 => $tagsCount === 0
                 && $dto->name === 'Test CSV 2'
                 && $dto->login === 'csv_login2'
                 && $dto->url === 'http://linux.org'
                 && str_starts_with($dto->notes, 'CSV Notes 2'),
            5 => $tagsCount === 0
                 && $dto->name === 'Test CSV 3'
                 && $dto->login === 'csv_login2'
                 && $dto->url === 'http://apple.com'
                 && $dto->notes === 'CSV Notes 3',
        };
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithMasterPassword()
    {
        $importParamsDto = $this->createMock(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);
        $importParamsDto->expects(self::atLeast(7))
                        ->method('getMasterPassword')
                        ->willReturn('a_password');

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->clientService
            ->expects(self::exactly(4))
            ->method('create')
            ->with(self::callback(static fn(Client $client) => !empty($client->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 3)));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     empty($category->getDescription())
                )
            )
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 4)));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Debian'], ['JBoss'], ['MySQL'], ['server'], ['SSH'], ['www'])
            )
            ->willThrowException(NoSuchItemException::error('test'));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('create')
            ->with(self::callback(static fn(Tag $tag) => !empty($tag->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 6)));

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willReturn(password_hash('a_password', PASSWORD_BCRYPT));

        $this->crypt
            ->expects(self::exactly(5))
            ->method('decrypt')
            ->with(self::anything(), self::anything(), 'a_password')
            ->willReturn('super_secret');

        $accountCounter = new InvokedCount(5);

        $this->accountService
            ->expects($accountCounter)
            ->method('create')
            ->with(
                self::callback(function (AccountCreateDto $dto) use ($accountCounter) {
                    return $dto->clientId > 0
                           && $dto->categoryId > 0
                           && $dto->userId === 100
                           && $dto->userGroupId === 200
                           && $dto->pass === 'super_secret'
                           && empty($dto->key)
                           && $this->getCommonAccountMatcher($accountCounter, $dto);
                })
            );

        $out = $this->sysPassImport->doImport($importParamsDto);

        $this->assertEquals(5, $out->getCounter());
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithItemsByName()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willReturn(...array_map(static fn() => new Client(['id' => self::$faker->randomNumber(3)]), range(0, 3)));

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willReturn(
                ...array_map(static fn() => new Category(['id' => self::$faker->randomNumber(3)]), range(0, 4))
            );

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->tagService
            ->expects(self::exactly(7))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Debian'], ['JBoss'], ['MySQL'], ['server'], ['SSH'], ['www'])
            )
            ->willReturn(...array_map(static fn() => new Tag(['id' => self::$faker->randomNumber(3)]), range(0, 6)));

        $this->tagService
            ->expects(self::never())
            ->method('create');

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $accountCounter = new InvokedCount(5);

        $this->accountService
            ->expects($accountCounter)
            ->method('create')
            ->with(
                self::callback(function (AccountCreateDto $dto) use ($accountCounter) {
                    return $dto->clientId > 0
                           && $dto->categoryId > 0
                           && $dto->userId === 100
                           && $dto->userGroupId === 200
                           && !empty($dto->pass)
                           && !empty($dto->key)
                           && $this->getCommonAccountMatcher($accountCounter, $dto);
                })
            );

        $out = $this->sysPassImport->doImport($importParamsDto);

        $this->assertEquals(5, $out->getCounter());
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithMasterPasswordAndNoConfigHash()
    {
        $importParamsDto = $this->createMock(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);
        $importParamsDto->expects(self::atLeast(2))
                        ->method('getMasterPassword')
                        ->willReturn('a_password');

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->clientService
            ->expects(self::exactly(4))
            ->method('create')
            ->with(self::callback(static fn(Client $client) => !empty($client->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 3)));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     empty($category->getDescription())
                )
            )
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 4)));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Debian'], ['JBoss'], ['MySQL'], ['server'], ['SSH'], ['www'])
            )
            ->willThrowException(NoSuchItemException::error('test'));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('create')
            ->with(self::callback(static fn(Tag $tag) => !empty($tag->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 6)));

        $this->configService
            ->expects(self::once())
            ->method('getByParam')
            ->with('masterPwd')
            ->willThrowException(NoSuchItemException::error('test'));

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $accountCounter = new InvokedCount(5);

        $this->accountService
            ->expects($accountCounter)
            ->method('create')
            ->with(
                self::callback(function (AccountCreateDto $dto) use ($accountCounter) {
                    return $dto->clientId > 0
                           && $dto->categoryId > 0
                           && $dto->userId === 100
                           && $dto->userGroupId === 200
                           && !empty($dto->pass)
                           && !empty($dto->key)
                           && $this->getCommonAccountMatcher($accountCounter, $dto);
                })
            );

        $out = $this->sysPassImport->doImport($importParamsDto);

        $this->assertEquals(5, $out->getCounter());
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithTagException()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willReturn(...array_map(static fn() => new Client(['id' => self::$faker->randomNumber(3)]), range(0, 3)));

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willReturn(
                ...array_map(static fn() => new Category(['id' => self::$faker->randomNumber(3)]), range(0, 4))
            );

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->tagService
            ->expects(self::once(1))
            ->method('getByName')
            ->with('Apache')
            ->willThrowException(new RuntimeException('test'));

        $this->tagService
            ->expects(self::never())
            ->method('create');

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('test');

        $this->sysPassImport->doImport($importParamsDto);
    }

    /**
     * @throws Exception
     */
    public function testDoImportWithCategoryException()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::never())
            ->method('getByName');

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::once())
            ->method('getByName')
            ->with('CSV Category 1')
            ->willThrowException(new RuntimeException('test'));

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->tagService
            ->expects(self::never(1))
            ->method('getByName');

        $this->tagService
            ->expects(self::never())
            ->method('create');

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('test');

        $this->sysPassImport->doImport($importParamsDto);
    }

    /**
     * @throws Exception
     */
    public function testDoImportWithClientException()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::once())
            ->method('getByName')
            ->with('Apple')
            ->willThrowException(new RuntimeException('test'));

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willReturn(
                ...array_map(static fn() => new Category(['id' => self::$faker->randomNumber(3)]), range(0, 4))
            );

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->tagService
            ->expects(self::never(1))
            ->method('getByName');

        $this->tagService
            ->expects(self::never())
            ->method('create');

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $this->expectException(ImportException::class);
        $this->expectExceptionMessage('test');

        $this->sysPassImport->doImport($importParamsDto);
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithEncryptedFile()
    {
        $importHelper = new ImportHelper(
            $this->accountService,
            $this->categoryService,
            $this->clientService,
            $this->tagService,
            $this->configService
        );

        $document = new DOMDocument();
        $document->load(self::SYSPASS_ENCRYPTED_FILE, LIBXML_NOBLANKS);

        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getPassword')->willReturn('test_encrypt');
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::exactly(3))
            ->method('getByName')
            ->with(...self::withConsecutive(['Amazon'], ['Apple'], ['Google']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->clientService
            ->expects(self::exactly(3))
            ->method('create')
            ->with(self::callback(static fn(Client $client) => !empty($client->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 3)));

        $this->categoryService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['AWS'], ['GCP'], ['SSH'], ['Web']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->categoryService
            ->expects(self::exactly(4))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     empty($category->getDescription())
                )
            )
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 4)));

        $this->tagService
            ->expects(self::exactly(6))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Email'], ['JBoss'], ['SaaS'], ['SSH'], ['Tomcat'])
            )
            ->willThrowException(NoSuchItemException::error('test'));

        $this->tagService
            ->expects(self::exactly(6))
            ->method('create')
            ->with(self::callback(static fn(Tag $tag) => !empty($tag->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 6)));

        $this->crypt
            ->expects(self::exactly(4))
            ->method('decrypt')
            ->with(self::anything(), self::anything(), 'test_encrypt')
            ->willReturnCallback(static function (string $encrypted, string $key) {
                return (new Crypt())->decrypt($encrypted, $key, 'test_encrypt');
            });

        $accountCounter = new InvokedCount(2);

        $this->accountService
            ->expects($accountCounter)
            ->method('create')
            ->with(
                self::callback(function (AccountCreateDto $dto) use ($accountCounter) {
                    $tagsCount = count(array_filter($dto->tags ?? [], static fn($value) => is_int($value)));

                    $accountMatcher = match ($accountCounter->numberOfInvocations()) {
                        1 => $tagsCount === 1
                             && $dto->name === 'Amazon SES'
                             && $dto->login === 'admin'
                             && $dto->url === 'https://aws.amazon.com/'
                             && $dto->notes === 'Simple Email Service',
                        2 => $tagsCount === 1
                             && $dto->name === 'Google GCP'
                             && $dto->login === 'admin'
                             && $dto->url === 'https://cloud.google.com/'
                             && $dto->notes === 'Google Cloud'
                    };

                    return $dto->clientId > 0
                           && $dto->categoryId > 0
                           && $dto->userId === 100
                           && $dto->userGroupId === 200
                           && !empty($dto->pass)
                           && !empty($dto->key)
                           && $accountMatcher;
                })
            );

        $sysPassImport = new SyspassImport($this->application, $importHelper, $this->crypt, $document);

        $out = $sysPassImport->doImport($importParamsDto);

        $this->assertEquals(2, $out->getCounter());
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithEncryptedFileAndCryptoException()
    {
        $importHelper = new ImportHelper(
            $this->accountService,
            $this->categoryService,
            $this->clientService,
            $this->tagService,
            $this->configService
        );

        $document = new DOMDocument();
        $document->load(self::SYSPASS_ENCRYPTED_FILE, LIBXML_NOBLANKS);

        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getPassword')->willReturn('test_encrypt');
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::never())
            ->method('getByName');

        $this->clientService
            ->expects(self::never())
            ->method('create');

        $this->categoryService
            ->expects(self::never())
            ->method('getByName');

        $this->categoryService
            ->expects(self::never())
            ->method('create');

        $this->tagService
            ->expects(self::never())
            ->method('getByName');

        $this->tagService
            ->expects(self::never())
            ->method('create');

        $this->crypt
            ->expects(self::exactly(4))
            ->method('decrypt')
            ->with(self::anything(), self::anything(), 'test_encrypt')
            ->willThrowException(CryptException::error('test'));

        $this->accountService
            ->expects(self::never())
            ->method('create');

        $sysPassImport = new SyspassImport($this->application, $importHelper, $this->crypt, $document);

        $out = $sysPassImport->doImport($importParamsDto);

        $this->assertEquals(0, $out->getCounter());
    }

    /**
     * @throws ImportException
     * @throws Exception
     */
    public function testDoImportWithAccountException()
    {
        $importParamsDto = $this->createStub(ImportParamsDto::class);
        $importParamsDto->method('getDefaultUser')->willReturn(100);
        $importParamsDto->method('getDefaultGroup')->willReturn(200);

        $this->clientService
            ->expects(self::exactly(4))
            ->method('getByName')
            ->with(...self::withConsecutive(['Apple'], ['CSV Client 1'], ['Google'], ['KK']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->clientService
            ->expects(self::exactly(4))
            ->method('create')
            ->with(self::callback(static fn(Client $client) => !empty($client->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 3)));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('getByName')
            ->with(...self::withConsecutive(['CSV Category 1'], ['Linux'], ['SSH'], ['Test'], ['Web']))
            ->willThrowException(NoSuchItemException::error('test'));

        $this->categoryService
            ->expects(self::exactly(5))
            ->method('create')
            ->with(
                self::callback(
                    static fn(Category $category) => !empty($category->getName()) &&
                                                     empty($category->getDescription())
                )
            )
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 4)));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('getByName')
            ->with(
                ...self::withConsecutive(['Apache'], ['Debian'], ['JBoss'], ['MySQL'], ['server'], ['SSH'], ['www'])
            )
            ->willThrowException(NoSuchItemException::error('test'));

        $this->tagService
            ->expects(self::exactly(7))
            ->method('create')
            ->with(self::callback(static fn(Tag $tag) => !empty($tag->getName())))
            ->willReturn(...array_map(static fn() => self::$faker->randomNumber(3), range(0, 6)));

        $this->crypt
            ->expects(self::never())
            ->method('decrypt');

        $this->accountService
            ->expects(self::exactly(5))
            ->method('create')
            ->willThrowException(new RuntimeException('test'));

        $out = $this->sysPassImport->doImport($importParamsDto);

        $this->assertEquals(0, $out->getCounter());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->accountService = $this->createMock(AccountService::class);
        $this->categoryService = $this->createMock(CategoryService::class);
        $this->clientService = $this->createMock(ClientService::class);
        $this->tagService = $this->createMock(TagService::class);
        $this->configService = $this->createMock(ConfigService::class);

        $importHelper = new ImportHelper(
            $this->accountService,
            $this->categoryService,
            $this->clientService,
            $this->tagService,
            $this->configService
        );

        $this->crypt = $this->createMock(CryptInterface::class);

        $document = new DOMDocument();
        $document->load(self::SYSPASS_FILE, LIBXML_NOBLANKS);

        $this->sysPassImport = new SyspassImport($this->application, $importHelper, $this->crypt, $document);
    }

}
