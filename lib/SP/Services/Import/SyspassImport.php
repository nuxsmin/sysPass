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

use Defuse\Crypto\Exception\CryptoException;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Crypt\OldCrypt;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\DataModel\TagData;
use SP\Services\Account\AccountRequest;
use SP\Services\Export\XmlVerifyService;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde sysPass
 */
final class SyspassImport extends XmlImportBase implements ImportInterface
{
    /**
     * Iniciar la importación desde sysPass.
     *
     * @return ImportInterface
     * @throws ImportException
     */
    public function doImport()
    {
        try {
            $this->eventDispatcher->notifyEvent('run.import.syspass',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('sysPass XML Import')))
            );

            if ($this->importParams->getImportMasterPwd() !== '') {
                $this->mPassValidHash = Hash::checkHashKey($this->importParams->getImportMasterPwd(), $this->configService->getByParam('masterPwd'));
            }

            $this->version = $this->getXmlVersion();

            if ($this->detectEncrypted()) {
                if ($this->importParams->getImportPwd() === '') {
                    throw new ImportException(__u('Encryption password not set'), ImportException::INFO);
                }

                $this->processEncrypted();
            }

            $this->checkIntegrity();

            $this->processCategories();

            if ($this->version >= VersionUtil::versionToInteger('3.0.0')) {
                $this->processClients();
            } else {
                $this->processCustomers();
            }

            $this->processTags();
            $this->processAccounts();

            return $this;
        } catch (ImportException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new ImportException($e->getMessage(), ImportException::CRITICAL);
        }
    }

    /**
     * Obtener la versión del XML
     */
    protected function getXmlVersion()
    {
        return VersionUtil::versionToInteger((new DOMXPath($this->xmlDOM))->query('/Root/Meta/Version')->item(0)->nodeValue);
    }

    /**
     * Verificar si existen datos encriptados
     *
     * @return bool
     */
    protected function detectEncrypted()
    {
        return ($this->xmlDOM->getElementsByTagName('Encrypted')->length > 0);
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     *
     * @throws ImportException
     */
    protected function processEncrypted()
    {
        $hash = $this->xmlDOM->getElementsByTagName('Encrypted')->item(0)->getAttribute('hash');

        if (!empty($hash) && !Hash::checkHashKey($this->importParams->getImportPwd(), $hash)) {
            throw new ImportException(__u('Wrong encryption password'));
        }

        foreach ($this->xmlDOM->getElementsByTagName('Data') as $node) {
            /** @var $node DOMElement */
            $data = base64_decode($node->nodeValue);

            try {
                if ($this->version >= 210) {
                    $xmlDecrypted = Crypt::decrypt($data, $node->getAttribute('key'), $this->importParams->getImportPwd());
                } else {
                    $xmlDecrypted = OldCrypt::getDecrypt($data, base64_decode($node->getAttribute('iv')), $this->importParams->getImportPwd());
                }
            } catch (CryptoException $e) {
                processException($e);

                $this->eventDispatcher->notifyEvent('exception', new Event($e));

                continue;
            }

            $newXmlData = new DOMDocument();

            if ($newXmlData->loadXML($xmlDecrypted) === false) {
                throw new ImportException(__u('Wrong encryption password'));
            }

            $this->xmlDOM->documentElement->appendChild($this->xmlDOM->importNode($newXmlData->documentElement, TRUE));
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->xmlDOM->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->xmlDOM->getElementsByTagName('Encrypted')->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }

        $this->eventDispatcher->notifyEvent('run.import.syspass.process.decryption',
            new Event($this, EventMessage::factory()
                ->addDescription(__u('Data unencrypted')))
        );
    }

    /**
     * Checks XML file's data integrity using the signed hash
     */
    protected function checkIntegrity()
    {
        $key = $this->importParams->getImportPwd() ?: sha1($this->configData->getPasswordSalt());

        if (!XmlVerifyService::checkXmlHash($this->xmlDOM, $key)) {
            $this->eventDispatcher->notifyEvent('run.import.syspass.process.verify',
                new Event($this, EventMessage::factory()
                    ->addDescription(__u('Error while checking integrity hash'))
                    ->addDescription(__u('If you are importing an exported file from the same origin, the data could be compromised.')))
            );
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processCategories()
    {
        $this->getNodesData('Categories', 'Category',
            function (DOMElement $category) {
                $categoryData = new CategoryData();

                foreach ($category->childNodes as $node) {
                    if (isset($node->tagName)) {
                        switch ($node->tagName) {
                            case 'name':
                                $categoryData->setName($node->nodeValue);
                                break;
                            case 'description':
                                $categoryData->setDescription($node->nodeValue);
                                break;
                        }
                    }
                }

                try {
                    $this->addWorkingItem('category', (int)$category->getAttribute('id'), $this->addCategory($categoryData));

                    $this->eventDispatcher->notifyEvent('run.import.syspass.process.category',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Category imported'), $categoryData->getName()))
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));
                }
            });
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     */
    protected function processClients()
    {
        $this->getNodesData('Clients', 'Client',
            function (DOMElement $client) {
                $clientData = new ClientData();

                foreach ($client->childNodes as $node) {
                    if (isset($node->tagName)) {
                        switch ($node->tagName) {
                            case 'name':
                                $clientData->setName($node->nodeValue);
                                break;
                            case 'description':
                                $clientData->setDescription($node->nodeValue);
                                break;
                        }
                    }
                }

                try {
                    $this->addWorkingItem('client', (int)$client->getAttribute('id'), $this->addClient($clientData));

                    $this->eventDispatcher->notifyEvent('run.import.syspass.process.client',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Client imported'), $clientData->getName()))
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));
                }
            });
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     * @deprecated
     */
    protected function processCustomers()
    {
        $this->getNodesData('Customers', 'Customer',
            function (DOMElement $client) {
                $clientData = new ClientData();

                foreach ($client->childNodes as $node) {
                    if (isset($node->tagName)) {
                        switch ($node->tagName) {
                            case 'name':
                                $clientData->setName($node->nodeValue);
                                break;
                            case 'description':
                                $clientData->setDescription($node->nodeValue);
                                break;
                        }
                    }
                }

                try {
                    $this->addWorkingItem('client', (int)$client->getAttribute('id'), $this->addClient($clientData));

                    $this->eventDispatcher->notifyEvent('run.import.syspass.process.customer',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Client imported'), $clientData->getName()))
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));
                }
            });
    }

    /**
     * Obtener las etiquetas y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processTags()
    {
        $this->getNodesData('Tags', 'Tag',
            function (DOMElement $tag) {
                $tagData = new TagData();

                foreach ($tag->childNodes as $node) {
                    if (isset($node->tagName)) {
                        switch ($node->tagName) {
                            case 'name':
                                $tagData->setName($node->nodeValue);
                                break;
                        }
                    }
                }

                try {
                    $this->addWorkingItem('tag', (int)$tag->getAttribute('id'), $this->addTag($tagData));

                    $this->eventDispatcher->notifyEvent('run.import.syspass.process.tag',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Tag imported'), $tagData->getName()))
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));
                }
            }, false);
    }

    /**
     * Obtener los datos de las cuentas de sysPass y crearlas.
     *
     * @throws ImportException
     */
    protected function processAccounts()
    {
        $this->getNodesData('Accounts', 'Account',
            function (DOMElement $account) {
                $accountRequest = new AccountRequest();

                /** @var DOMElement $node */
                foreach ($account->childNodes as $node) {
                    if (isset($node->tagName)) {
                        switch ($node->tagName) {
                            case 'name';
                                $accountRequest->name = $node->nodeValue;
                                break;
                            case 'login';
                                $accountRequest->login = $node->nodeValue;
                                break;
                            case 'categoryId';
                                $accountRequest->categoryId = $this->getWorkingItem('category', (int)$node->nodeValue);
                                break;
                            case 'clientId';
                            case 'customerId';
                                $accountRequest->clientId = $this->getWorkingItem('client', (int)$node->nodeValue);
                                break;
                            case 'url';
                                $accountRequest->url = $node->nodeValue;
                                break;
                            case 'pass';
                                $accountRequest->pass = $node->nodeValue;
                                break;
                            case 'key';
                                $accountRequest->key = $node->nodeValue;
                                break;
                            case 'notes';
                                $accountRequest->notes = $node->nodeValue;
                                break;
                            case 'tags':
                                $accountRequest->tags = $this->processAccountTags($node->childNodes);
                        }
                    }
                }

                try {
                    $this->addAccount($accountRequest);

                    $this->eventDispatcher->notifyEvent('run.import.syspass.process.account',
                        new Event($this, EventMessage::factory()
                            ->addDetail(__u('Account imported'), $accountRequest->name))
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notifyEvent('exception', new Event($e));
                }
            });
    }

    /**
     * Procesar las etiquetas de la cuenta
     *
     * @param DOMNodeList $nodes
     *
     * @return array
     */
    protected function processAccountTags(DOMNodeList $nodes)
    {
        $tags = [];

        if ($nodes->length > 0) {
            /** @var DOMElement $node */
            foreach ($nodes as $node) {
                if (isset($node->tagName)) {
                    $tags[] = $this->getWorkingItem('tag', (int)$node->getAttribute('id'));
                }
            }
        }

        return $tags;
    }
}