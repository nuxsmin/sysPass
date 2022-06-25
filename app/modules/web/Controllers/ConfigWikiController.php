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

use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SessionTimeout;
use SP\Core\Exceptions\SPException;
use SP\Http\JsonResponse;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

/**
 * Class ConfigWikiController
 *
 * @package SP\Modules\Web\Controllers
 */
final class ConfigWikiController extends SimpleControllerBase
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

        // Wiki
        $wikiEnabled = $this->request->analyzeBool('wiki_enabled', false);
        $wikiSearchUrl = $this->request->analyzeString('wiki_searchurl');
        $wikiPageUrl = $this->request->analyzeString('wiki_pageurl');
        $wikiFilter = $this->request->analyzeString('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            return $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Missing Wiki parameters'));
        }

        if ($wikiEnabled) {
            $configData->setWikiEnabled(true);
            $configData->setWikiSearchurl($wikiSearchUrl);
            $configData->setWikiPageurl($wikiPageUrl);
            $configData->setWikiFilter(explode(',', $wikiFilter));

            if ($configData->isWikiEnabled() === false) {
                $eventMessage->addDescription(__u('Wiki enabled'));
            }
        } elseif ($wikiEnabled === false && $configData->isWikiEnabled()) {
            $configData->setWikiEnabled(false);

            $eventMessage->addDescription(__u('Wiki disabled'));
        }

        return $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.wiki', new Event($this, $eventMessage));
        });
    }

    /**
     * @return bool
     * @throws SessionTimeout
     */
    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_WIKI);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            return $this->returnJsonResponseException($e);
        }

        return true;
    }
}