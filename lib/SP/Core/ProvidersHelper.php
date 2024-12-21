<?php

declare(strict_types=1);
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

namespace SP\Core;

use SP\Domain\Auth\Providers\AclHandler;
use SP\Domain\Log\Providers\DatabaseHandler;
use SP\Domain\Log\Providers\LogHandler;
use SP\Domain\Notification\Services\MailEvent;
use SP\Domain\Notification\Services\NotificationEvent;

/**
 * The Provider helper class will have oll the providers availabe in the application
 */
final readonly class ProvidersHelper
{

    public function __construct(
        private LogHandler         $logHandler,
        private ?DatabaseHandler   $databaseLogHandler = null,
        private ?MailEvent         $mailHandler = null,
        private ?AclHandler          $aclHandler = null,
        private ?NotificationEvent $notificationHandler = null
    ) {
    }

    public function getLogHandler(): LogHandler
    {
        return $this->logHandler;
    }

    public function getDatabaseLogHandler(): DatabaseHandler
    {
        return $this->databaseLogHandler;
    }

    public function getMailHandler(): MailEvent
    {
        return $this->mailHandler;
    }

    public function getAclHandler(): AclHandler
    {
        return $this->aclHandler;
    }

    public function getNotificationHandler(): NotificationEvent
    {
        return $this->notificationHandler;
    }
}
