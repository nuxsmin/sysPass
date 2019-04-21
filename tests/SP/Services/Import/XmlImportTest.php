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

namespace SP\Tests\Services\Import;

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use SP\Core\Context\ContextException;
use SP\Services\Import\FileImport;
use SP\Services\Import\ImportException;
use SP\Services\Import\ImportParams;
use SP\Services\Import\XmlFileImport;
use SP\Services\Import\XmlImport;
use SP\Storage\Database\DatabaseConnectionData;
use SP\Storage\File\FileException;
use SP\Tests\DatabaseTestCase;
use function SP\Tests\setupContext;

/**
 * Class XmlImportTest
 *
 * @package SP\Tests\Services\Import
 */
class XmlImportTest extends DatabaseTestCase
{
    /**
     * @var Container
     */
    protected static $dic;

    /**
     * @throws NotFoundException
     * @throws ContextException
     * @throws DependencyException
     */
    public static function setUpBeforeClass()
    {
        self::$dic = setupContext();

        self::$dataset = 'syspass_import.xml';

        // Datos de conexión a la BBDD
        self::$databaseConnectionData = self::$dic->get(DatabaseConnectionData::class);
    }

    /**
     * @throws ImportException
     * @throws FileException
     */
    public function testDoImport()
    {
        $params = new ImportParams();
        $params->setDefaultUser(1);
        $params->setDefaultGroup(1);

        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_syspass.xml';

        $import = new XmlImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);

        $this->assertEquals(5, $import->doImport()->getCounter());

        $file = RESOURCE_DIR . DIRECTORY_SEPARATOR . 'import' . DIRECTORY_SEPARATOR . 'data_keepass.xml';

        $import = new XmlImport(self::$dic, new XmlFileImport(FileImport::fromFilesystem($file)), $params);

        $this->assertEquals(5, $import->doImport()->getCounter());
    }
}
