<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2017, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Import;

use DOMElement;
use DOMXPath;
use SP\DataModel\AccountExtData;
use SP\DataModel\CategoryData;
use SP\DataModel\CustomerData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde KeePass
 */
class KeepassImport extends ImportBase
{
    use XmlImportTrait;

    /**
     * @var int
     */
    protected $customerId = 0;

    /**
     * Iniciar la importación desde KeePass
     *
     * @throws \SP\Core\Exceptions\SPException
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public function doImport()
    {
        $customerData = new CustomerData(null, 'KeePass');
        $this->addCustomer($customerData);

        $this->customerId = $customerData->getCustomerId();

        $this->process();
    }

    /**
     * Obtener los grupos y procesar lan entradas de KeePass.
     */
    protected function process()
    {
        foreach ($this->getItems() as $group => $entry) {
            $CategoryData = new CategoryData(null, $group);
            $this->addCategory($CategoryData);

            if (count($entry) > 0) {
                foreach ($entry as $account) {
                    $AccountData = new AccountExtData();
                    $AccountData->setAccountNotes($account['Notes']);
                    $AccountData->setAccountPass($account['Password']);
                    $AccountData->setAccountName($account['Title']);
                    $AccountData->setAccountUrl($account['URL']);
                    $AccountData->setAccountLogin($account['UserName']);
                    $AccountData->setAccountCategoryId($CategoryData->getCategoryId());
                    $AccountData->setAccountCustomerId($this->customerId);

                    $this->addAccount($AccountData);
                }
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
        $Tags = $DomXpath->query('/KeePassFile/Root/Group//Group|/KeePassFile/Root/Group//Entry');
        $items = [];

        /** @var DOMElement[] $Tags */
        foreach ($Tags as $tag) {
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