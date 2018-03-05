<?php
/**
 * sysPass
 *
 * @author nuxsmin 
 * @link https://syspass.org
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

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use SP\Bootstrap;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\Html\Html;
use SP\Providers\Provider;
use SP\Util\Util;

/**
 * Class Mailer
 *
 * @package SP\Providers\Mail
 */
class MailHandler extends Provider
{
    /**
     * @var PHPMailer
     */
    private $mailer;
    /**
     * @var array
     */
    private $appInfo;

    /**
     * @param string      $subject
     * @param string      $to
     * @param MailMessage $mailMessage
     * @throws MailHandlerException
     */
    public function send($subject, $to, MailMessage $mailMessage)
    {
        $this->mailer->isHTML();
        $this->mailer->addAddress($to);
        $this->mailer->Subject = sprintf('%s - %s', $this->appInfo['appname'], $subject);;
        $this->mailer->Body = $mailMessage->setFooter($this->getEmailFooter())->composeHtml();

        $this->sendMail();
    }

    /**
     * Devolver el pie del email con la firma de la aplicación
     *
     * @return array
     */
    protected function getEmailFooter()
    {
        return [
            '',
            '--',
            sprintf('%s - %s', $this->appInfo['appname'], $this->appInfo['appdesc']),
            Html::anchorText(Bootstrap::$WEBURI)
        ];
    }

    /**
     * @throws MailHandlerException
     */
    private function sendMail()
    {
        try {
            $this->mailer->send();

            $this->eventDispatcher->notifyEvent('mail.send', new Event($this,
                    EventMessage::factory()
                        ->addDescription(__u('Correo enviado'))
                        ->addDetail(__u('Destinatario'), implode(',', $this->mailer->getToAddresses())))
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new MailHandlerException(__u('Error al enviar correo'));
        }
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws MailHandlerException
     */
    protected function initialize()
    {
        $this->mailer = $this->getMailer();
        $this->appInfo = Util::getAppInfo();
    }

    /**
     * Inicializar la clase PHPMailer.
     *
     * @return PHPMailer
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws MailHandlerException
     */
    private function getMailer()
    {
        try {
            $configData = $this->config->getConfigData();

            $mailer = $this->dic->get(PHPMailer::class);
            $mailer->SMTPAutoTLS = false;
            $mailer->isSMTP();
            $mailer->CharSet = 'utf-8';
            $mailer->Host = $configData->getMailServer();
            $mailer->Port = $configData->getMailPort();

            if ($configData->isMailAuthenabled()) {
                $mailer->SMTPAuth = true;
                $mailer->Username = $configData->getMailUser();
                $mailer->Password = $configData->getMailPass();
            }

            $mailer->SMTPSecure = strtolower($configData->getMailSecurity());
            //$mail->SMTPDebug = 2;
            //$mail->Debugoutput = 'error_log';

            $mailer->setFrom($configData->getMailFrom(), $this->appInfo['appname']);
            $mailer->addReplyTo($configData->getMailFrom(), $this->appInfo['appname']);
            $mailer->WordWrap = 100;

            return $mailer;
        } catch (\Exception $e) {
            processException($e);

            throw new MailHandlerException(
                __u('No es posible inicializar'),
                MailHandlerException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }
}