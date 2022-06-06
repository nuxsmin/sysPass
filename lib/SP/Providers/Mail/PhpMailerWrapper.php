<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2022, Rubén Domínguez nuxsmin@$syspass.org
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
use SP\Domain\Providers\MailerInterface;

/**
 * A wrapper for PHPMailer
 */
final class PhpMailerWrapper implements MailerInterface
{
    private PHPMailer $mailer;

    public function __construct(PHPMailer $mailer)
    {
        $this->mailer = $mailer;
    }

    public function isHtml(): MailerInterface
    {
        $this->mailer->isHTML();

        return $this;
    }

    /**
     * @throws \SP\Providers\Mail\MailProviderException
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
     * @throws \SP\Providers\Mail\MailProviderException
     */
    public function send(): bool
    {
        try {
            return $this->mailer->send();
        } catch (Exception $e) {
            throw new MailProviderException($e);
        }
    }

    public function getMailer(): PHPMailer
    {
        return $this->mailer;
    }

    public function getToAddresses(): array
    {
        return $this->mailer->getToAddresses();
    }
}