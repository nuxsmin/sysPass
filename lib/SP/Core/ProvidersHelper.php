<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

use SP\Providers\Acl\AclHandler;
use SP\Providers\Log\DatabaseLogHandler;
use SP\Providers\Log\FileLogHandler;
use SP\Providers\Log\RemoteSyslogHandler;
use SP\Providers\Log\SyslogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Providers\Notification\NotificationHandler;
use SP\Providers\ProviderInterface;

/**
 * The Provider helper class will have oll the providers availabe in the application
 */
final readonly class ProvidersHelper
{

    public function __construct(
        private FileLogHandler       $fileLogHandler,
        private ?DatabaseLogHandler  $databaseLogHandler = null,
        private ?MailHandler         $mailHandler = null,
        private ?SyslogHandler       $syslogHandler = null,
        private ?RemoteSyslogHandler $remoteSyslogHandler = null,
        private ?AclHandler          $aclHandler = null,
        private ?NotificationHandler $notificationHandler = null
    ) {
    }

    public function getFileLogHandler(): FileLogHandler
    {
        self::ensureIsInitialized($this->fileLogHandler);

        return $this->fileLogHandler;
    }

    private static function ensureIsInitialized(?ProviderInterface $provider = null): void
    {
        if ($provider !== null && !$provider->isInitialized()) {
            $provider->initialize();
        }
    }

    public function getDatabaseLogHandler(): DatabaseLogHandler
    {
        self::ensureIsInitialized($this->databaseLogHandler);

        return $this->databaseLogHandler;
    }

    public function getMailHandler(): MailHandler
    {
        self::ensureIsInitialized($this->mailHandler);

        return $this->mailHandler;
    }

    public function getSyslogHandler(): SyslogHandler
    {
        self::ensureIsInitialized($this->syslogHandler);

        return $this->syslogHandler;
    }

    public function getRemoteSyslogHandler(): RemoteSyslogHandler
    {
        self::ensureIsInitialized($this->remoteSyslogHandler);

        return $this->remoteSyslogHandler;
    }

    public function getAclHandler(): AclHandler
    {
        self::ensureIsInitialized($this->aclHandler);

        return $this->aclHandler;
    }

    public function getNotificationHandler(): NotificationHandler
    {
        self::ensureIsInitialized($this->notificationHandler);

        return $this->notificationHandler;
    }
}
