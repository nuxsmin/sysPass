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

namespace SP\Modules\Web\Controllers;

use SP\Config\ConfigUtil;
use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;
use SP\Providers\Mail\MailParams;
use SP\Services\MailService;

/**
 * Class ConfigMailController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigMailController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     */
    public function saveAction()
    {
        $eventMessage = EventMessage::factory();
        $configData = $this->config->getConfigData();

        // Mail
        $mailEnabled = Request::analyzeBool('mail_enabled', false);
        $mailServer = Request::analyzeString('mail_server');
        $mailPort = Request::analyzeInt('mail_port', 25);
        $mailUser = Request::analyzeString('mail_user');
        $mailPass = Request::analyzeEncrypted('mail_pass');
        $mailSecurity = Request::analyzeString('mail_security');
        $mailFrom = Request::analyzeEmail('mail_from');
        $mailRequests = Request::analyzeBool('mail_requests_enabled', false);
        $mailAuth = Request::analyzeBool('mail_auth_enabled', false);
        $mailRecipients = ConfigUtil::mailAddressesAdapter(Request::analyzeString('mail_recipients'));

        // Valores para la configuración del Correo
        if ($mailEnabled && (!$mailServer || !$mailFrom || count($mailRecipients) === 0)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de Correo'));
        }

        if ($mailEnabled) {
            $configData->setMailEnabled(true);
            $configData->setMailRequestsEnabled($mailRequests);
            $configData->setMailServer($mailServer);
            $configData->setMailPort($mailPort);
            $configData->setMailSecurity($mailSecurity);
            $configData->setMailFrom($mailFrom);
            $configData->setMailRecipients($mailRecipients);
            $configData->setMailEvents(Request::analyzeArray('mail_events', function ($items) {
                return ConfigUtil::eventsAdapter($items);
            }));

            if ($mailAuth) {
                $configData->setMailAuthenabled($mailAuth);
                $configData->setMailUser($mailUser);

                if ($mailPass !== '***') {
                    $configData->setMailPass($mailPass);
                }
            }

            if ($configData->isMailEnabled() === false) {
                $eventMessage->addDescription(__u('Correo habiltado'));
            }
        } elseif ($mailEnabled === false && $configData->isMailEnabled()) {
            $configData->setMailEnabled(false);
            $configData->setMailRequestsEnabled(false);
            $configData->setMailAuthenabled(false);

            $eventMessage->addDescription(__u('Correo deshabilitado'));
        } else {
            $this->returnJsonResponse(JsonResponse::JSON_SUCCESS, __u('Sin cambios'));
        }

        $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.mail', new Event($this, $eventMessage));
        });
    }

    /**
     * checkAction
     */
    public function checkAction()
    {
        $mailParams = new MailParams();
        $mailParams->server = Request::analyzeString('mail_server');
        $mailParams->port = Request::analyzeInt('mail_port', 25);
        $mailParams->security = Request::analyzeString('mail_security');
        $mailParams->from = Request::analyzeEmail('mail_from');
        $mailParams->mailAuthenabled = Request::analyzeBool('mail_authenabled', false);
        $mailRecipients = ConfigUtil::mailAddressesAdapter(Request::analyzeString('mail_recipients'));

        // Valores para la configuración del Correo
        if (!$mailParams->server || empty($mailParams->from) || empty($mailRecipients)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de Correo'));
        }

        if ($mailParams->mailAuthenabled) {
            $mailParams->user = Request::analyzeString('mail_user');
            $mailParams->pass = Request::analyzeEncrypted('mail_pass');
        }

        try {
            $this->dic->get(MailService::class)->check($mailParams, $mailRecipients[0]);

            $this->eventDispatcher->notifyEvent('send.mail.check',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Correo enviado'))
                    ->addDetail(__u('Destinatario'), $mailRecipients[0]))
            );

            $this->returnJsonResponse(
                JsonResponse::JSON_SUCCESS,
                __u('Correo enviado'),
                [__u('Compruebe su buzón de correo')]
            );
        } catch (\Exception $e) {
            processException($e);

            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::MAIL_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}