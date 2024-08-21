<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Export\Services;

use DOMElement;
use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountToTagService;
use SP\Domain\Common\Services\ServiceException;

use function SP\__u;

/**
 * Class XmlAccountExport
 */
final class XmlAccountExport extends XmlExportEntityBase
{

    public function __construct(
        Application                          $application,
        private readonly AccountService      $accountService,
        private readonly AccountToTagService $accountToTagService
    ) {
        parent::__construct($application);
    }

    /**
     * Crear el nodo con los datos
     *
     * @throws ServiceException
     */
    public function export(): DOMElement
    {
        try {
            $this->eventDispatcher->notify(
                'run.export.process.account',
                new Event($this, EventMessage::build()->addDescription(__u('Exporting accounts')))
            );

            $accounts = $this->accountService->getAllBasic();

            // Crear el nodo de cuentas
            $nodeAccounts = $this->document->createElement('Accounts');

            if ($nodeAccounts === false) {
                throw ServiceException::error(__u('Unable to create node'));
            }

            if (count($accounts) === 0) {
                return $nodeAccounts;
            }

            foreach ($accounts as $account) {
                $accountName = $this->document->createElement(
                    'name',
                    $this->document->createTextNode($account->getName())->nodeValue
                );
                $accountCustomerId = $this->document->createElement('clientId', (string)$account->getClientId());
                $accountCategoryId = $this->document->createElement('categoryId', (string)$account->getCategoryId());
                $accountLogin = $this->document->createElement(
                    'login',
                    $this->document->createTextNode($account->getLogin())->nodeValue
                );
                $accountUrl = $this->document->createElement(
                    'url',
                    $this->document->createTextNode(
                        $account->getUrl()
                    )->nodeValue
                );
                $accountNotes = $this->document->createElement(
                    'notes',
                    $this->document->createTextNode($account->getNotes())->nodeValue
                );
                $accountPass = $this->document->createElement(
                    'pass',
                    $this->document->createTextNode($account->getPass())->nodeValue
                );
                $accountIV = $this->document->createElement(
                    'key',
                    $this->document->createTextNode($account->getKey())->nodeValue
                );
                $tags = $this->document->createElement('tags');

                foreach ($this->accountToTagService->getTagsByAccountId($account->getId()) as $itemData) {
                    $tag = $this->document->createElement('tag');
                    $tags->appendChild($tag);

                    $tag->setAttribute('id', (string)$itemData->getId());
                }

                // Crear el nodo de cuenta
                $nodeAccount = $this->document->createElement('Account');
                $nodeAccount->setAttribute('id', (string)$account->getId());
                $nodeAccount->appendChild($accountName);
                $nodeAccount->appendChild($accountCustomerId);
                $nodeAccount->appendChild($accountCategoryId);
                $nodeAccount->appendChild($accountLogin);
                $nodeAccount->appendChild($accountUrl);
                $nodeAccount->appendChild($accountNotes);
                $nodeAccount->appendChild($accountPass);
                $nodeAccount->appendChild($accountIV);
                $nodeAccount->appendChild($tags);

                // Añadir cuenta al nodo de cuentas
                $nodeAccounts->appendChild($nodeAccount);
            }

            return $nodeAccounts;
        } catch (Exception $e) {
            throw ServiceException::error($e->getMessage(), __FUNCTION__);
        }
    }
}
