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

namespace SP\Tests\Domain\Notification\Providers;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\Domain\Config\Adapters\ConfigData;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Http\Ports\RequestService;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\Notification\Providers\MailHandler;
use SP\Tests\Generators\ConfigDataGenerator;
use SP\Tests\UnitaryTestCase;

/**
 * Class MailHandlerTest
 */
#[Group('unitary')]
class MailHandlerTest extends UnitaryTestCase
{

    private MockObject|MailService    $mailService;
    private RequestService|MockObject $requestService;
    private MailHandler               $mailHandler;
    private ConfigData                $configData;

    public function testUpdate()
    {
        $eventMessage = EventMessage::factory()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('email', ['an_email']);

        $event = new Event($this, $eventMessage);

        $this->requestService
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn(self::$faker->ipv4());

        $this->mailService
            ->expects($this->once())
            ->method('send')
            ->with(
                'a_description',
                ['an_email'],
                self::callback(static function (MailMessage $mailMessage) {
                    $matches = preg_match(
                        '/\na_description<br>a_detail: a_value\n\nPerformed by: [\w.]+ \([\w.]+\)\nIP Address: [\d.]+/',
                        $mailMessage->composeText()
                    );

                    return empty($mailMessage->getTitle())
                           && empty($mailMessage->getFooter())
                           && $matches === 1;
                })
            );

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testUpdateWithConfiguredEmail()
    {
        $this->configData->setMailRecipients(['an_email']);

        $eventMessage = EventMessage::factory()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value');

        $event = new Event($this, $eventMessage);

        $this->requestService
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn(self::$faker->ipv4());

        $this->mailService
            ->expects($this->once())
            ->method('send')
            ->with(
                'a_description',
                ['an_email'],
                self::callback(static function (MailMessage $mailMessage) {
                    $matches = preg_match(
                        '/\na_description<br>a_detail: a_value\n\nPerformed by: [\w.]+ \([\w.]+\)\nIP Address: [\d.]+/',
                        $mailMessage->composeText()
                    );

                    return empty($mailMessage->getTitle())
                           && empty($mailMessage->getFooter())
                           && $matches === 1;
                })
            );

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testUpdateWithNoEmail()
    {
        $eventMessage = EventMessage::factory()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value');

        $event = new Event($this, $eventMessage);

        $this->mailService
            ->expects($this->never())
            ->method('send');

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testUpdateWithNoDescriptionAndDetails()
    {
        $eventMessage = EventMessage::factory()->setExtra('email', ['an_email']);

        $event = new Event($this, $eventMessage);

        $this->requestService
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn(self::$faker->ipv4());

        $this->mailService
            ->expects($this->once())
            ->method('send')
            ->with(
                'test_a.update',
                ['an_email'],
                self::callback(static function (MailMessage $mailMessage) {
                    $matches = preg_match(
                        '/\nEvent: test_a.update\n\nPerformed by: [\w.]+ \([\w.]+\)\nIP Address: [\d.]+/',
                        $mailMessage->composeText()
                    );

                    return empty($mailMessage->getTitle())
                           && empty($mailMessage->getFooter())
                           && $matches === 1;
                })
            );

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testUpdateWithEmptyRecipients()
    {
        $eventMessage = EventMessage::factory()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->setExtra('email', ['an_email', '']);

        $event = new Event($this, $eventMessage);

        $this->requestService
            ->expects($this->once())
            ->method('getClientAddress')
            ->with(true)
            ->willReturn(self::$faker->ipv4());

        $this->mailService
            ->expects($this->once())
            ->method('send')
            ->with(
                'a_description',
                ['an_email'],
                self::callback(static function (MailMessage $mailMessage) {
                    $matches = preg_match(
                        '/\na_description<br>a_detail: a_value\n\nPerformed by: [\w.]+ \([\w.]+\)\nIP Address: [\d.]+/',
                        $mailMessage->composeText()
                    );

                    return empty($mailMessage->getTitle())
                           && empty($mailMessage->getFooter())
                           && $matches === 1;
                })
            );

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testUpdateWithException()
    {
        $eventMessage = EventMessage::factory()
                                    ->addDescription('a_description')
                                    ->addDetail('a_detail', 'a_value')
                                    ->addExtra('email', ['an_email']);

        $event = new Event($this, $eventMessage);

        $this->mailService
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('test'));

        $this->mailHandler->update('test_a.update', $event);
    }

    public function testGetEventsString()
    {
        $expected = 'test_a\.|test_b\.|clear\.eventlog|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword';
        $out = $this->mailHandler->getEventsString();

        $this->assertEquals($expected, $out);
    }

    public function testGetEventsStringWithNoConfiguredEvents()
    {
        $expected = 'clear\.eventlog|refresh\.masterPassword|update\.masterPassword\.start|update\.masterPassword\.end|request\.account|edit\.user\.password|save\.config\.|create\.tempMasterPassword';

        $this->configData->setMailEvents([]);

        $databaseHandler = new MailHandler($this->application, $this->mailService, $this->requestService);
        $out = $databaseHandler->getEventsString();

        $this->assertEquals($expected, $out);
    }

    protected function buildConfig(): ConfigFileService
    {
        $this->configData = ConfigDataGenerator::factory()->buildConfigData();
        $this->configData->setMailEvents(['test_a.', 'test_b.']);

        $config = $this->createMock(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($this->configData);

        return $config;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mailService = $this->createMock(MailService::class);
        $this->requestService = $this->createMock(RequestService::class);

        $this->mailHandler = new MailHandler($this->application, $this->mailService, $this->requestService);
    }
}
