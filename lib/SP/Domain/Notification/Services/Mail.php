<?php
declare(strict_types=1);
/*
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

namespace SP\Domain\Notification\Services;

use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Messages\MailMessage;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\AppInfoInterface;
use SP\Domain\Core\Bootstrap\UriContextInterface;
use SP\Domain\Html\Html;
use SP\Domain\Notification\Dtos\MailParams;
use SP\Domain\Notification\Ports\MailerInterface;
use SP\Domain\Notification\Ports\MailService;

use function SP\__u;
use function SP\processException;

/**
 * Class Mail
 */
final class Mail extends Service implements MailService
{
    public function __construct(
        Application                          $application,
        private readonly MailerInterface     $mailer,
        private readonly UriContextInterface $uriContext
    ) {
        parent::__construct($application);
    }

    public static function getParamsFromConfig(ConfigDataInterface $configData): MailParams
    {
        return new MailParams(
            $configData->getMailServer(),
            $configData->getMailPort(),
            $configData->getMailUser(),
            $configData->getMailPass(),
            $configData->getMailSecurity(),
            $configData->getMailFrom(),
            $configData->isMailAuthenabled()
        );
    }

    /**
     * Checks mail params by sending a test email
     *
     * @throws ServiceException
     */
    public function check(MailParams $mailParams, string $to): void
    {
        try {
            $mailMessage = (new MailMessage())
                ->setTitle(__u('Mail test'))
                ->addDescription(
                    __u('This is a test email in order to verify that the configuration is working right.')
                )
                ->setFooter($this->getEmailFooter());

            $this->mailer
                ->configure($mailParams)
                ->isHTML()
                ->addAddress($to)
                ->subject($this->getSubjectForAction($mailMessage->getTitle()))
                ->body($mailMessage->composeHtml())
                ->send();
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw ServiceException::error(__u('Error while sending the email'), $e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Devolver el pie del email con la firma de la aplicación
     */
    private function getEmailFooter(): array
    {
        return [
            '',
            '--',
            sprintf('%s - %s', AppInfoInterface::APP_NAME, AppInfoInterface::APP_DESC),
            Html::anchorText($this->uriContext->getWebUri()),
        ];
    }

    /**
     * @param string $subject
     * @param array|string $to
     * @param MailMessage $mailMessage
     *
     * @throws ServiceException
     */
    public function send(string $subject, string|array $to, MailMessage $mailMessage): void
    {
        if (!is_array($to)) {
            $to = [$to];
        }

        foreach ($to as $addr) {
            $this->mailer->addAddress($addr);
        }

        $this->mailer
            ->isHTML()
            ->subject($this->getSubjectForAction($subject))
            ->body($mailMessage->setFooter($this->getEmailFooter())->composeHtml());

        $this->sendMail();
    }

    /**
     * @param $action
     *
     * @return string
     */
    private function getSubjectForAction($action): string
    {
        return sprintf('%s - %s', AppInfoInterface::APP_NAME, $action);
    }

    /**
     * @throws ServiceException
     */
    private function sendMail(): void
    {
        try {
            $this->mailer->send();

            $this->eventDispatcher->notify(
                'send.mail',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Email sent'))
                        ->addDetail(
                            __u('Recipient'),
                            implode(
                                ',',
                                array_map(
                                    static fn($value) => $value[0],
                                    $this->mailer->getToAddresses()
                                )
                            )
                        )
                )
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            throw ServiceException::error(__u('Error while sending the email'));
        }
    }
}
