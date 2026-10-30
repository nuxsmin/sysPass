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

namespace SP\Modules\Web\Controllers\ConfigWiki;

use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Events\Event;
use SP\Domain\Core\Events\EventMessage;
use SP\Modules\Web\Controllers\ConfigSaveControllerBase;
use SP\Modules\Web\Mvc\Controller\SimpleControllerHelper;

use function SP\__u;

/**
 * Class SaveController
 *
 * @package SP\Modules\Web\Controllers
 */
final class SaveController extends ConfigSaveControllerBase
{
    public function __construct(SimpleControllerHelper $simpleControllerHelper)
    {
        parent::__construct($simpleControllerHelper);

        $this->checks();
    }

    #[Action(ResponseType::JSON)]
    public function saveAction(): ActionResponse
    {
        if (!$this->acl->checkUserAccess(AclActionsInterface::CONFIG_WIKI)
            && !$this->session->getUserData()->isAdminApp
        ) {
            return ActionResponse::error(__u('You don\'t have permission to access this page'));
        }

        $eventMessage = EventMessage::build();
        $configData = $this->config->getConfigData();

        $wikiEnabled = $this->request->analyzeBool('wiki_enabled', false);
        $wikiSearchUrl = $this->request->analyzeString('wiki_searchurl');
        $wikiPageUrl = $this->request->analyzeString('wiki_pageurl');
        $wikiFilter = $this->request->analyzeString('wiki_filter');

        if ($wikiEnabled && (!$wikiSearchUrl || !$wikiPageUrl || !$wikiFilter)) {
            return ActionResponse::error(__u('Missing Wiki parameters'));
        }

        if ($wikiEnabled) {
            $configData->setWikiEnabled(true);
            $configData->setWikiSearchurl($wikiSearchUrl);
            $configData->setWikiPageurl($wikiPageUrl);
            $configData->setWikiFilter(explode(',', $wikiFilter));

            if ($configData->isWikiEnabled() === false) {
                $eventMessage->addDescription(__u('Wiki enabled'));
            }
        } elseif ($configData->isWikiEnabled()) {
            $configData->setWikiEnabled(false);

            $eventMessage->addDescription(__u('Wiki disabled'));
        } else {
            return ActionResponse::ok(__u('No changes'));
        }

        return $this->saveConfig(
            $configData,
            $this->config,
            function () use ($eventMessage) {
                $this->eventDispatcher->notify('save.config.wiki', new Event($this, $eventMessage));
            }
        );
    }
}
