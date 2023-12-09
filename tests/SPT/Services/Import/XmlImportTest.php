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

namespace SPT\Services\Import;

use DI\Container;
use SP\Core\Context\ContextException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Import\Services\FileImport;
use SP\Domain\Import\Services\ImportException;
use SP\Domain\Import\Services\ImportParams;
use SP\Domain\Import\Services\XmlFileImport;
use SP\Domain\Import\Services\XmlImport;
use SP\Infrastructure\File\FileException;
use SPT\DatabaseTestCase;

use function SPT\setupContext;

/**
 * Class XmlImportTest
 *
 * @package SPT\Services\Import
 */
class XmlImportTest extends DatabaseTestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws ContextException
     */
    public static function setUpBeforeClass(): void
    {
        self::$dic = setupContext();

        self::$loadFixtures = true;
    }

    /**
     * @throws ImportException
     * @throws FileException
     * @throws SPException
     */
    public function testDoImport()
    {
        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(1);

        $file = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $import = new XmlImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);

        $this->assertEquals(5, $import->doImport()->getCounter());

        $file = RESOURCE_PATH . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_keepass.xml';

        $import = new XmlImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);

        $this->assertEquals(5, $import->doImport()->getCounter());
    }
}
