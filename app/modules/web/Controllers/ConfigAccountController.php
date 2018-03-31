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
use SP\Core\Acl\ActionsInterface;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Http\JsonResponse;
use SP\Http\Request;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigAccountController
 *
 * @package SP\Modules\Web\Controllers
 */
class ConfigAccountController extends SimpleControllerBase
{
    use ConfigTrait;

    /**
     * saveAction
     */
    public function saveAction()
    {
        $configData = $this->config->getConfigData();

        $eventMessage = EventMessage::factory();

        // Accounts
        $configData->setGlobalSearch(Request::analyzeBool('account_globalsearch_enabled', false));
        $configData->setAccountPassToImage(Request::analyzeBool('account_passtoimage_enabled', false));
        $configData->setAccountLink(Request::analyzeBool('account_link_enabled', false));
        $configData->setAccountFullGroupAccess(Request::analyzeBool('account_fullgroup_access_enabled', false));
        $configData->setAccountCount(Request::analyzeInt('account_count', 10));
        $configData->setResultsAsCards(Request::analyzeBool('account_resultsascards_enabled', false));
        $configData->setAccountExpireEnabled(Request::analyzeBool('account_expire_enabled', false));
        $configData->setAccountExpireTime(Request::analyzeInt('account_expire_time', 10368000) * 24 * 3600);

        // Files
        $filesEnabled = Request::analyzeBool('files_enabled', false);

        if ($filesEnabled) {
            $filesAllowedSize = Request::analyzeInt('files_allowed_size', 1024);

            if ($filesAllowedSize >= 16384) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('El tamaño máximo por archivo es de 16MB'));
            }

            $configData->setFilesEnabled(true);
            $configData->setFilesAllowedExts(ConfigUtil::filesExtsAdapter(Request::analyzeString('files_allowed_exts')));
            $configData->setFilesAllowedSize($filesAllowedSize);

            if ($configData->isFilesEnabled() === false) {
                $eventMessage->addDescription(__u('Archivos habilitados'));
            }
        } elseif ($filesEnabled === false && $configData->isFilesEnabled()) {
            $configData->setFilesEnabled(false);

            $eventMessage->addDescription(__u('Archivos deshabilitados'));
        }

        // Public Links
        $pubLinksEnabled = Request::analyzeBool('publiclinks_enabled', false);

        if ($pubLinksEnabled === true) {
            $configData->setPublinksEnabled(true);
            $configData->setPublinksImageEnabled(Request::analyzeBool('publiclinks_image_enabled', false));
            $configData->setPublinksMaxTime(Request::analyzeInt('publiclinks_maxtime', 10) * 60);
            $configData->setPublinksMaxViews(Request::analyzeInt('publiclinks_maxviews', 3));

            if ($configData->isPublinksEnabled() === false) {
                $eventMessage->addDescription(__u('Enlaces públicos habilitados'));
            }
        } elseif ($pubLinksEnabled === false && $configData->isPublinksEnabled()) {
            $configData->setPublinksEnabled(false);

            $eventMessage->addDescription(__u('Enlaces públicos deshabilitados'));
        }


        $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.account', new Event($this, $eventMessage));
        });
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(ActionsInterface::ACCOUNT_CONFIG);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}