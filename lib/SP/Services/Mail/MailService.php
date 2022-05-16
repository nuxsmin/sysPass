<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Services\Mail;

use Exception;
use PHPMailer\PHPMailer\PHPMailer;
use SP\Core\AppInfoInterface;
use SP\Core\Bootstrap\BootstrapBase;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\Core\Messages\MailMessage;
use SP\Html\Html;
use SP\Providers\Mail\MailParams;
use SP\Providers\Mail\MailProvider;
use SP\Providers\Mail\MailProviderException;
use SP\Services\Service;
use SP\Services\ServiceException;

/**
 * Class MailService
 *
 * @package SP\Services
 */
final class MailService extends Service
{
    protected ?PHPMailer $mailer = null;

    /**
     * Checks mail params by sending a test email
     *
     * @throws ServiceException
     */
    public function check(MailParams $mailParams, string $to): void
    {
        try {
            $mailer = $this->dic->get(MailProvider::class)
                ->getMailer($mailParams);

            $mailMessage = new MailMessage();
            $mailMessage->setTitle(__u('Mail test'));
            $mailMessage->addDescription(
                __u('This is a test email in order to verify that the configuration is working right.')
            );
            $mailMessage->setFooter($this->getEmailFooter());

            $mailer->isHTML();
            $mailer->addAddress($to);
            $mailer->Subject = $this->getSubjectForAction($mailMessage->getTitle());
            $mailer->Body = $mailMessage->composeHtml();
            $mailer->send();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            throw new ServiceException(
                __u('Error while sending the email'),
                SPException::ERROR,
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Devolver el pie del email con la firma de la aplicación
     */
    protected function getEmailFooter(): array
    {
        return [
            '',
            '--',
            sprintf(
                '%s - %s',
                AppInfoInterface::APP_NAME,
                AppInfoInterface::APP_DESC
            ),
            Html::anchorText(BootstrapBase::$WEBURI),
        ];
    }

    /**
     * @param $action
     *
     * @return string
     */
    protected function getSubjectForAction($action): string
    {
        return sprintf(
            '%s - %s',
            AppInfoInterface::APP_NAME,
            $action
        );
    }

    /**
     * @param  string  $subject
     * @param  array|string  $to
     * @param  MailMessage  $mailMessage
     *
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Services\ServiceException
     */
    public function send(string $subject, $to, MailMessage $mailMessage): void
    {
        $this->mailer->isHTML();

        if (is_array($to)) {
            foreach ($to as $addr) {
                $this->mailer->addAddress($addr);
            }
        } else {
            $this->mailer->addAddress($to);
        }

        $this->mailer->Subject = $this->getSubjectForAction($subject);
        $this->mailer->Body = $mailMessage->setFooter($this->getEmailFooter())
            ->composeHtml();

        $this->sendMail();
    }

    /**
     * @throws ServiceException
     */
    private function sendMail(): void
    {
        try {
            $this->mailer->send();

            $this->eventDispatcher->notifyEvent(
                'send.mail',
                new Event(
                    $this, EventMessage::factory()
                    ->addDescription(__u('Email sent'))
                    ->addDetail(
                        __u('Recipient'),
                        implode(
                            ',',
                            array_map(
                                static function ($value) {
                                    return $value[0];
                                },
                                $this->mailer->getToAddresses()
                            )
                        )
                    )
                )
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent(
                'exception',
                new Event($e)
            );

            throw new ServiceException(__u('Error while sending the email'));
        }
    }

    /**
     * @throws \PHPMailer\PHPMailer\Exception
     * @throws \SP\Services\ServiceException
     */
    public function sendBatch(
        string $subject,
        array $to,
        MailMessage $mailMessage
    ): void {
        $this->mailer->isHTML();

        foreach ($to as $address) {
            $this->mailer->addAddress($address);
        }

        $this->mailer->Subject = $this->getSubjectForAction($subject);
        $this->mailer->Body = $mailMessage->setFooter($this->getEmailFooter())
            ->composeHtml();

        $this->sendMail();
    }

    /**
     * @throws MailProviderException
     */
    protected function initialize(): void
    {
        if ($this->config->getConfigData()->isMailEnabled()) {
            $this->mailer = $this->dic->get(MailProvider::class)
                ->getMailer($this->getParamsFromConfig());
        }
    }

    public function getParamsFromConfig(): MailParams
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