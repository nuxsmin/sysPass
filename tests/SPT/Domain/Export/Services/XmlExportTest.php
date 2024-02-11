<?php
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

namespace SPT\Domain\Export\Services;

use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Defuse\Crypto\KeyProtectedByPassword;
use DOMDocument;
use DOMElement;
use DOMException;
use PHPUnit\Framework\Constraint\Callback;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CheckException;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Export\Ports\XmlAccountExportService;
use SP\Domain\Export\Ports\XmlCategoryExportService;
use SP\Domain\Export\Ports\XmlClientExportService;
use SP\Domain\Export\Ports\XmlTagExportService;
use SP\Domain\Export\Services\XmlExport;
use SP\Domain\File\Ports\DirectoryHandlerService;
use SP\Infrastructure\File\FileException;
use SP\Util\VersionUtil;
use SPT\UnitaryTestCase;

/**
 * Class XmlExportTest
 *
 * @group unitary
 */
class XmlExportTest extends UnitaryTestCase
{
    use XmlTrait;

    private PhpExtensionCheckerService|MockObject $phpExtensionCheckerService;
    private MockObject|XmlClientExportService     $xmlClientExportService;
    private XmlAccountExportService|MockObject    $xmlAccountExportService;
    private XmlCategoryExportService|MockObject   $xmlCategoryExportService;
    private XmlTagExportService|MockObject        $xmlTagExportService;
    private CryptInterface|MockObject             $crypt;
    private XmlExport                             $xmlExport;

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws FileException
     * @throws CheckException
     * @throws EnvironmentIsBrokenException
     * @throws DOMException
     */
    public function testExport()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $password = self::$faker->password();

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestCategories'));

        $this->xmlClientExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestClients'));

        $this->xmlTagExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestTags'));

        $this->xmlAccountExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestAccounts'));

        $this->checkCrypt($password);

        $out = $this->xmlExport->export($exportPath, $password);

        $this->assertNotEmpty($this->config->getConfigData()->getExportHash());
        $this->assertFileExists($out);

        $xml = new DOMDocument();
        $xml->load($out, LIBXML_NOBLANKS);

        $meta = $xml->documentElement->getElementsByTagName('Meta')->item(0)->childNodes;

        $this->assertEquals(6, $meta->count());

        $this->checkNodes(
            $meta,
            [
                'Generator' => 'sysPass',
                'Version' => VersionUtil::getVersionStringNormalized(),
                'Time' => static fn(string $value) => self::assertTrue($value > 0),
                'User' => 'test_user',
                'Group' => 'test_group',
                'Hash' => static fn(string $value) => self::assertNotEmpty($value),
            ]
        );

        $this->assertNotEmpty($meta->item(5)->attributes->getNamedItem('sign')->nodeValue);

        $encrypted = $xml->documentElement->getElementsByTagName('Encrypted')->item(0);

        $this->assertStringStartsWith('$2y$10$', $encrypted->attributes->getNamedItem('hash')->nodeValue);

        $this->assertEquals(4, $encrypted->childNodes->count());

        $this->assertEquals(
            'encrypted_data_categories',
            $encrypted->childNodes->item(0)->childNodes->item(0)->nodeValue
        );
        $this->assertNotEmpty($encrypted->childNodes->item(0)->attributes->getNamedItem('key')->nodeValue);
        $this->assertEquals(
            'encrypted_data_clients',
            $encrypted->childNodes->item(1)->childNodes->item(0)->nodeValue
        );
        $this->assertNotEmpty($encrypted->childNodes->item(1)->attributes->getNamedItem('key')->nodeValue);
        $this->assertEquals(
            'encrypted_data_tags',
            $encrypted->childNodes->item(2)->childNodes->item(0)->nodeValue
        );
        $this->assertNotEmpty($encrypted->childNodes->item(2)->attributes->getNamedItem('key')->nodeValue);
        $this->assertEquals(
            'encrypted_data_accounts',
            $encrypted->childNodes->item(3)->childNodes->item(0)->nodeValue
        );
        $this->assertNotEmpty($encrypted->childNodes->item(3)->attributes->getNamedItem('key')->nodeValue);
    }

    /**
     * @throws DOMException
     */
    private function createNode(string $nodeName): DOMElement
    {
        return new DOMElement($nodeName, self::$faker->text());
    }

    /**
     * @param string $password
     * @param int $times
     * @return void
     * @throws EnvironmentIsBrokenException
     */
    private function checkCrypt(string $password, int $times = 4): void
    {
        $securedKey = KeyProtectedByPassword::createRandomPasswordProtectedKey($password);

        $this->crypt
            ->expects(self::exactly($times))
            ->method('makeSecuredKey')
            ->with($password, false)
            ->willReturn($securedKey);

        $this->crypt
            ->expects(self::exactly($times))
            ->method('encrypt')
            ->with(
                self::anything(),
                new Callback(static function (Key $key) use ($password, $securedKey) {
                    return $key->saveToAsciiSafeString() === $securedKey->unlockKey($password)->saveToAsciiSafeString();
                })
            )
            ->willReturn(
                'encrypted_data_categories',
                'encrypted_data_clients',
                'encrypted_data_tags',
                'encrypted_data_accounts'
            );
    }

    /**
     * @throws CheckException
     * @throws Exception
     * @throws FileException
     * @throws ServiceException
     */
    public function testExportWithCheckDirectoryException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate')
                   ->willThrowException(CheckException::error('test'));

        $this->expectException(CheckException::class);
        $this->expectExceptionMessage('test');

        $this->xmlExport->export($exportPath);
    }

    /**
     * @throws CheckException
     * @throws Exception
     * @throws FileException
     * @throws ServiceException
     */
    public function testExportWithExportCategoryException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $password = self::$faker->password();

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while exporting');

        $this->xmlExport->export($exportPath, $password);
    }

    /**
     * @throws CheckException
     * @throws Exception
     * @throws FileException
     * @throws ServiceException
     * @throws DOMException
     * @throws EnvironmentIsBrokenException
     */
    public function testExportWithExportClientException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestCategories'));

        $this->xmlClientExportService
            ->expects(self::once())
            ->method('export')
            ->willThrowException(new RuntimeException('test'));

        $password = self::$faker->password();

        $this->checkCrypt($password, 1);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while exporting');

        $this->xmlExport->export($exportPath, $password);
    }

    /**
     * @throws CheckException
     * @throws Exception
     * @throws FileException
     * @throws ServiceException
     * @throws DOMException
     * @throws EnvironmentIsBrokenException
     */
    public function testExportWithExportTagException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestCategories'));

        $this->xmlClientExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestClients'));

        $this->xmlTagExportService
            ->expects(self::once())
            ->method('export')
            ->willThrowException(new RuntimeException('test'));

        $password = self::$faker->password();

        $this->checkCrypt($password, 2);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while exporting');

        $this->xmlExport->export($exportPath, $password);
    }

    /**
     * @throws CheckException
     * @throws Exception
     * @throws FileException
     * @throws ServiceException
     * @throws DOMException
     * @throws EnvironmentIsBrokenException
     */
    public function testExportWithExportAccountException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestCategories'));

        $this->xmlClientExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestClients'));

        $this->xmlTagExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestTags'));

        $this->xmlAccountExportService
            ->expects(self::once())
            ->method('export')
            ->willThrowException(new RuntimeException('test'));

        $password = self::$faker->password();

        $this->checkCrypt($password, 3);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while exporting');

        $this->xmlExport->export($exportPath, $password);
    }

    /**
     * @throws ServiceException
     * @throws Exception
     * @throws FileException
     * @throws CheckException
     * @throws DOMException
     */
    public function testExportWithCryptException()
    {
        $userData = $this->context->getUserData();
        $userData->setLogin('test_user');
        $userData->setUserGroupName('test_group');
        $this->context->setUserData($userData);

        $exportPath = $this->createMock(DirectoryHandlerService::class);
        $exportPath->expects(self::once())
                   ->method('checkOrCreate');
        $exportPath->method('getPath')
                   ->willReturn(TMP_PATH);

        $password = self::$faker->password();

        $this->xmlCategoryExportService
            ->expects(self::once())
            ->method('export')
            ->willReturn($this->createNode('TestCategories'));

        $this->crypt
            ->expects(self::once())
            ->method('makeSecuredKey')
            ->willThrowException(CryptException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('test');

        $this->xmlExport->export($exportPath, $password);
    }

    public function testBuildFilename()
    {
        $out = XmlExport::buildFilename('test', 'a_hash');

        $this->assertEquals('test/sysPass_export-a_hash.xml', $out);
    }

    public function testBuildFilenameCompressed()
    {
        $out = XmlExport::buildFilename('test', 'a_hash', true);

        $this->assertEquals('test/sysPass_export-a_hash.tar.gz', $out);
    }

    /**
     * @throws Exception
     * @throws ServiceException
     * @throws ContextException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->phpExtensionCheckerService = $this->createMock(PhpExtensionCheckerService::class);
        $this->xmlClientExportService = $this->createMock(XmlClientExportService::class);
        $this->xmlAccountExportService = $this->createMock(XmlAccountExportService::class);
        $this->xmlCategoryExportService = $this->createMock(XmlCategoryExportService::class);
        $this->xmlTagExportService = $this->createMock(XmlTagExportService::class);
        $this->crypt = $this->createMock(CryptInterface::class);

        $this->xmlExport = new XmlExport(
            $this->application,
            $this->phpExtensionCheckerService,
            $this->xmlClientExportService,
            $this->xmlAccountExportService,
            $this->xmlCategoryExportService,
            $this->xmlTagExportService,
            $this->crypt
        );
    }
}
