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

namespace SP\Services\Import;

use DOMElement;
use DOMXPath;
use Exception;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\Services\Account\AccountRequest;
use SP\Util\Filter;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
final class KeepassImport extends XmlImportBase implements ImportInterface
{
    /**
     * @var array
     */
    private $items = [];

    /**
     * Iniciar la importación desde KeePass
     *
     * @return ImportInterface
     * @throws SPException
     */
    public function doImport()
    {
        $this->eventDispatcher->notifyEvent('run.import.keepass',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('KeePass XML Import')))
        );

        $this->process();

        return $this;
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @throws SPException
     */
    private function process()
    {
        $clientId = $this->addClient(new ClientData(null, 'KeePass'));

        $this->eventDispatcher->notifyEvent('run.import.keepass.process.client',
            new Event($this, EventMessage::factory()
                ->addDetail(__u('Client added'), 'KeePass'))
        );

        $this->getGroups();

        $this->getEntries();

        /** @var AccountRequest[] $group */
        foreach ($this->items as $group => $entry) {
            try {
                $categoryId = $this->addCategory(new CategoryData(null, $group, 'KeePass'));

                $this->eventDispatcher->notifyEvent('run.import.keepass.process.category',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__u('Category imported'), $group))
                );

                if (count($entry) > 0) {
                    foreach ($entry as $account) {
                        $account->categoryId = $categoryId;
                        $account->clientId = $clientId;

                        $this->addAccount($account);

                        $this->eventDispatcher->notifyEvent('run.import.keepass.process.account',
                            new Event($this, EventMessage::factory()
                                ->addDetail(__u('Account imported'), $account->name)
                                ->addDetail(__u('Category'), $group))
                        );
                    }
                }
            } catch (Exception $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));
            }
        }
    }

    /**
     * Gets the groups found
     */
    private function getGroups()
    {
        $DomXpath = new DOMXPath($this->xmlDOM);
        $tags = $DomXpath->query('/KeePassFile/Root/Group//Group');

        /** @var DOMElement[] $tags */
        foreach ($tags as $tag) {
            if ($tag->nodeType === 1) {
                $groupName = $DomXpath->query($tag->getNodePath() . '/Name')->item(0)->nodeValue;

                if (!isset($groups[$groupName])) {
                    $this->items[$groupName] = [];
                }
            }
        }
    }

    /**
     * Gets the entries found
     */
    private function getEntries()
    {
        $DomXpath = new DOMXPath($this->xmlDOM);
        $tags = $DomXpath->query('/KeePassFile/Root/Group//Entry[not(parent::History)]');

        /** @var DOMElement[] $tags */
        foreach ($tags as $tag) {
            if ($tag->nodeType === 1) {
                $path = $tag->getNodePath();
                $entryData = [];

                /** @var DOMElement $key */
                foreach ($DomXpath->query($path . '/String/Key') as $key) {
                    $value = $DomXpath->query($key->getNodePath() . '/../Value')->item(0)->nodeValue;

                    $entryData[$key->nodeValue] = $value;
                }

                $groupName = $DomXpath->query($path . '/../Name')->item(0)->nodeValue;

                $this->items[$groupName][] = $this->mapEntryToAccount($entryData);
            }
        }
    }

    /**
     * @param array $entry
     *
     * @return AccountRequest
     */
    private function mapEntryToAccount(array $entry)
    {
        $accountRequest = new AccountRequest();
        $accountRequest->name = isset($entry['Title']) ? Filter::getString($entry['Title']) : '';
        $accountRequest->login = isset($entry['UserName']) ? Filter::getString($entry['UserName']) : '';
        $accountRequest->pass = isset($entry['Password']) ? $entry['Password'] : '';
        $accountRequest->url = isset($entry['URL']) ? Filter::getString($entry['URL']) : '';
        $accountRequest->notes = isset($entry['Notes']) ? Filter::getString($entry['Notes']) : '';

        return $accountRequest;
    }
}