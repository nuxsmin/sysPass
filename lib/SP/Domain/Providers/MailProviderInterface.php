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

namespace SP\Domain\Providers;


use SP\Providers\Mail\MailParams;
use SP\Providers\Mail\MailProviderException;

/**
 * Class MailProvider
 *
 * @package SP\Providers\Mail
 */
interface MailProviderInterface
{
    /**
     * Inicializar la clase PHPMailer.
     *
     * @param  MailParams  $mailParams
     *
     * @throws MailProviderException
     */
    public function getMailerFrom(MailParams $mailParams): MailerInterface;

    public function isDebug(): bool;

    public function setDebug(bool $debug): void;

    public function initialize(): void;
}