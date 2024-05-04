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
use SP\Domain\Log\Providers\FileHandler;
use SP\Domain\Log\Providers\RemoteSyslogHandler;
use SP\Domain\Log\Providers\SyslogHandler;
use SP\Domain\Notification\Providers\MailHandler;
use SP\Domain\Notification\Providers\NotificationHandler;

/**
 * The Provider helper class will have oll the providers availabe in the application
 */
final readonly class ProvidersHelper
{

    public function __construct(
        private FileHandler      $fileLogHandler,
        private ?DatabaseHandler $databaseLogHandler = null,
        private ?MailHandler         $mailHandler = null,
        private ?SyslogHandler       $syslogHandler = null,
        private ?RemoteSyslogHandler $remoteSyslogHandler = null,
        private ?AclHandler          $aclHandler = null,
        private ?NotificationHandler $notificationHandler = null
    ) {
    }

    public function getFileLogHandler(): FileHandler
    {
        return $this->fileLogHandler;
    }

    public function getDatabaseLogHandler(): DatabaseHandler
    {
        return $this->databaseLogHandler;
    }

    public function getMailHandler(): MailHandler
    {
        return $this->mailHandler;
    }

    public function getSyslogHandler(): SyslogHandler
    {
        return $this->syslogHandler;
    }

    public function getRemoteSyslogHandler(): RemoteSyslogHandler
    {
        return $this->remoteSyslogHandler;
    }

    public function getAclHandler(): AclHandler
    {
        return $this->aclHandler;
    }

    public function getNotificationHandler(): NotificationHandler
    {
        return $this->notificationHandler;
    }
}
