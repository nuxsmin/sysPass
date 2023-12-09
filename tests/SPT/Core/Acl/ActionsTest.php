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

namespace SPT\Core\Acl;

use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use SP\Core\Acl\Actions;
use SP\Core\Context\ContextException;
use SP\DataModel\ActionData;
use SP\Domain\Core\Acl\ActionNotFoundException;
use SP\Infrastructure\File\FileCacheInterface;
use SP\Infrastructure\File\FileException;
use SP\Infrastructure\File\FileHandlerInterface;
use SP\Infrastructure\File\XmlFileStorageInterface;
use SPT\UnitaryTestCase;

use function PHPUnit\Framework\once;

/**
 * Class ActionsTest
 *
 * @group unitary
 */
class ActionsTest extends UnitaryTestCase
{

    private FileCacheInterface|MockObject      $fileCache;
    private XmlFileStorageInterface|MockObject $xmlFileStorage;
    private Actions                            $actions;

    public static function expirationDataProvider(): array
    {
        return [
            [false, true],
            [true, false],
            [true, true]
        ];
    }

    /**
     * @dataProvider expirationDataProvider
     *
     * @throws FileException
     * @throws Exception
     * @throws ActionNotFoundException
     */
    public function testResetAndExpired(bool $expiredCache, bool $expiredDate)
    {
        $fileTime = self::$faker->randomNumber();

        $this->fileCache
            ->expects(self::once())
            ->method('isExpired')
            ->with(Actions::CACHE_EXPIRE)
            ->willReturn($expiredCache);

        if (!$expiredCache) {
            $fileHandler = $this->createMock(FileHandlerInterface::class);
            $fileHandler->expects(once())
                        ->method('getFileTime')
                        ->willReturn($fileTime);

            $this->xmlFileStorage
                ->expects(self::once())
                ->method('getFileHandler')
                ->willReturn($fileHandler);

            $this->fileCache
                ->expects(self::once())
                ->method('isExpiredDate')
                ->with($fileTime)
                ->willReturn($expiredDate);
        }

        $actionsMapped = $this->checkLoadAndSave();

        $this->actions->reset();

        $action = current($actionsMapped);

        $out = $this->actions->getActionById(array_key_first($actionsMapped));

        self::assertEquals($action, $out);
    }

    /**
     * @return ActionData[]
     */
    private function checkLoadAndSave(): array
    {
        $actions = $this->getActions();

        $this->xmlFileStorage
            ->expects(once())
            ->method('load')
            ->with('actions')
            ->willReturn($this->xmlFileStorage);

        $this->xmlFileStorage
            ->expects(once())
            ->method('getItems')
            ->willReturn($actions);

        $actionsMapped = array_map(
            static fn(array $a) => new ActionData($a['id'], $a['name'], $a['text'], $a['route']),
            $actions
        );

        $this->fileCache
            ->expects(once())
            ->method('save')
            ->with($actionsMapped);

        return $actionsMapped;
    }

    /**
     * @return array|array[]
     */
    private function getActions(): array
    {
        $actionsId = array_map(static fn() => self::$faker->unixTime, range(0, 10));

        $actions = array_map(
            static fn(int $id) => [
                'id' => $id,
                'name' => self::$faker->colorName,
                'text' => self::$faker->city,
                'route' => self::$faker->url
            ],
            $actionsId
        );

        return array_combine($actionsId, $actions);
    }

    /**
     * @throws FileException
     * @throws Exception
     * @throws ActionNotFoundException
     */
    public function testResetAndNotExpired()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('isExpired')
            ->with(Actions::CACHE_EXPIRE)
            ->willReturn(false);

        $actions = $this->getActions();

        $actionsMapped = array_map(
            static fn(array $a) => new ActionData($a['id'], $a['name'], $a['text'], $a['route']),
            $actions
        );

        $this->fileCache
            ->expects(self::once())
            ->method('load')
            ->willReturn($actionsMapped);

        $this->actions->reset();

        $action = current($actionsMapped);

        $out = $this->actions->getActionById(array_key_first($actions));

        self::assertEquals($action, $out);
    }

    /**
     * @throws ActionNotFoundException
     * @throws FileException
     */
    public function testResetWithCacheFileException()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('isExpired')
            ->with(Actions::CACHE_EXPIRE)
            ->willThrowException(new FileException('TestException'));

        $actionsMapped = $this->checkLoadAndSave();

        $this->actions->reset();

        $action = current($actionsMapped);

        $out = $this->actions->getActionById(array_key_first($actionsMapped));

        self::assertEquals($action, $out);
    }

    /**
     * @throws ActionNotFoundException
     * @throws FileException
     * @throws Exception
     */
    public function testResetWithXmlFileException()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('isExpired')
            ->with(Actions::CACHE_EXPIRE)
            ->willReturn(false);

        $fileHandler = $this->createMock(FileHandlerInterface::class);
        $fileHandler->expects(once())
                    ->method('getFileTime')
                    ->willThrowException(new FileException('TestException'));

        $this->xmlFileStorage
            ->expects(self::once())
            ->method('getFileHandler')
            ->willReturn($fileHandler);

        $actionsMapped = $this->checkLoadAndSave();

        $this->actions->reset();

        $action = current($actionsMapped);

        $out = $this->actions->getActionById(array_key_first($actionsMapped));

        self::assertEquals($action, $out);
    }

    /**
     * @throws FileException
     */
    public function testResetWithSaveException()
    {
        $this->fileCache
            ->expects(self::once())
            ->method('isExpired')
            ->with(Actions::CACHE_EXPIRE)
            ->willReturn(true);

        $actions = $this->getActions();

        $this->xmlFileStorage
            ->expects(once())
            ->method('load')
            ->with('actions')
            ->willReturn($this->xmlFileStorage);

        $this->xmlFileStorage
            ->expects(once())
            ->method('getItems')
            ->willReturn($actions);

        $actionsMapped = array_map(
            static fn(array $a) => new ActionData($a['id'], $a['name'], $a['text'], $a['route']),
            $actions
        );

        $this->fileCache
            ->expects(once())
            ->method('save')
            ->with($actionsMapped)
            ->willThrowException(new FileException('TestException'));

        $this->actions->reset();
    }

    /**
     * @throws ActionNotFoundException
     * @throws FileException
     */
    public function testGetActionById()
    {
        $actionsMapped = array_map(
            static fn(array $a) => new ActionData($a['id'], $a['name'], $a['text'], $a['route']),
            $this->getActions()
        );

        $this->fileCache
            ->expects(self::once())
            ->method('load')
            ->willReturn($actionsMapped);

        $actions = new Actions($this->fileCache, $this->xmlFileStorage);

        $out = $actions->getActionById(array_key_first($actionsMapped));

        self::assertEquals(current($actionsMapped), $out);
    }

    /**
     * @throws ActionNotFoundException
     */
    public function testGetActionByIdWithNotFound()
    {
        $this->expectException(ActionNotFoundException::class);
        $this->expectExceptionMessage('Action not found');

        $this->actions->getActionById(self::$faker->randomNumber());
    }

    /**
     * @throws ContextException
     * @throws Exception
     * @throws FileException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->fileCache = $this->createMock(FileCacheInterface::class);
        $this->xmlFileStorage = $this->createMock(XmlFileStorageInterface::class);

        $this->actions = new Actions($this->fileCache, $this->xmlFileStorage);
    }

}
