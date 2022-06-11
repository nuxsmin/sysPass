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

namespace SP\Modules\Web\Controllers\ConfigMail;

use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Config\Services\ConfigUtil;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class SaveController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * @return bool
     * @throws \JsonException
     */
    public function saveAction(): bool
    {
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

        if ($mailEnabled && (empty($mailServer) || empty($mailFrom))
        ) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing Mail parameters'));
        }

        if ($mailEnabled) {
            $configData->setMailRequestsEnabled($mailRequests);
            $configData->setMailServer($mailServer);
            $configData->setMailPort($mailPort);
            $configData->setMailSecurity($mailSecurity);
            $configData->setMailFrom($mailFrom);
            $configData->setMailRecipients($mailRecipients);
            $configData->setMailAuthenabled($mailAuth);
            $configData->setMailEvents(
                $this->request->analyzeArray('mail_events', fn($items) => ConfigUtil::eventsAdapter($items), [])
            );

            if ($mailAuth) {
                $configData->setMailUser($mailUser);

                if ($mailPass !== '***') {
                    $configData->setMailPass($mailPass);
                }
            }

            if ($configData->isMailEnabled() === false) {
                $configData->setMailEnabled(true);
                $eventMessage->addDescription(__u('Mail enabled'));
            }
        } elseif ($configData->isMailEnabled()) {
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
                $this->eventDispatcher->notifyEvent('save.config.mail', new Event($this, $eventMessage));
            }
        );
    }

    /**
     * @return void
     * @throws \JsonException
     * @throws \SP\Core\Exceptions\SessionTimeout
     */
    protected function initialize(): void
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_MAIL);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}