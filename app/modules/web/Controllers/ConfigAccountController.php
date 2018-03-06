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
        $globalSearchEnabled = Request::analyzeBool('globalsearch', false);
        $accountPassToImageEnabled = Request::analyzeBool('account_passtoimage', false);
        $accountLinkEnabled = Request::analyzeBool('account_link', false);
        $accountFullGroupAccessEnabled = Request::analyzeBool('account_fullgroup_access', false);
        $accountCount = Request::analyzeInt('account_count', 10);
        $resultsAsCardsEnabled = Request::analyzeBool('resultsascards', false);

        $configData->setGlobalSearch($globalSearchEnabled);
        $configData->setAccountPassToImage($accountPassToImageEnabled);
        $configData->setAccountLink($accountLinkEnabled);
        $configData->setAccountFullGroupAccess($accountFullGroupAccessEnabled);
        $configData->setAccountCount($accountCount);
        $configData->setResultsAsCards($resultsAsCardsEnabled);

        // Files
        $filesEnabled = Request::analyzeBool('files_enabled', false);
        $filesAllowedSize = Request::analyzeInt('files_allowed_size', 1024);
        $filesAllowedExts = ConfigUtil::filesExtsAdapter(Request::analyzeString('files_allowed_exts'));

        if ($filesEnabled) {
            if ($filesAllowedSize >= 16384) {
                $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('El tamaño máximo por archivo es de 16MB'));
            } elseif ($configData->isFilesEnabled() === false) {
                $eventMessage->addDescription(__u('Archivos habilitados'));
            }
        } elseif ($filesEnabled === false && $configData->isFilesEnabled()) {
            $eventMessage->addDescription(__u('Archivos deshabilitados'));
        }

        $configData->setFilesEnabled($filesEnabled);
        $configData->setFilesAllowedExts($filesAllowedExts);
        $configData->setFilesAllowedSize($filesAllowedSize);

        // Public Links
        $pubLinksEnabled = Request::analyzeBool('publinks_enabled', false);
        $pubLinksImageEnabled = Request::analyzeBool('publinks_image_enabled', false);
        $pubLinksMaxTime = Request::analyzeInt('publinks_maxtime', 10);
        $pubLinksMaxViews = Request::analyzeInt('publinks_maxviews', 3);

        $configData->setPublinksEnabled($pubLinksEnabled);
        $configData->setPublinksImageEnabled($pubLinksImageEnabled);
        $configData->setPublinksMaxTime($pubLinksMaxTime * 60);
        $configData->setPublinksMaxViews($pubLinksMaxViews);

        if ($pubLinksEnabled === true && $configData->isPublinksEnabled() === false) {
            $eventMessage->addDescription(__u('Enlaces públicos habilitados'));
        } elseif ($pubLinksEnabled === false && $configData->isPublinksEnabled()) {
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