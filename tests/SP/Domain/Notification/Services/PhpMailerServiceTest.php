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

namespace SP\Tests\Domain\Notification\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SP\Domain\Notification\Dtos\MailParams;
use SP\Domain\Notification\MailerException;
use SP\Domain\Notification\Services\PhpMailerService;
use SP\Tests\UnitaryTestCase;

/**
 * Class PhpMailerServiceTest
 */
#[Group('unitary')]
class PhpMailerServiceTest extends UnitaryTestCase
{

    private PhpMailerService     $phpMailerService;
    private PHPMailer|MockObject $phpMailer;

    public function testBody()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('set')
            ->with('Body', 'a_body');

        $this->phpMailerService->body('a_body');
    }

    public function testGetToAddresses()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('getToAddresses')
            ->willReturn(['mail_a', 'email_b']);

        $out = $this->phpMailerService->getToAddresses();

        $this->assertEquals(['mail_a', 'email_b'], $out);
    }

    public function testIsHtml()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('isHTML');

        $this->phpMailerService->isHtml();
    }

    /**
     * @throws MailerException
     */
    public function testAddAddress()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('addAddress')
            ->with('an_email');

        $this->phpMailerService->addAddress('an_email');
    }

    /**
     * @throws MailerException
     */
    public function testAddAddressWithException()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('addAddress')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(MailerException::class);
        $this->expectExceptionMessage('test');

        $this->phpMailerService->addAddress('an_email');
    }

    /**
     * @throws MailerException
     */
    public function testConfigure()
    {
        $mailParams = new MailParams(
            self::$faker->ipv4(),
            self::$faker->randomNumber(2),
            self::$faker->userName,
            self::$faker->password(),
            self::$faker->colorName(),
            self::$faker->email(),
            self::$faker->boolean()
        );

        $this->phpMailer
            ->expects($this->once())
            ->method('isSMTP');

        $this->phpMailer
            ->expects($this->once())
            ->method('setFrom')
            ->with($mailParams->getFrom(), 'sysPass');

        $this->phpMailer
            ->expects($this->once())
            ->method('addReplyTo')
            ->with($mailParams->getFrom(), 'sysPass');

        $out = $this->phpMailerService->configure($mailParams);

        $this->assertNotSame($this->phpMailerService, $out);
    }

    /**
     * @throws MailerException
     */
    public function testConfigureWithException()
    {
        $mailParams = new MailParams(
            self::$faker->ipv4(),
            self::$faker->randomNumber(2),
            self::$faker->userName,
            self::$faker->password(),
            self::$faker->colorName(),
            self::$faker->email(),
            self::$faker->boolean()
        );

        $this->phpMailer
            ->expects($this->once())
            ->method('isSMTP');

        $this->phpMailer
            ->expects($this->once())
            ->method('setFrom')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(MailerException::class);
        $this->expectExceptionMessage('Unable to initialize');

        $this->phpMailerService->configure($mailParams);
    }

    public function testSubject()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('set')
            ->with('Subject', 'a_subject');

        $this->phpMailerService->subject('a_subject');
    }

    /**
     * @throws MailerException
     */
    public function testSend()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('send')
            ->willReturn(true);

        $this->assertTrue($this->phpMailerService->send());
    }

    /**
     * @throws MailerException
     */
    public function testSendWithException()
    {
        $this->phpMailer
            ->expects($this->once())
            ->method('send')
            ->willThrowException(new RuntimeException('test'));

        $this->expectException(MailerException::class);
        $this->expectExceptionMessage('test');

        $this->assertTrue($this->phpMailerService->send());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->phpMailer = $this->createMock(PHPMailer::class);
        $this->phpMailerService = new PhpMailerService($this->phpMailer, true);
    }
}
