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

use DI\Container;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use SP\Core\AppInfoInterface;
use SP\Providers\Provider;

/**
 * Class MailProvider
 *
 * @package SP\Providers\Mail
 */
final class MailProvider extends Provider
{
    /**
     * @var  PHPMailer
     */
    private $mailer;
    /**
     * @var bool
     */
    private $debug = false;

    /**
     * Inicializar la clase PHPMailer.
     *
     * @param MailParams $mailParams
     *
     * @return PHPMailer
     * @throws MailProviderException
     */
    public function getMailer(MailParams $mailParams)
    {
        $appName = AppInfoInterface::APP_NAME;

        try {
            $this->mailer->SMTPAutoTLS = false;
            $this->mailer->isSMTP();
            $this->mailer->CharSet = 'utf-8';
            $this->mailer->Host = $mailParams->server;
            $this->mailer->Port = $mailParams->port;

            if ($mailParams->mailAuthenabled) {
                $this->mailer->SMTPAuth = true;
                $this->mailer->Username = $mailParams->user;
                $this->mailer->Password = $mailParams->pass;
            }

            $this->mailer->SMTPSecure = strtolower($mailParams->security);

            if ($this->debug) {
                $this->mailer->SMTPDebug = 2;
                $this->mailer->Debugoutput = function ($str, $level) {
                    logger($str, strtoupper($level));
                };
            }

            $this->mailer->setFrom($mailParams->from, $appName);
            $this->mailer->addReplyTo($mailParams->from, $appName);
            $this->mailer->WordWrap = 100;

            return $this->mailer;
        } catch (Exception $e) {
            processException($e);

            throw new MailProviderException(
                __u('Unable to initialize'),
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

    /**
     * @param Container $dic
     *
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function initialize(Container $dic)
    {
        $this->mailer = $dic->get(PHPMailer::class);
    }
}