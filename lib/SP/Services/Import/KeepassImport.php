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

namespace SP\Services\Import;

use DOMElement;
use DOMXPath;
use SP\Account\AccountRequest;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
class KeepassImport extends XmlImportBase implements ImportInterface
{
    /**
     * Iniciar la importación desde KeePass
     *
     * @throws \SP\Core\Exceptions\SPException
     * @return ImportInterface
     */
    public function doImport()
    {
        $this->process();

        return $this;
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    protected function process()
    {
        $clientId = $this->addClient(new ClientData(null, 'KeePass'));

        $this->eventDispatcher->notifyEvent('run.import.keepass.client',
            new Event($this, EventMessage::factory()
                ->addDetail(__('Cliente creado'), 'KeePass'))
        );

        foreach ($this->getItems() as $group => $entry) {
            try {
                $categoryId = $this->addCategory(new CategoryData(null, $group));

                $this->eventDispatcher->notifyEvent('run.import.keepass.category',
                    new Event($this, EventMessage::factory()
                        ->addDetail(__('Categoría importada'), $group))
                );

                if (count($entry) > 0) {
                    foreach ($entry as $account) {
                        $accountRequest = new AccountRequest();
                        $accountRequest->notes = $account['Notes'];
                        $accountRequest->pass = $account['Password'];
                        $accountRequest->name = $account['Title'];
                        $accountRequest->url = $account['URL'];
                        $accountRequest->login = $account['UserName'];
                        $accountRequest->categoryId = $categoryId;
                        $accountRequest->clientId = $clientId;

                        $this->addAccount($accountRequest);

                        $this->eventDispatcher->notifyEvent('run.import.keepass.account',
                            new Event($this,
                                EventMessage::factory()
                                    ->addDetail(__('Cuenta importada'), $accountRequest->name))
                        );
                    }
                }
            } catch (\Exception $e) {
                processException($e);
            }
        }
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     *
     * @return array
     */
    protected function getItems()
    {
        $DomXpath = new DOMXPath($this->xmlDOM);
        $tags = $DomXpath->query('/KeePassFile/Root/Group//Group|/KeePassFile/Root/Group//Entry');
        $items = [];

        /** @var DOMElement[] $tags */
        foreach ($tags as $tag) {
            if ($tag->nodeType === 1) {
                if ($tag->nodeName === 'Entry') {
                    $path = $tag->getNodePath();
                    $groupName = $DomXpath->query($path . '/../Name')->item(0)->nodeValue;
                    $entryData = [
                        'Title' => '',
                        'UserName' => '',
                        'URL' => '',
                        'Notes' => '',
                        'Password' => ''
                    ];

                    /** @var DOMElement $key */
                    foreach ($DomXpath->query($path . '/String/Key') as $key) {
                        $value = $DomXpath->query($key->getNodePath() . '/../Value')->item(0)->nodeValue;

                        $entryData[$key->nodeValue] = $value;
                    }

                    $items[$groupName][] = $entryData;
                } elseif ($tag->nodeName === 'Group') {
                    $groupName = $DomXpath->query($tag->getNodePath() . '/Name')->item(0)->nodeValue;

                    if (!isset($groups[$groupName])) {
                        $items[$groupName] = [];
                    }
                }
            }
        }

        return $items;
    }
}