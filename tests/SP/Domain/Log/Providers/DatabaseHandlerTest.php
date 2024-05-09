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

namespace SP\Tests\Domain\Log\Providers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Security\Models\Eventlog;
use SP\Domain\Security\Ports\EventlogService;
use SP\Tests\Generators\ConfigDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class DatabaseHandlerTest
 */
#[Group('unitary')]
class DatabaseHandlerTest extends UnitaryTestCase
{
    private MockObject|EventlogService   $eventLogService;
    private MockObject|LanguageInterface $language;
    private DatabaseHandler              $databaseHandler;
    private ConfigData                   $configData;

    public function testGetEventsString()
    {
        $expected = 'test_a\.|test_b\.|upgrade\.|acl\.deny|plugin\.load\.error|show\.authToken|clear\.eventlog|clear\.track|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword|run\.import\.start|run\.import\.end';
        $out = $this->databaseHandler->getEventsString();

        $this->assertEquals($expected, $out);
    }

    public function testGetEventsStringWithNoConfiguredEvents()
    {
        $expected = 'upgrade\.|acl\.deny|plugin\.load\.error|show\.authToken|clear\.eventlog|clear\.track|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword|run\.import\.start|run\.import\.end';

        $this->configData->setLogEvents([]);

        $databaseHandler = new DatabaseHandler($this->application, $this->eventLogService, $this->language);
        $out = $databaseHandler->getEventsString();

        $this->assertEquals($expected, $out);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdate()
    {
        $eventMessage = EventMessage::factory()->addDescription('a_description')->addDetail('a_detail', 'a_value');
        $event = new Event($this, $eventMessage);

        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->eventLogService
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (Eventlog $eventlog) use ($eventMessage) {
                    return $eventlog->getDescription() === $eventMessage->composeText()
                           && $eventlog->getAction() === 'test_a.update'
                           && $eventlog->getLevel() == 'INFO';
                })
            );

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $this->databaseHandler->update('test_a.update', $event);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdateWithNoEventMessage()
    {
        $event = new Event($this);

        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->eventLogService
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (Eventlog $eventlog) {
                    return $eventlog->getDescription() === null
                           && $eventlog->getAction() === 'test_a.update'
                           && $eventlog->getLevel() == 'INFO';
                })
            );

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $this->databaseHandler->update('test_a.update', $event);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdateWithSPExceptionMessage()
    {
        $event = new Event(SPException::error('an_exception', 'a_hint'));

        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->eventLogService
            ->expects($this->once())
            ->method('create')
            ->with(
                self::callback(static function (Eventlog $eventlog) {
                    return $eventlog->getDescription() ===
                           'SP\Domain\Core\Exceptions\SPException: [0]: an_exception (a_hint)'
                           && $eventlog->getAction() === 'test_a.update'
                           && $eventlog->getLevel() == 'ERROR';
                })
            );

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $this->databaseHandler->update('test_a.update', $event);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdateWithException()
    {
        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->eventLogService
            ->expects($this->once())
            ->method('create')
            ->willThrowException(new RuntimeException('test'));

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $this->databaseHandler->update('test_a.update', new Event($this));
    }

    protected function buildConfig(): ConfigFileService
    {
        $this->configData = ConfigDataGenerator::factory()->buildConfigData();
        $this->configData->setLogEvents(['test_a.', 'test_b.']);

        $config = $this->createMock(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($this->configData);

        return $config;
    }


    protected function setUp(): void
    {
        parent::setUp();

        $this->eventLogService = $this->createMock(EventlogService::class);
        $this->language = $this->createMock(LanguageInterface::class);

        $this->databaseHandler = new DatabaseHandler($this->application, $this->eventLogService, $this->language);
    }
}
