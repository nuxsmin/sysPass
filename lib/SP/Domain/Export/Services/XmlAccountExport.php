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

namespace SP\Domain\Export\Services;

use DOMDocument;
use DOMElement;
use Exception;
use SP\Core\Application;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Ports\AccountService;
use SP\Domain\Account\Ports\AccountToTagService;
use SP\Domain\Common\Services\Service;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Export\Ports\XmlAccountExportService;

use function SP\__u;

/**
 * Class XmlAccountExport
 */
final class XmlAccountExport extends Service implements XmlAccountExportService
{
    public function __construct(
        Application                          $application,
        private readonly AccountService      $accountService,
        private readonly AccountToTagService $accountToTagService,

    ) {
        parent::__construct($application);
    }

    /**
     * Crear el nodo con los datos
     *
     * @throws ServiceException
     */
    public function export(DOMDocument $document): DOMElement
    {
        try {
            $this->eventDispatcher->notify(
                'run.export.process.account',
                new Event($this, EventMessage::factory()->addDescription(__u('Exporting accounts')))
            );

            $accounts = $this->accountService->getAllBasic();

            // Crear el nodo de cuentas
            $nodeAccounts = $document->createElement('Accounts');

            if ($nodeAccounts === false) {
                throw ServiceException::error(__u('Unable to create node'));
            }

            if (count($accounts) === 0) {
                return $nodeAccounts;
            }

            foreach ($accounts as $account) {
                $accountName = $document->createElement(
                    'name',
                    $document->createTextNode($account->getName())->nodeValue
                );
                $accountCustomerId = $document->createElement('clientId', $account->getClientId());
                $accountCategoryId = $document->createElement('categoryId', $account->getCategoryId());
                $accountLogin = $document->createElement(
                    'login',
                    $document->createTextNode($account->getLogin())->nodeValue
                );
                $accountUrl = $document->createElement('url', $document->createTextNode($account->getUrl())->nodeValue);
                $accountNotes = $document->createElement(
                    'notes',
                    $document->createTextNode($account->getNotes())->nodeValue
                );
                $accountPass = $document->createElement(
                    'pass',
                    $document->createTextNode($account->getPass())->nodeValue
                );
                $accountIV = $document->createElement('key', $document->createTextNode($account->getKey())->nodeValue);
                $tags = $document->createElement('tags');

                foreach ($this->accountToTagService->getTagsByAccountId($account->getId()) as $itemData) {
                    $tag = $document->createElement('tag');
                    $tags->appendChild($tag);

                    $tag->setAttribute('id', $itemData->getId());
                }

                // Crear el nodo de cuenta
                $nodeAccount = $document->createElement('Account');
                $nodeAccount->setAttribute('id', $account->getId());
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
