<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2019, Rubén Domínguez nuxsmin@$syspass.org
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
 *  along with sysPass.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SP\Providers\Mail;

/**
 * Class MailParams
 *
 * @package SP\Providers\Mail
 */
final class MailParams
{
    /**
     * @var string
     */
    public $server;
    /**
     * @var int
     */
    public $port;
    /**
     * @var string
     */
    public $user;
    /**
     * @var string
     */
    public $pass;
    /**
     * @var string
     */
    public $security;
    /**
     * @var string
     */
    public $from;
    /**
     * @var bool
     */
    public $mailAuthenabled;
}