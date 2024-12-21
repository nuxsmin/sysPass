<?php
/**
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

declare(strict_types=1);

namespace SP\Tests\Infrastructure\File;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Domain\File\Ports\FileHandlerInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\YamlFileStorage;
use SP\Tests\UnitaryTestCase;

/**
 * Class YamlFileStorageTest
 */
#[Group('unitary')]
class YamlFileStorageTest extends UnitaryTestCase
{
    private const YAML =
        'list:' . PHP_EOL .
        '  -' . PHP_EOL .
        '    id: 1' . PHP_EOL .
        '    name: a_name' . PHP_EOL;
    private FileHandlerInterface|MockObject $fileHandler;
    private YamlFileStorage                 $yamlFileStorage;

    /**
     * @throws FileException
     */
    public function testLoad()
    {
        $file = TMP_PATH . DIRECTORY_SEPARATOR . 'test.yaml';

        $this->fileHandler
            ->method('getFile')
            ->willReturn($file);

        file_put_contents($file, self::YAML);

        $out = $this->yamlFileStorage->load();

        $expected = ['list' => [['id' => 1, 'name' => 'a_name']]];

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws FileException
     */
    public function testSave()
    {
        $this->fileHandler
            ->expects($this->once())
            ->method('save')
            ->with(self::YAML);

        $this->yamlFileStorage->save(['list' => [(object)['id' => 1, 'name' => 'a_name']]]);
    }

    /**
     * @throws FileException
     */
    public function testGetFileTime()
    {
        $time = self::$faker->unixTime();

        $this->fileHandler->expects($this->once())
                          ->method('getFileTime')
                          ->willReturn($time);

        $this->assertEquals($time, $this->yamlFileStorage->getFileTime());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->fileHandler = $this->createMock(FileHandlerInterface::class);
        $this->yamlFileStorage = new YamlFileStorage($this->fileHandler);
    }
}
