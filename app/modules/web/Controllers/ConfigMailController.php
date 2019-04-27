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

namespace SP\Modules\Web\Controllers;

use Exception;
use SP\Config\ConfigUtil;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Providers\Mail\MailParams;
use SP\Services\Mail\MailService;

/**
 * Class ConfigMailController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigMailController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     *
     * @throws SPException
     */
    public function saveAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        $eventMessage = EventMessage::factory();
        $configData = $this->config->getConfigData();

        // Mail
        $mailEnabled = $this->request->analyzeBool('mail_enabled', false);
        $mailServer = $this->request->analyzeString('mail_server');
        $mailPort = $this->request->analyzeInt('mail_port', 25);
        $mailUser = $this->request->analyzeString('mail_user');
        $mailPass = $this->request->analyzeEncrypted('mail_pass');
        $mailSecurity = $this->request->analyzeString('mail_security');
        $mailFrom = $this->request->analyzeEmail('mail_from');
        $mailRequests = $this->request->analyzeBool('mail_requests_enabled', false);
        $mailAuth = $this->request->analyzeBool('mail_auth_enabled', false);
        $mailRecipients = ConfigUtil::mailAddressesAdapter($this->request->analyzeString('mail_recipients'));

        // Valores para la configuración del Correo
        if ($mailEnabled
            && (empty($mailServer) || empty($mailFrom))
        ) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing Mail parameters'));
        }

        if ($mailEnabled) {
            $configData->setMailEnabled(true);
            $configData->setMailRequestsEnabled($mailRequests);
            $configData->setMailServer($mailServer);
            $configData->setMailPort($mailPort);
            $configData->setMailSecurity($mailSecurity);
            $configData->setMailFrom($mailFrom);
            $configData->setMailRecipients($mailRecipients);
            $configData->setMailEvents(
                $this->request->analyzeArray('mail_events',
                    function ($items) {
                        return ConfigUtil::eventsAdapter($items);
                    }, [])
            );

            if ($mailAuth) {
                $configData->setMailAuthenabled($mailAuth);
                $configData->setMailUser($mailUser);

                if ($mailPass !== '***') {
                    $configData->setMailPass($mailPass);
                }
            }

            if ($configData->isMailEnabled() === false) {
                $eventMessage->addDescription(__u('Mail enabled'));
            }
        } elseif ($mailEnabled === false && $configData->isMailEnabled()) {
            $configData->setMailEnabled(false);
            $configData->setMailRequestsEnabled(false);
            $configData->setMailAuthenabled(false);

            $eventMessage->addDescription(__u('Mail disabled'));
        } else {
            return $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('No changes'));
        }

        return $this->saveConfig(
            $configData,
            $this->config,
            function () use ($eventMessage) {
                $this->eventDispatcher->notifyEvent(
                    'save.config.mail',
                    new Event($this, $eventMessage)
                );
            }
        );
    }

    /**
     * checkAction
     *
     * @throws SPException
     */
    public function checkAction()
    {
        $this->checkSecurityToken($this->previousSk, $this->request);

        $mailParams = new MailParams();
        $mailParams->server = $this->request->analyzeString('mail_server');
        $mailParams->port = $this->request->analyzeInt('mail_port', 25);
        $mailParams->security = $this->request->analyzeString('mail_security');
        $mailParams->from = $this->request->analyzeEmail('mail_from');
        $mailParams->mailAuthenabled = $this->request->analyzeBool('mail_auth_enabled', false);
        $mailRecipients = ConfigUtil::mailAddressesAdapter($this->request->analyzeString('mail_recipients'));

        // Valores para la configuración del Correo
        if (empty($mailParams->server) || empty($mailParams->from) || empty($mailRecipients)) {
            return $this->returnJsonResponse(
                JsonResponse::JSON_ERROR,
                __u('Missing Mail parameters')
            );
        }

        if ($mailParams->mailAuthenabled) {
            $mailParams->user = $this->request->analyzeString('mail_user');
            $mailParams->pass = $this->request->analyzeEncrypted('mail_pass');
        }

        try {
            $this->dic->get(MailService::class)->check($mailParams, $mailRecipients[0]);

            $this->eventDispatcher->notifyEvent('send.mail.check',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Email sent'))
                    ->addDetail(__u('Recipient'), $mailRecipients[0]))
            );

            return $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Email sent'),
                [__u('Please, check your inbox')]
            );
        } catch (Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected
    function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_MAIL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}