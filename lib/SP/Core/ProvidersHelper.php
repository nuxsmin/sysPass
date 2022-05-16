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

namespace SP\Core;


use SP\Providers\Acl\AclHandler;
use SP\Providers\Log\DatabaseLogHandler;
use SP\Providers\Log\FileLogHandler;
use SP\Providers\Log\RemoteSyslogHandler;
use SP\Providers\Log\SyslogHandler;
use SP\Providers\Mail\MailHandler;
use SP\Providers\Notification\NotificationHandler;

/**
 * The Provider helper class will have oll the providers availabe in the application
 */
final class ProvidersHelper
{
    private FileLogHandler      $fileLogHandler;
    private DatabaseLogHandler  $databaseLogHandler;
    private MailHandler         $mailHandler;
    private SyslogHandler       $syslogHandler;
    private RemoteSyslogHandler $remoteSyslogHandler;
    private AclHandler          $aclHandler;
    private NotificationHandler $notificationHandler;

    /**
     * Module constructor.
     *
     * @param  \SP\Providers\Log\FileLogHandler  $fileLogHandler
     * @param  \SP\Providers\Log\DatabaseLogHandler  $databaseLogHandler
     * @param  \SP\Providers\Mail\MailHandler  $mailHandler
     * @param  \SP\Providers\Log\SyslogHandler  $syslogHandler
     * @param  \SP\Providers\Log\RemoteSyslogHandler  $remoteSyslogHandler
     * @param  \SP\Providers\Acl\AclHandler  $aclHandler
     * @param  \SP\Providers\Notification\NotificationHandler  $notificationHandler
     */
    public function __construct(
        FileLogHandler $fileLogHandler,
        DatabaseLogHandler $databaseLogHandler,
        MailHandler $mailHandler,
        SyslogHandler $syslogHandler,
        RemoteSyslogHandler $remoteSyslogHandler,
        AclHandler $aclHandler,
        NotificationHandler $notificationHandler
    ) {
        $this->fileLogHandler = $fileLogHandler;
        $this->databaseLogHandler = $databaseLogHandler;
        $this->mailHandler = $mailHandler;
        $this->syslogHandler = $syslogHandler;
        $this->remoteSyslogHandler = $remoteSyslogHandler;
        $this->aclHandler = $aclHandler;
        $this->notificationHandler = $notificationHandler;
    }

    /**
     * @return \SP\Providers\Log\FileLogHandler
     */
    public function getFileLogHandler(): FileLogHandler
    {
        return $this->fileLogHandler;
    }

    /**
     * @return \SP\Providers\Log\DatabaseLogHandler
     */
    public function getDatabaseLogHandler(): DatabaseLogHandler
    {
        return $this->databaseLogHandler;
    }

    /**
     * @return \SP\Providers\Mail\MailHandler
     */
    public function getMailHandler(): MailHandler
    {
        return $this->mailHandler;
    }

    /**
     * @return \SP\Providers\Log\SyslogHandler
     */
    public function getSyslogHandler(): SyslogHandler
    {
        return $this->syslogHandler;
    }

    /**
     * @return \SP\Providers\Log\RemoteSyslogHandler
     */
    public function getRemoteSyslogHandler(): RemoteSyslogHandler
    {
        return $this->remoteSyslogHandler;
    }

    /**
     * @return \SP\Providers\Acl\AclHandler
     */
    public function getAclHandler(): AclHandler
    {
        return $this->aclHandler;
    }

    /**
     * @return \SP\Providers\Notification\NotificationHandler
     */
    public function getNotificationHandler(): NotificationHandler
    {
        return $this->notificationHandler;
    }
}