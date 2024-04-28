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

use PHPMailer\PHPMailer\Exception;
use SP\Core\Messages\MailMessage;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Providers\Mail\MailParams;

/**
 * Class MailService
 *
 * @package SP\Domain\Common\Services
 */
interface MailService
{
    public static function getParamsFromConfig(ConfigDataInterface $configData): MailParams;

    /**
     * Checks mail params by sending a test email
     *
     * @throws ServiceException
     */
    public function check(MailParams $mailParams, string $to): void;

    /**
     * @param string $subject
     * @param array|string $to
     * @param MailMessage $mailMessage
     *
     * @throws Exception
     * @throws ServiceException
     */
    public function send(string $subject, string|array $to, MailMessage $mailMessage): void;
}
