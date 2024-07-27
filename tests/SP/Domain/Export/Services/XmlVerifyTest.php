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

namespace SP\Tests\Domain\Export\Services;

use DOMDocument;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Bootstrap\Path;
use SP\Core\Crypt\Hash;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Crypt\CryptInterface;
use SP\Domain\Core\Exceptions\CryptException;
use SP\Domain\Export\Services\XmlVerify;
use SP\Tests\UnitaryTestCase;

/**
 * Class XmlVerifyTest
 *
 */
#[Group('unitary')]
class XmlVerifyTest extends UnitaryTestCase
{
    private const VALID_ENCRYPTED_FILE  = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                          'data_syspass_encrypted.xml';
    private const VALID_FILE            = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                          'data_syspass.xml';
    private const NO_VALID_FILE         = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                          'data_syspass_invalid.xml';
    private const NO_VALID_VERSION_FILE = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                          'data_syspass_invalid_version.xml';
    private const NO_VALID_HASH_FILE    = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR .
                                          'data_syspass_invalid_hash.xml';

    private CryptInterface|MockObject $crypt;
    private XmlVerify                 $xmlVerify;

    public function testCheckXmlHashWithNoHash()
    {
        $xml = '<Root><Meta><Hash></Hash></Meta></Root>';

        $document = new DOMDocument();
        $document->loadXML($xml);

        $out = XmlVerify::checkXmlHash($document, 'test');

        $this->assertFalse($out);
    }

    public function testCheckXmlHashWithNoSign()
    {
        $xml = '<Root><Meta><Hash sign="">a_hash</Hash></Meta></Root>';

        $document = new DOMDocument();
        $document->loadXML($xml);

        $out = XmlVerify::checkXmlHash($document, 'test');

        $this->assertFalse($out);
    }

    public function testCheckXmlHashWithWrongHash()
    {
        $xml = '<Root><Meta><Hash sign="a_sign">a_hash</Hash></Meta></Root>';

        $document = new DOMDocument();
        $document->loadXML($xml);

        $out = XmlVerify::checkXmlHash($document, 'test');

        $this->assertFalse($out);
    }

    public function testCheckXmlHash()
    {
        $sign = Hash::signMessage('test_data', 'test_key');
        $xml = sprintf('<Root><Meta><Hash sign="%s">test_data</Hash></Meta></Root>', $sign);

        $document = new DOMDocument();
        $document->loadXML($xml);

        $out = XmlVerify::checkXmlHash($document, 'test_key');

        $this->assertTrue($out);
    }

    public function testCheckXmlHashWithNodesHash()
    {
        $xml = sprintf(
            '<Root><Meta><Hash>%s</Hash></Meta><Test>test_data</Test></Root>',
            sha1('<Test>test_data</Test>')
        );
        $document = new DOMDocument();
        $document->loadXML($xml);

        $out = XmlVerify::checkXmlHash($document, 'test_key');

        $this->assertTrue($out);
    }

    /**
     * @throws ServiceException
     */
    public function testVerify()
    {
        $out = $this->xmlVerify->verify(self::VALID_FILE);

        $this->assertEquals('300.18071701', $out->getVersion());
        $nodes = $out->getNodes();
        $this->assertCount(4, $nodes);
        $this->assertEquals(5, $nodes['Category']);
        $this->assertEquals(4, $nodes['Client']);
        $this->assertEquals(7, $nodes['Tag']);
        $this->assertEquals(5, $nodes['Account']);
        $this->assertFalse($out->isEncrypted());
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyWithInvalidFile()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Unable to load XML file');

        $this->xmlVerify->verify('a_file');
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyWithInvalidFileSchema()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Invalid XML schema');

        $this->xmlVerify->verify(self::NO_VALID_FILE);
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyWithInvalidVersion()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Sorry, this XML version is not compatible. Please use >= 2.1');

        $this->xmlVerify->verify(self::NO_VALID_VERSION_FILE);
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyWithInvalidHash()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while checking integrity hash');

        $this->xmlVerify->verify(self::NO_VALID_HASH_FILE);
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyEncrypted()
    {
        $categories = '<Categories><Category id="4"><name>CSV Category 1</name><description/></Category></Categories>';
        $clients = '<Clients><Client id="5"><name>Apple</name><description/></Client></Clients>';
        $tags = '<Tags><Tag id="5"><name>Apache</name></Tag></Tags>';
        $accounts = '<Accounts><Account id="1"><name>Google</name><clientId>1</clientId><categoryId>1</categoryId><login>admin</login><url>https://google.com</url><notes/><pass>a</pass><key>test</key><tags><tag id="4"/></tags></Account></Accounts>';

        $this->crypt
            ->expects(self::exactly(4))
            ->method('decrypt')
            ->with(
                self::stringStartsWith('def50200'),
                self::stringStartsWith('def10000def5020'),
                'test_encrypt'
            )
            ->willReturn($categories, $clients, $tags, $accounts);

        $out = $this->xmlVerify->verify(self::VALID_ENCRYPTED_FILE, 'test_encrypt');

        $this->assertEquals('300.18082201', $out->getVersion());
        $nodes = $out->getNodes();
        $this->assertCount(4, $nodes);
        $this->assertEquals(1, $nodes['Category']);
        $this->assertEquals(1, $nodes['Client']);
        $this->assertEquals(1, $nodes['Tag']);
        $this->assertEquals(1, $nodes['Account']);
        $this->assertFalse($out->isEncrypted());
    }

    /**
     * @throws ServiceException
     */
    public function testVerifyEncryptedWithCryptException()
    {
        $this->crypt
            ->expects(self::once())
            ->method('decrypt')
            ->willThrowException(CryptException::error('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Wrong encryption password');

        $this->xmlVerify->verify(self::VALID_ENCRYPTED_FILE, 'test_encrypt');
    }

    protected function buildConfig(): ConfigFileService
    {
        $configData = new ConfigData([ConfigDataInterface::PASSWORD_SALT => 'a_salt']);

        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        return $config;
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->crypt = $this->createMock(CryptInterface::class);

        $this->xmlVerify = new XmlVerify($this->application, $this->crypt, $this->pathsContext[Path::XML_SCHEMA]);
    }
}
