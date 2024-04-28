<?php
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

namespace SP\Modules\Web\Controllers\ConfigMail;


use Exception;
use JsonException;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\Services\ConfigUtil;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Acl\UnauthorizedPageException;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Domain\Notification\Ports\MailService;
use SP\Domain\Providers\Mail\MailParams;
use SP\Http\JsonMessage;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Mvc\Controller\SimpleControllerHelper;

/**
 * Class CheckController
 */
final class CheckController extends SimpleControllerBase
{
    use ConfigTrait;

    public function __construct(
        Application                  $application,
        SimpleControllerHelper       $simpleControllerHelper,
        private readonly MailService $mailService
    ) {
        parent::__construct($application, $simpleControllerHelper);
    }

    /**
     * @return bool
     * @throws JsonException
     */
    public function checkAction(): bool
    {
        try {
            $mailParams = $this->handleMailConfig();

            $mailRecipients = ConfigUtil::mailAddressesAdapter($this->request->analyzeString('mail_recipients'));

            // Valores para la configuración del Correo
            if (empty($mailParams->getServer()) || empty($mailParams->getFrom()) || count($mailRecipients) === 0) {
                throw new ValidationException(SPException::ERROR, __u('Missing Mail parameters'));
            }

            $this->mailService->check($mailParams, $mailRecipients[0]);

            $this->eventDispatcher->notify(
                'send.mail.check',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Email sent'))
                        ->addDetail(__u('Recipient'), $mailRecipients[0])
                )
            );

            return $this->returnJsonResponse(
                JsonMessage::JSON_SUCCESS,
                __u('Email sent'),
                [__u('Please, check your inbox')]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notify('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return MailParams
     */
    private function handleMailConfig(): MailParams
    {
        return new MailParams(
            $this->request->analyzeString('mail_server'),
            $this->request->analyzeInt('mail_port', 25),
            $this->request->analyzeString('mail_user'),
            $this->request->analyzeEncrypted('mail_pass'),
            $this->request->analyzeString('mail_security'),
            $this->request->analyzeEmail('mail_from'),
            $this->request->analyzeBool('mail_auth_enabled', false)
        );
    }

    /**
     * @return void
     * @throws JsonException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(AclActionsInterface::CONFIG_MAIL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notify('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}
