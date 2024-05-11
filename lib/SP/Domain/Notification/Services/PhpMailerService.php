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

namespace SP\Domain\Notification\Services;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Notification\Dtos\MailParams;
use SP\Domain\Notification\MailerException;
use SP\Domain\Notification\Ports\MailerInterface;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * A wrapper for PHPMailer
 */
final readonly class PhpMailerService implements MailerInterface
{

    public function __construct(private PHPMailer $mailer, private bool $debug = false)
    {
    }

    public function isHtml(): MailerInterface
    {
        $this->mailer->isHTML();

        return $this;
    }

    /**
     * @throws MailerException
     */
    public function addAddress(string $address): MailerInterface
    {
        try {
            $this->mailer->addAddress($address);
        } catch (Exception $e) {
            throw MailerException::from($e);
        }

        return $this;
    }

    public function subject(string $subject): MailerInterface
    {
        $this->mailer->set('Subject', $subject);

        return $this;
    }

    public function body(string $body): MailerInterface
    {
        $this->mailer->set('Body', $body);

        return $this;
    }

    /**
     * @throws MailerException
     */
    public function send(): bool
    {
        try {
            return $this->mailer->send();
        } catch (Exception $e) {
            throw MailerException::from($e);
        }
    }

    public function getToAddresses(): array
    {
        return $this->mailer->getToAddresses();
    }

    /**
     * Configure the mailer with the configuration settings
     *
     * @throws MailerException
     */
    public function configure(MailParams $mailParams): MailerInterface
    {
        $mailer = clone $this->mailer;

        $appName = AppInfoInterface::APP_NAME;

        try {
            $mailer->SMTPAutoTLS = false;
            $mailer->isSMTP();
            $mailer->CharSet = 'utf-8';
            $mailer->Host = $mailParams->getServer();
            $mailer->Port = $mailParams->getPort();
            $mailer->SMTPSecure = strtolower($mailParams->getSecurity());

            if ($mailParams->isMailAuthenabled()) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $mailParams->getUser();
                $mailer->Password = $mailParams->getPass();
            }

            if ($this->debug) {
                $mailer->SMTPDebug = 2;
                $mailer->Debugoutput = static fn($str, $level) => logger($str, strtoupper($level));
            }

            $mailer->setFrom($mailParams->getFrom(), $appName);
            $mailer->addReplyTo($mailParams->getFrom(), $appName);
            $mailer->WordWrap = 100;

            return new self($mailer, $this->debug);
        } catch (Exception $e) {
            processException($e);

            throw MailerException::error(__u('Unable to initialize'), $e->getMessage(), $e->getCode(), $e);
        }
    }
}
