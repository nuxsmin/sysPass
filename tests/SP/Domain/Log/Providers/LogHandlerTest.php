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
use Psr\Log\LoggerInterface;
use RuntimeException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Core\Exceptions\InvalidClassException;
use SP\Domain\Core\LanguageInterface;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Log\Providers\LogHandler;
use SP\Tests\UnitaryTestCase;

/**
 * Class LogHandlerTest
 */
#[Group('unitary')]
class LogHandlerTest extends UnitaryTestCase
{

    private LoggerInterface|MockObject   $logger;
    private MockObject|LanguageInterface $language;
    private RequestService|MockObject    $request;
    private LogHandler                   $logHandler;

    /**
     * @throws InvalidClassException
     */
    public function testUpdate()
    {
        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $ipv4 = self::$faker->ipv4();

        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn($ipv4);

        $eventMessage = EventMessage::factory()->addDescription('test');
        $event = new Event($this, $eventMessage);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'test.event',
                self::callback(function (array $event) use ($eventMessage, $ipv4) {
                    return $event['message'] === $eventMessage->composeText(' | ')
                           && $event['address'] === $ipv4
                           && $event['user'] === $this->context->getUserData()->login
                           && !empty($event['caller']);
                })
            );

        $this->logHandler->update('test.event', $event);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdateWithNoMessage()
    {
        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $ipv4 = self::$faker->ipv4();

        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn($ipv4);

        $event = new Event($this);

        $this->logger
            ->expects($this->once())
            ->method('debug')
            ->with(
                'test.event',
                self::callback(function (array $event) use ($ipv4) {
                    return $event['message'] === 'N/A'
                           && $event['address'] === $ipv4
                           && $event['user'] === $this->context->getUserData()->login
                           && !empty($event['caller']);
                })
            );

        $this->logHandler->update('test.event', $event);
    }

    /**
     * @throws InvalidClassException
     */
    public function testUpdateWithExceptionSource()
    {
        $this->language
            ->expects($this->once())
            ->method('setAppLocales');

        $this->language
            ->expects($this->once())
            ->method('unsetAppLocales');

        $ipv4 = self::$faker->ipv4();

        $this->request
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn($ipv4);

        $event = new Event(new RuntimeException('an_exception'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'test.event',
                self::callback(function (array $event) use ($ipv4) {
                    return $event['message'] === 'an_exception'
                           && $event['address'] === $ipv4
                           && $event['user'] === $this->context->getUserData()->login
                           && !empty($event['caller']);
                })
            );

        $this->logHandler->update('test.event', $event);
    }


    public function testGetEvents()
    {
        $out = $this->logHandler->getEvents();

        $expected = 'upgrade\.|acl\.deny|plugin\.load\.error|show\.authToken|clear\.eventlog|clear\.track|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword|run\.import\.start|run\.import\.end';

        $this->assertEquals($expected, $out);
    }

    public function testGetEventsWithConfigEvents()
    {
        $this->config->getConfigData()->setLogEvents(['test.event_a', 'test.event_b']);

        $logHandler = new LogHandler($this->application, $this->logger, $this->language, $this->request);

        $out = $logHandler->getEvents();

        $expected = 'test\.event_a|test\.event_b|upgrade\.|acl\.deny|plugin\.load\.error|show\.authToken|clear\.eventlog|clear\.track|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword|run\.import\.start|run\.import\.end';

        $this->assertEquals($expected, $out);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->language = $this->createMock(LanguageInterface::class);
        $this->request = $this->createMock(RequestService::class);

        $this->logHandler = new LogHandler($this->application, $this->logger, $this->language, $this->request);
    }
}
