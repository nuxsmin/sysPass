<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      https://syspass.org
 * @copyright 2012-2018, Rubén Domínguez nuxsmin@$syspass.org
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

use PHPMailer\PHPMailer\PHPMailer;
use SP\Providers\Provider;
use SP\Util\Util;

/**
 * Class MailProvider
 *
 * @package SP\Providers\Mail
 */
class MailProvider extends Provider
{
    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Inicializar la clase PHPMailer.
     *
     * @param MailParams $mailParams
     * @return PHPMailer
     * @throws MailProviderException
     */
    public function getMailer(MailParams $mailParams)
    {
        $appName = Util::getAppInfo('appname');

        try {
            $mailer = $this->dic->get(PHPMailer::class);
            $mailer->SMTPAutoTLS = false;
            $mailer->isSMTP();
            $mailer->CharSet = 'utf-8';
            $mailer->Host = $mailParams->server;
            $mailer->Port = $mailParams->port;

            if ($mailParams->mailAuthenabled) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $mailParams->user;
                $mailer->Password = $mailParams->pass;
            }

            $mailer->SMTPSecure = strtolower($mailParams->security);

            if ($this->debug) {
                $mailer->SMTPDebug = 2;
                $mailer->Debugoutput = function ($str, $level) {
                    debugLog($str);
                };
            }

            $mailer->setFrom($mailParams->from, $appName);
            $mailer->addReplyTo($mailParams->from, $appName);
            $mailer->WordWrap = 100;

            return $mailer;
        } catch (\Exception $e) {
            processException($e);

            throw new MailProviderException(
                __u('No es posible inicializar'),
                MailProviderException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }
}