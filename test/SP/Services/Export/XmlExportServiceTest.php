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

namespace SP\Test\Services\Export;

use SP\Services\Export\VerifyResult;
use SP\Services\Export\XmlExportService;
use SP\Services\Export\XmlVerifyService;
use SP\Services\ServiceException;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Test\DatabaseTestCase;
use SP\Util\Util;
use function SP\Test\setupContext;

/**
 * Class XmlExportServiceTest
 *
 * @package SP\Tests\Services\Export
 */
class XmlExportServiceTest extends DatabaseTestCase
{
    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     */
    public static function setUpBeforeClass()
    {
        array_map('unlink', glob(TMP_DIR . DIRECTORY_SEPARATOR . '*.xml'));

        $dic = setupContext();

        self::$dataset = 'syspass_import.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = $dic->get(DatabaseConnectionData::class);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\FileException
     */
    public function testDoExportWithoutPassword()
    {
        $dic = setupContext();
        $service = $dic->get(XmlExportService::class);
        $service->doExport(TMP_DIR);

        $this->assertFileExists($service->getExportFile());

        $this->verifyExportWithoutPassword($service->getExportFile());
    }

    /**
     * @depends testDoExportWithoutPassword
     *
     * @param $file
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\FileException
     */
    private function verifyExportWithoutPassword($file)
    {
        $dic = setupContext();
        $service = $dic->get(XmlVerifyService::class);

        $result = $service->verify($file);

        $this->assertInstanceOf(VerifyResult::class, $result);

        $this->checkVerifyResult($result);
    }

    /**
     * @param VerifyResult $verifyResult
     */
    private function checkVerifyResult(VerifyResult $verifyResult)
    {
        $nodes = $verifyResult->getNodes();

        $this->assertCount(4, $nodes);
        $this->assertArrayHasKey('Account', $nodes);
        $this->assertArrayHasKey('Category', $nodes);
        $this->assertArrayHasKey('Client', $nodes);
        $this->assertArrayHasKey('Tag', $nodes);
        $this->assertEquals(2, $nodes['Account']);
        $this->assertEquals(3, $nodes['Category']);
        $this->assertEquals(3, $nodes['Client']);
        $this->assertEquals(3, $nodes['Tag']);
    }

    /**
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\FileException
     */
    public function testDoExportWithPassword()
    {
        $dic = setupContext();
        $service = $dic->get(XmlExportService::class);

        $password = Util::randomPassword();

        $service->doExport(TMP_DIR, $password);

        $this->assertFileExists($service->getExportFile());

        $this->verifyExportWithPassword($service->getExportFile(), $password);
    }

    /**
     * @param $file
     * @param $password
     *
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     * @throws \Defuse\Crypto\Exception\CryptoException
     * @throws \SP\Core\Context\ContextException
     * @throws \SP\Services\ServiceException
     * @throws \SP\Storage\FileException
     */
    private function verifyExportWithPassword($file, $password)
    {
        $dic = setupContext();
        $service = $dic->get(XmlVerifyService::class);

        $result = $service->verifyEncrypted($file, $password);

        $this->assertInstanceOf(VerifyResult::class, $result);
        $this->assertTrue($result->isEncrypted());

        $this->checkVerifyResult($result);

        $this->expectException(ServiceException::class);

        $service->verifyEncrypted($file, 'test123');
    }
}
