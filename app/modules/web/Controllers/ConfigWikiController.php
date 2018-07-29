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

use SP\Core\Acl\Acl;
use SP\Core\Acl\UnauthorizedPageException;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
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
     */
    public function saveAction()
    {
        $eventMessage = EventMessage::factory();
        $configData = $this->config->getConfigData();

        // Wiki
        $wikiEnabled = $this->request->analyzeBool('wiki_enabled', false);
        $wikiSearchUrl = $this->request->analyzeString('wiki_searchurl');
        $wikiPageUrl = $this->request->analyzeString('wiki_pageurl');
        $wikiFilter = $this->request->analyzeString('wiki_filter');

        // Valores para la conexión a la Wiki
        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de Wiki'));
        }

        if ($wikiEnabled) {
            $configData->setWikiEnabled(true);
            $configData->setWikiSearchurl($wikiSearchUrl);
            $configData->setWikiPageurl($wikiPageUrl);
            $configData->setWikiFilter(explode(',', $wikiFilter));

            if ($configData->isWikiEnabled() === false) {
                $eventMessage->addDescription(__u('Wiki habiltada'));
            }
        } elseif ($wikiEnabled === false && $configData->isWikiEnabled()) {
            $configData->setWikiEnabled(false);

            $eventMessage->addDescription(__u('Wiki deshabilitada'));
        }

        // DokuWiki
        $dokuWikiEnabled = $this->request->analyzeBool('dokuwiki_enabled', false);
        $dokuWikiUrl = $this->request->analyzeString('dokuwiki_url');
        $dokuWikiUrlBase = $this->request->analyzeString('dokuwiki_urlbase');
        $dokuWikiUser = $this->request->analyzeString('dokuwiki_user');
        $dokuWikiPass = $this->request->analyzeEncrypted('dokuwiki_pass');
        $dokuWikiNamespace = $this->request->analyzeString('dokuwiki_namespace');

        // Valores para la conexión a la API de DokuWiki
        if ($dokuWikiEnabled && (!$dokuWikiUrl || !$dokuWikiUrlBase)) {
            $this->returnJsonResponse(JsonResponse::JSON_ERROR, __u('Faltan parámetros de DokuWiki'));
        }

        if ($dokuWikiEnabled) {
            $configData->setDokuwikiEnabled(true);
            $configData->setDokuwikiUrl($dokuWikiUrl);
            $configData->setDokuwikiUrlBase(trim($dokuWikiUrlBase, '/'));
            $configData->setDokuwikiUser($dokuWikiUser);
            $configData->setDokuwikiPass($dokuWikiPass);
            $configData->setDokuwikiNamespace($dokuWikiNamespace);

            if ($configData->isDokuwikiEnabled() === false) {
                $eventMessage->addDescription(__u('DokuWiki habilitada'));
            }
        } elseif ($dokuWikiEnabled === false && $configData->isDokuwikiEnabled()) {
            $configData->setDokuwikiEnabled(false);

            $eventMessage->addDescription(__u('DokuWiki deshabilitada'));
        }

        $this->saveConfig($configData, $this->config, function () use ($eventMessage) {
            $this->eventDispatcher->notifyEvent('save.config.wiki', new Event($this, $eventMessage));
        });
    }

    protected function initialize()
    {
        try {
            $this->checks();
            $this->checkAccess(Acl::CONFIG_WIKI);
        } catch (UnauthorizedPageException $e) {
            $this->eventDispatcher->notifyEvent('exception', new Event($e));

            $this->returnJsonResponseException($e);
        }
    }
}