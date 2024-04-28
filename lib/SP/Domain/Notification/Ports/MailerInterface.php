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

namespace SP\Domain\Notification\Ports;

use SP\Domain\Notification\Dtos\MailParams;

/**
 * A wrapper for a mailer
 */
interface MailerInterface
{
    public function isHtml(): MailerInterface;

    public function addAddress(string $address): MailerInterface;

    public function subject(string $subject): MailerInterface;

    public function body(string $body): MailerInterface;

    public function send(): bool;

    public function getToAddresses(): array;

    public function configure(MailParams $mailParams): MailerInterface;
}
