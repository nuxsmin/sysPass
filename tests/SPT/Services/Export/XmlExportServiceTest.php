<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SPT\Services\Export;

use Defuse\Crypto\Exception\CryptoException;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Ports\XmlExportServiceInterface;
use SP\Domain\Export\Ports\XmlVerifyServiceInterface;
use SP\Domain\Export\Services\VerifyResult;
use SP\Domain\Export\Services\XmlExportService;
use SP\Domain\Export\Services\XmlVerifyService;
use SP\Infrastructure\File\FileException;
use SP\Util\PasswordUtil;
use SPT\DatabaseTestCase;

use function SPT\setupContext;

/**
 * Class XmlExportServiceTest
 *
 * @package SPT\Services\Export
 */
class XmlExportServiceTest extends DatabaseTestCase
{
    /**
     * @var XmlExportServiceInterface
     */
    private static $xmlExportService;
    /**
     * @var XmlVerifyServiceInterface
     */
    private static $xmlVerifyService;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        array_map('unlink', glob(TMP_PATH . DIRECTORY_SEPARATOR . '*.xml'));

        $dic = setupContext();

        self::$loadFixtures = true;

        self::$xmlExportService = $dic->get(XmlExportService::class);
        self::$xmlVerifyService = $dic->get(XmlVerifyService::class);
    }

    /**
     * @throws ServiceException
     * @throws FileException
     */
    public function testDoExportWithoutPassword()
    {
        self::$xmlExportService->doExport(TMP_PATH);

        $this->assertFileExists(self::$xmlExportService->getExportFile());

        $this->verifyExportWithoutPassword(self::$xmlExportService->getExportFile());
    }

    /**
     * @depends testDoExportWithoutPassword
     *
     * @param $file
     *
     * @throws ServiceException
     * @throws FileException
     */
    private function verifyExportWithoutPassword($file)
    {
        $result = self::$xmlVerifyService->verify($file);

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
        $this->assertEquals(4, $nodes['Account']);
        $this->assertEquals(3, $nodes['Category']);
        $this->assertEquals(4, $nodes['Client']);
        $this->assertEquals(3, $nodes['Tag']);
    }

    /**
     * @throws CryptoException
     * @throws ServiceException
     * @throws FileException
     */
    public function testDoExportWithPassword()
    {
        $password = PasswordUtil::randomPassword();

        self::$xmlExportService->doExport(TMP_PATH, $password);

        $this->assertFileExists(self::$xmlExportService->getExportFile());

        $this->verifyExportWithPassword(self::$xmlExportService->getExportFile(), $password);
    }

    /**
     * @param $file
     * @param $password
     *
     * @throws CryptoException
     * @throws ServiceException
     * @throws FileException
     */
    private function verifyExportWithPassword($file, $password)
    {
        $result = self::$xmlVerifyService->verifyEncrypted($file, $password);

        $this->assertInstanceOf(VerifyResult::class, $result);
        $this->assertTrue($result->isEncrypted());

        $this->checkVerifyResult($result);

        $this->expectException(ServiceException::class);

        self::$xmlVerifyService->verifyEncrypted($file, 'test123');
    }
}
