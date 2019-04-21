<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Tests\Services\Export;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use SP\Core\Context\ContextException;
use SP\Services\Export\VerifyResult;
use SP\Services\Export\XmlVerifyService;
use SP\Services\ServiceException;
use SP\Storage\File\FileException;
use function SP\Tests\setupContext;

/**
 * Class XmlVerifyServiceTest
 *
 * @package SP\Tests\Services\Export
 */
class XmlVerifyServiceTest extends TestCase
{
    /**
     * @var XmlVerifyService
     */
    private static $xmlVerifyService;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass()
    {
        $dic = setupContext();

        self::$xmlVerifyService = $dic->get(XmlVerifyService::class);
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     * @throws FileException
     */
    public function testVerifyEncrypted()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass_encrypted.xml';

        $result = self::$xmlVerifyService->verifyEncrypted($file, 'test_encrypt');

        $this->assertInstanceOf(VerifyResult::class, $result);
        $this->assertEquals(300.18082201, $result->getVersion());

        $nodes = $result->getNodes();

        $this->assertCount(4, $nodes);
        $this->assertEquals(4, $nodes['Category']);
        $this->assertEquals(3, $nodes['Client']);
        $this->assertEquals(6, $nodes['Tag']);
        $this->assertEquals(2, $nodes['Account']);
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testVerify()
    {
        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $result = self::$xmlVerifyService->verify($file);

        $this->assertInstanceOf(VerifyResult::class, $result);
        $this->assertEquals(300.18071701, $result->getVersion());

        $nodes = $result->getNodes();

        $this->assertCount(4, $nodes);
        $this->assertEquals(5, $nodes['Category']);
        $this->assertEquals(4, $nodes['Client']);
        $this->assertEquals(7, $nodes['Tag']);
        $this->assertEquals(5, $nodes['Account']);
    }

    public function testCheckXmlHash()
    {
        $dom = new DOMDocument();
        $dom->load(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass_encrypted.xml');

        $this->assertTrue(XmlVerifyService::checkXmlHash($dom, 'test_encrypt'));

        $dom->load(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass_invalid.xml');

        $this->assertFalse(XmlVerifyService::checkXmlHash($dom, 'test_encrypt'));

        $key = sha1('d5851082a3914a647a336d8910e24eb64b8f8adef24d27329040ebd0d4c1');

        $dom->load(RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass_valid_hash.xml');

        $this->assertTrue(XmlVerifyService::checkXmlHash($dom, $key));
    }
}
