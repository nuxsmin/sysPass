<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2023, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Modules\Web\Controllers\ConfigAccount;

use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Common\Attributes\Action;
use SP\Domain\Common\Dtos\ActionResponse;
use SP\Domain\Common\Enums\ResponseType;
use SP\Domain\Config\Ports\ConfigDataInterface;
use SP\Domain\Core\Acl\AclActionsInterface;
use SP\Domain\Core\Exceptions\SessionTimeout;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\Exceptions\ValidationException;
use SP\Modules\Web\Controllers\SimpleControllerBase;
use SP\Modules\Web\Controllers\Traits\ConfigTrait;

use function SP\__u;

/**
 * Class SaveController
 */
final class SaveController extends SimpleControllerBase
{
    use ConfigTrait;

    private const MAX_FILES_SIZE = 16384;

    /**
     * @return ActionResponse
     * @throws ValidationException
     * @throws SPException
     */
    #[Action(ResponseType::JSON)]
    public function saveAction(): ActionResponse
    {
        $configData = $this->config->getConfigData();

        $eventMessage = EventMessage::build();

        $this->handleAccountsConfig($configData);
        $this->handleFilesConfig($configData, $eventMessage);
        $this->handlePublicLinksConfig($configData, $eventMessage);

        return $this->saveConfig(
            $configData,
            $this->config,
            fn() => $this->eventDispatcher->notify('save.config.account', new Event($this, $eventMessage))
        );
    }

    /**
     * @param ConfigDataInterface $configData
     *
     * @return void
     */
    private function handleAccountsConfig(ConfigDataInterface $configData): void
    {
        $configData->setGlobalSearch($this->request->analyzeBool('account_globalsearch_enabled', false));
        $configData->setAccountPassToImage($this->request->analyzeBool('account_passtoimage_enabled', false));
        $configData->setAccountLink($this->request->analyzeBool('account_link_enabled', false));
        $configData->setAccountFullGroupAccess($this->request->analyzeBool('account_fullgroup_access_enabled', false));
        $configData->setAccountCount($this->request->analyzeInt('account_count', 10));
        $configData->setResultsAsCards($this->request->analyzeBool('account_resultsascards_enabled', false));
        $configData->setAccountExpireEnabled($this->request->analyzeBool('account_expire_enabled', false));
        $configData->setAccountExpireTime($this->request->analyzeInt('account_expire_time', 10368000) * 24 * 3600);
    }

    /**
     * @param ConfigDataInterface $configData
     * @param EventMessage $eventMessage
     *
     * @return void
     * @throws ValidationException
     */
    private function handleFilesConfig(ConfigDataInterface $configData, EventMessage $eventMessage): void
    {
        $filesEnabled = $this->request->analyzeBool('files_enabled', false);

        if ($filesEnabled) {
            $filesAllowedSize = $this->request->analyzeInt('files_allowed_size', 1024);

            if ($filesAllowedSize > self::MAX_FILES_SIZE) {
                throw new ValidationException(__u('Maximum size per file is 16MB'));
            }

            $configData->setFilesEnabled(true);
            $configData->setFilesAllowedMime($this->request->analyzeArray('files_allowed_mimetypes', null, []));
            $configData->setFilesAllowedSize($filesAllowedSize);

            if ($configData->isFilesEnabled() === false) {
                $eventMessage->addDescription(__u('Files enabled'));
            }
        } elseif ($configData->isFilesEnabled()) {
            $configData->setFilesEnabled(false);

            $eventMessage->addDescription(__u('Files disabled'));
        }
    }

    /**
     * @param ConfigDataInterface $configData
     * @param EventMessage $eventMessage
     *
     * @return void
     */
    private function handlePublicLinksConfig(ConfigDataInterface $configData, EventMessage $eventMessage): void
    {
        $pubLinksEnabled = $this->request->analyzeBool('publiclinks_enabled', false);

        if ($pubLinksEnabled) {
            $configData->setPublinksEnabled(true);
            $configData->setPublinksImageEnabled($this->request->analyzeBool('publiclinks_image_enabled', false));
            $configData->setPublinksMaxTime($this->request->analyzeInt('publiclinks_maxtime', 10) * 60);
            $configData->setPublinksMaxViews($this->request->analyzeInt('publiclinks_maxviews', 3));

            if ($configData->isPublinksEnabled() === false) {
                $eventMessage->addDescription(__u('Public links enabled'));
            }
        } elseif ($configData->isPublinksEnabled()) {
            $configData->setPublinksEnabled(false);

            $eventMessage->addDescription(__u('Public links disabled'));
        }
    }

    /**
     * @return void
     * @throws SPException
     * @throws SessionTimeout
     */
    protected function initialize(): void
    {
        $this->checks();
        $this->checkAccess(AclActionsInterface::CONFIG_ACCOUNT);
    }
}
