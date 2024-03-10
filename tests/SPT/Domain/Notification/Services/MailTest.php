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

namespace SPT\Domain\Notification\Services;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Core\Context\ContextException;
use SP\Core\Messages\MailMessage;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigFileService;
use SP\Domain\Notification\Services\Mail;
use SP\Domain\Providers\MailerInterface;
use SP\Providers\Mail\MailParams;
use SPT\Generators\ConfigDataGenerator;
use SPT\UnitaryTestCase;

/**
 * Class MailTest
 */
#[Group('unitary')]
class MailTest extends UnitaryTestCase
{

    private Mail                       $mail;
    private MailerInterface|MockObject $mailer;

    /**
     * @throws ServiceException
     */
    public function testSend()
    {
        $message = new MailMessage();
        $message->setTitle(self::$faker->colorName);
        $message->setDescription([self::$faker->text]);

        $to = self::$faker->email();

        $this->mailer
            ->expects($this->once())
            ->method('addAddress')
            ->with($to)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('isHtml')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('subject')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('body')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->mail->send('test', $to, $message);
    }

    /**
     * @throws ServiceException
     */
    public function testSendWithAddresses()
    {
        $message = new MailMessage();
        $message->setTitle(self::$faker->colorName);
        $message->setDescription([self::$faker->text]);

        $to = array_map(static fn() => self::$faker->email(), range(0, 4));

        $this->mailer
            ->expects($this->exactly(5))
            ->method('addAddress')
            ->with(...self::withConsecutive([$to[0]], [$to[1]], [$to[2]], [$to[3]], [$to[4]]))
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('isHtml')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('subject')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('body')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->mail->send('test', $to, $message);
    }

    /**
     * @throws ServiceException
     */
    public function testCheck()
    {
        $configData = $this->config->getConfigData();

        $mailParams = new MailParams(
            $configData->getMailServer(),
            $configData->getMailPort(),
            $configData->getMailUser(),
            $configData->getMailPass(),
            $configData->getMailSecurity(),
            $configData->getMailFrom(),
            $configData->isMailAuthenabled()
        );

        $to = self::$faker->email();

        $this->mailer
            ->expects($this->once())
            ->method('configure')
            ->with($mailParams)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('addAddress')
            ->with($to)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('isHtml')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('subject')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('body')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('send');

        $this->mail->check($mailParams, $to);
    }

    /**
     * @throws ServiceException
     */
    public function testCheckWithException()
    {
        $configData = $this->config->getConfigData();

        $mailParams = new MailParams(
            $configData->getMailServer(),
            $configData->getMailPort(),
            $configData->getMailUser(),
            $configData->getMailPass(),
            $configData->getMailSecurity(),
            $configData->getMailFrom(),
            $configData->isMailAuthenabled()
        );

        $to = self::$faker->email();

        $this->mailer
            ->expects($this->once())
            ->method('configure')
            ->with($mailParams)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('addAddress')
            ->with($to)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('isHtml')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('subject')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('body')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while sending the email');

        $this->mail->check($mailParams, $to);
    }

    /**
     * @throws ServiceException
     */
    public function testSendWithException()
    {
        $message = new MailMessage();
        $message->setTitle(self::$faker->colorName);
        $message->setDescription([self::$faker->text]);

        $to = self::$faker->email();

        $this->mailer
            ->expects($this->once())
            ->method('addAddress')
            ->with($to)
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('isHtml')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('subject')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('body')
            ->willReturn($this->mailer);

        $this->mailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('Error while sending the email');

        $this->mail->send('test', $to, $message);
    }

    public function testGetParamsFromConfig()
    {
        $configData = $this->config->getConfigData();
        $out = Mail::getParamsFromConfig($configData);

        $this->assertEquals($configData->getMailServer(), $out->getServer());
        $this->assertEquals($configData->getMailPort(), $out->getPort());
        $this->assertEquals($configData->getMailUser(), $out->getUser());
        $this->assertEquals($configData->getMailPass(), $out->getPass());
        $this->assertEquals($configData->getMailSecurity(), $out->getSecurity());
        $this->assertEquals($configData->getMailFrom(), $out->getFrom());
        $this->assertEquals($configData->isMailAuthenabled(), $out->isMailAuthenabled());
    }

    /**
     * @throws Exception
     */
    protected function getConfig(): ConfigFileService
    {
        $configData = ConfigDataGenerator::factory()->buildConfigData();
        $configData->setMailServer(self::$faker->domainName());
        $configData->setMailPort(587);
        $configData->setMailUser(self::$faker->userName());
        $configData->setMailPass(self::$faker->password());
        $configData->setMailSecurity('TLS');
        $configData->setMailFrom(self::$faker->email());
        $configData->setMailAuthenabled(self::$faker->boolean());
        $configData->setMailEnabled(true);

        $config = $this->createStub(ConfigFileService::class);
        $config->method('getConfigData')->willReturn($configData);

        return $config;
    }


    /**
     * @throws ContextException
     * @throws Exception
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mail = new Mail($this->application, $this->mailer);
    }
}
