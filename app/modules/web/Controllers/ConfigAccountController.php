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

use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigAccountController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigAccountController extends SimpleControllerBase
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

        $configData = $this->config->getConfigData();

        $eventMessage = EventMessage::factory();

        // Accounts
        $configData->setGlobalSearch($this->request->analyzeBool('account_globalsearch_enabled', false));
        $configData->setAccountPassToImage($this->request->analyzeBool('account_passtoimage_enabled', false));
        $configData->setAccountLink($this->request->analyzeBool('account_link_enabled', false));
        $configData->setAccountFullGroupAccess($this->request->analyzeBool('account_fullgroup_access_enabled', false));
        $configData->setAccountCount($this->request->analyzeInt('account_count', 10));
        $configData->setResultsAsCards($this->request->analyzeBool('account_resultsascards_enabled', false));
        $configData->setAccountExpireEnabled($this->request->analyzeBool('account_expire_enabled', false));
        $configData->setAccountExpireTime($this->request->analyzeInt('account_expire_time', 10368000) * 24 * 3600);

        // Files
        $filesEnabled = $this->request->analyzeBool('files_enabled', false);

        if ($filesEnabled) {
            $filesAllowedSize = $this->request->analyzeInt('files_allowed_size', 1024);

            if ($filesAllowedSize > 16384) {
                return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Maximum size per file is 16MB'));
            }

            $configData->setFilesEnabled(true);
            $configData->setFilesAllowedMime($this->request->analyzeArray('files_allowed_mimetypes', null, []));
            $configData->setFilesAllowedSize($filesAllowedSize);

            if ($configData->isFilesEnabled() === false) {
                $eventMessage->addDescription(__u('Files enabled'));
            }
        } elseif ($filesEnabled === false && $configData->isFilesEnabled()) {
            $configData->setFilesEnabled(false);

            $eventMessage->addDescription(__u('Files disabled'));
        }

        // Public Links
        $pubLinksEnabled = $this->request->analyzeBool('publiclinks_enabled', false);

        if ($pubLinksEnabled === true) {
            $configData->setPublinksEnabled(true);
            $configData->setPublinksImageEnabled($this->request->analyzeBool('publiclinks_image_enabled', false));
            $configData->setPublinksMaxTime($this->request->analyzeInt('publiclinks_maxtime', 10) * 60);
            $configData->setPublinksMaxViews($this->request->analyzeInt('publiclinks_maxviews', 3));

            if ($configData->isPublinksEnabled() === false) {
                $eventMessage->addDescription(__u('Public links enabled'));
            }
        } elseif ($pubLinksEnabled === false && $configData->isPublinksEnabled()) {
            $configData->setPublinksEnabled(false);

            $eventMessage->addDescription(__u('Public links disabled'));
        }


        return $this->saveConfig(
            $configData,
            $this->config,
            function () use ($eventMessage) {
                $this->eventDispatcher->notifyEvent(
                    'save.config.account',
                    new Event($this, $eventMessage)
                );
            }
        );
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::CONFIG_ACCOUNT);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}