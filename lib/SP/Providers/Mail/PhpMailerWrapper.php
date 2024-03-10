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

namespace SP\Providers\Mail;

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Providers\MailerInterface;

use function SP\__u;
use function SP\logger;
use function SP\processException;

/**
 * A wrapper for PHPMailer
 */
final class PhpMailerWrapper implements MailerInterface
{

    public function __construct(private readonly PHPMailer $mailer, private readonly bool $debug = false)
    {
    }

    public function isHtml(): MailerInterface
    {
        $this->mailer->isHTML();

        return $this;
    }

    /**
     * @throws MailProviderException
     */
    public function addAddress(string $address): MailerInterface
    {
        try {
            $this->mailer->addAddress($address);
        } catch (Exception $e) {
            throw new MailProviderException($e);
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
     * @throws MailProviderException
     */
    public function send(): bool
    {
        try {
            return $this->mailer->send();
        } catch (Exception $e) {
            throw new MailProviderException($e);
        }
    }

    public function getToAddresses(): array
    {
        return $this->mailer->getToAddresses();
    }

    /**
     * Configure the mailer with the configuration settings
     *
     * @throws MailProviderException
     */
    public function configure(MailParams $mailParams): MailerInterface
    {
        $instance = clone $this;

        $appName = AppInfoInterface::APP_NAME;

        try {
            $instance->mailer->SMTPAutoTLS = false;
            $instance->mailer->isSMTP();
            $instance->mailer->CharSet = 'utf-8';
            $instance->mailer->Host = $mailParams->getServer();
            $instance->mailer->Port = $mailParams->getPort();
            $instance->mailer->SMTPSecure = strtolower($mailParams->getSecurity());

            if ($mailParams->isMailAuthenabled()) {
                $instance->mailer->SMTPAuth = true;
                $instance->mailer->Username = $mailParams->getUser();
                $instance->mailer->Password = $mailParams->getPass();
            }

            if ($instance->debug) {
                $instance->mailer->SMTPDebug = 2;
                $instance->mailer->Debugoutput = static fn($str, $level) => logger($str, strtoupper($level));
            }

            $instance->mailer->setFrom($mailParams->getFrom(), $appName);
            $instance->mailer->addReplyTo($mailParams->getFrom(), $appName);
            $instance->mailer->WordWrap = 100;

            return $instance;
        } catch (Exception $e) {
            processException($e);

            throw MailProviderException::error(__u('Unable to initialize'), $e->getMessage(), $e->getCode(), $e);
        }
    }
}
