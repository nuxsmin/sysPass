<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Domain\Install\Services;


use SP\Domain\Install\Adapters\InstallData;
use SP\Infrastructure\Database\DatabaseConnectionData;
use SP\Infrastructure\Database\DatabaseUtil;
use SP\Infrastructure\Database\MysqlFileParser;
use SP\Infrastructure\Database\MysqlHandler;
use SP\Infrastructure\File\FileHandler;

/**
 * Class DatabaseSetupBuilder
 */
final class MysqlSetupBuilder implements MysqlSetupBuilderInterface
{
    private const DATABASE_SCHEMA_FILE = SQL_PATH.DIRECTORY_SEPARATOR.'dbstructure.sql';

    public static function build(InstallData $installData): DatabaseSetupInterface
    {
        $connectionData = (new DatabaseConnectionData())
            ->setDbHost($installData->getDbHost())
            ->setDbPort($installData->getDbPort())
            ->setDbSocket($installData->getDbSocket())
            ->setDbUser($installData->getDbAdminUser())
            ->setDbPass($installData->getDbAdminPass());

        $parser = new MysqlFileParser(new FileHandler(self::DATABASE_SCHEMA_FILE));

        $mysqlHandler = new MysqlHandler($connectionData);

        return new MysqlService($mysqlHandler, $installData, $parser, new DatabaseUtil($mysqlHandler));
    }
}
