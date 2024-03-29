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

namespace SPT\Infrastructure\Database;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use SP\Infrastructure\Database\MysqlFileParser;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SPT\UnitaryTestCase;

/**
 * Class MysqlFileParserTest
 */
#[Group('unitary')]
class MysqlFileParserTest extends UnitaryTestCase
{

    /**
     * @throws Exception
     * @throws FileException
     */
    public function testParse()
    {
        $lines = static function () {
            yield 'DELIMITER $$';
            yield '-- Test';
            yield '/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */$$';
            yield '/*!40101 SET NAMES utf8mb4 */$$';
            yield 'CREATE TABLE `Account`';
            yield '(';
            yield '`id`                 mediumint(8) unsigned NOT NULL AUTO_INCREMENT,';
            yield 'PRIMARY KEY (`id`),';
            yield 'CONSTRAINT `fk_Account_categoryId` FOREIGN KEY (`categoryId`) REFERENCES `Category` (`id`)';
            yield ') ENGINE = InnoDB';
            yield 'COLLATE = utf8mb3_unicode_ci$$';
        };

        $fileHandler = $this->createMock(FileHandlerInterface::class);

        $fileHandler->expects($this->once())
                    ->method('checkIsReadable');

        $fileHandler->expects($this->once())
                    ->method('read')
                    ->willReturnCallback($lines);

        $mysqlFileParser = new MysqlFileParser($fileHandler);

        $counter = 0;
        $queries = [];

        foreach ($mysqlFileParser->parse('$$') as $query) {
            $counter++;
            $this->assertNotEmpty($query);
            $queries[] = $query;
        }

        $this->assertEquals(3, $counter);
        $this->assertEquals('/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */', $queries[0]);
        $this->assertEquals('/*!40101 SET NAMES utf8mb4 */', $queries[1]);
        $this->assertEquals(
            'CREATE TABLE `Account` ( `id`                 mediumint(8) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`), CONSTRAINT `fk_Account_categoryId` FOREIGN KEY (`categoryId`) REFERENCES `Category` (`id`) ) ENGINE = InnoDB COLLATE = utf8mb3_unicode_ci',
            $queries[2]
        );
    }
}
