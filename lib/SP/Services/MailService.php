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

namespace SP\Services;

use PHPMailer\PHPMailer\PHPMailer;
use SP\Bootstrap;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\Html\Html;
use SP\Providers\Mail\MailParams;
use SP\Providers\Mail\MailProvider;
use SP\Providers\Mail\MailProviderException;
use SP\Util\Util;

/**
 * Class MailService
 *
 * @package SP\Services
 */
class MailService extends Service
{
    /**
     * @var PHPMailer
     */
    protected $mailer;
    /**
     * @var array
     */
    private $appInfo;

    /**
     * Checks mail params by sending a test email
     *
     * @param MailParams $mailParams
     * @param string     $to
     * @throws ServiceException
     */
    public function check(MailParams $mailParams, $to)
    {
        try {
            $mailer = $this->dic->get(MailProvider::class)->getMailer($mailParams);

            $mailMessage = new MailMessage();
            $mailMessage->setTitle(__u('Prueba de correo'));
            $mailMessage->addDescription(__u('Esto es una prueba de correo para verificar el correcto funcionamiento de la configuración.'));
            $mailMessage->setFooter($this->getEmailFooter());

            $mailer->isHTML();
            $mailer->addAddress($to);
            $mailer->Subject = $this->getSubjectForAction($mailMessage->getTitle());
            $mailer->Body = $mailMessage->composeHtml();
            $mailer->send();
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(
                __u('Error al enviar correo'),
                ServiceException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e);
        }
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
     * @param $action
     * @return string
     */
    protected function getSubjectForAction($action)
    {
        return sprintf('%s - %s', $this->appInfo['appname'], $action);
    }

    /**
     * @param string      $subject
     * @param string      $to
     * @param MailMessage $mailMessage
     * @throws ServiceException
     */
    public function send($subject, $to, MailMessage $mailMessage)
    {
        $this->mailer->isHTML();
        $this->mailer->addAddress($to);
        $this->mailer->Subject = $this->getSubjectForAction($subject);
        $this->mailer->Body = $mailMessage->setFooter($this->getEmailFooter())->composeHtml();

        $this->sendMail();
    }

    /**
     * @throws ServiceException
     */
    private function sendMail()
    {
        try {
            $this->mailer->send();

            $this->eventDispatcher->notifyEvent('send.mail',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Correo enviado'))
                    ->addDetail(__u('Destinatario'), implode(',', array_map(function ($value) {
                        return $value[0];
                    }, $this->mailer->getToAddresses()))))
            );
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            throw new ServiceException(__u('Error al enviar correo'));
        }
    }

    /**
     * @param string      $subject
     * @param array       $to
     * @param MailMessage $mailMessage
     * @throws ServiceException
     */
    public function sendBatch($subject, array $to, MailMessage $mailMessage)
    {
        $this->mailer->isHTML();

        foreach ($to as $address) {
            $this->mailer->addAddress($address);
        }

        $this->mailer->Subject = $this->getSubjectForAction($subject);
        $this->mailer->Body = $mailMessage->setFooter($this->getEmailFooter())->composeHtml();

        $this->sendMail();
    }

    /**
     * @throws MailProviderException
     */
    protected function initialize()
    {
        if ($this->config->getConfigData()->isMailEnabled()) {
            $this->mailer = $this->dic->get(MailProvider::class)
                ->getMailer($this->getParamsFromConfig());
        }

        $this->appInfo = Util::getAppInfo();
    }

    /**
     * @return MailParams
     */
    public function getParamsFromConfig()
    {
        $configData = $this->config->getConfigData();

        $mailParams = new MailParams();
        $mailParams->server = $configData->getMailServer();
        $mailParams->port = $configData->getMailPort();
        $mailParams->user = $configData->getMailUser();
        $mailParams->pass = $configData->getMailPass();
        $mailParams->security = $configData->getMailSecurity();
        $mailParams->from = $configData->getMailFrom();
        $mailParams->mailAuthenabled = $configData->isMailAuthenabled();

        return $mailParams;
    }
}