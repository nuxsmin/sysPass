<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
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

use Defuse\Crypto\Exception\CryptoException;
use DOMXPath;
use SP\Account\AccountRequest;
use SP\Config\ConfigDB;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Core\OldCrypt;
use SP\DataModel\CategoryData;
use SP\DataModel\ClientData;
use SP\DataModel\TagData;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde sysPass
 */
class SyspassImport extends XmlImportBase implements ImportInterface
{
    /**
     * Mapeo de etiquetas
     *
     * @var array
     */
    protected $tags = [];
    /**
     * Mapeo de categorías.
     *
     * @var array
     */
    protected $categories = [];
    /**
     * Mapeo de clientes.
     *
     * @var array
     */
    protected $clients = [];

    /**
     * Iniciar la importación desde sysPass.
     *
     * @throws ImportException
     * @return ImportInterface
     */
    public function doImport()
    {
        try {
            if ($this->importParams->getImportMasterPwd() !== '') {
                $this->mPassValidHash = Hash::checkHashKey($this->importParams->getImportMasterPwd(), ConfigDB::getValue('masterPwd'));
            }

            $this->version = $this->getXmlVersion();

            if ($this->detectEncrypted()) {
                if ($this->importParams->getImportPwd() === '') {
                    throw new ImportException(__u('Clave de encriptación no indicada'), ImportException::INFO);
                }

                $this->processEncrypted();
            }

            $this->processCategories();

            if ($this->version >= 300) {
                $this->processClients();
            } else {
                $this->processCustomers();
            }

            $this->processTags();
            $this->processAccounts();

            return $this;
        } catch (ImportException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new ImportException($e->getMessage(), ImportException::CRITICAL);
        }
    }

    /**
     * Obtener la versión del XML
     */
    protected function getXmlVersion()
    {
        return (int)str_replace('.', '', (new DOMXPath($this->xmlDOM))->query('/Root/Meta/Version')->item(0)->nodeValue);
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

        if ($hash !== '' && !Hash::checkHashKey($this->importParams->getImportPwd(), $hash)) {
            throw new ImportException(__u('Clave de encriptación incorrecta'));
        }

        foreach ($this->xmlDOM->getElementsByTagName('Data') as $node) {
            /** @var $node \DOMElement */
            $data = base64_decode($node->nodeValue);

            try {
                if ($this->version >= 210) {
                    $securedKey = Crypt::unlockSecuredKey($node->getAttribute('key'), $this->importParams->getImportPwd());
                    $xmlDecrypted = Crypt::decrypt($data, $securedKey, $this->importParams->getImportPwd());
                } else {
                    $xmlDecrypted = OldCrypt::getDecrypt($data, base64_decode($node->getAttribute('iv'), $this->importParams->getImportPwd()));
                }
            } catch (CryptoException $e) {
                processException($e);

                continue;
            }

            $newXmlData = new \DOMDocument();
//            $newXmlData->preserveWhiteSpace = true;
            if (!$newXmlData->loadXML($xmlDecrypted)) {
                throw new ImportException(__u('Clave de encriptación incorrecta'));
            }

            $newNode = $this->xmlDOM->importNode($newXmlData->documentElement, TRUE);

            $this->xmlDOM->documentElement->appendChild($newNode);
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->xmlDOM->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->xmlDOM->getElementsByTagName('Encrypted')->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }

        $this->eventDispatcher->notifyEvent('run.import.syspass',
            new Event($this,
                EventMessage::factory()
                    ->addDescription(__('Datos desencriptados')))
        );
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processCategories()
    {
        $this->getNodesData('Categories', 'Category',
            function (\DOMElement $category) {
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
                    $this->categories[$category->getAttribute('id')] = $this->addCategory($categoryData);

                    $this->eventDispatcher->notifyEvent('run.import.syspass',
                        new Event($this,
                            EventMessage::factory()
                                ->addDetail(__('Categoría importada'), $categoryData->getName()))
                    );
                } catch (\Exception $e) {
                    processException($e);
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
            function (\DOMElement $client) {
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
                    $this->clients[$client->getAttribute('id')] = $this->addClient($clientData);

                    $this->eventDispatcher->notifyEvent('run.import.syspass',
                        new Event($this,
                            EventMessage::factory()
                                ->addDetail(__('Cliente importado'), $clientData->getName()))
                    );
                } catch (\Exception $e) {
                    processException($e);
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
            function (\DOMElement $client) {
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

                    $this->clients[$client->getAttribute('id')] = $this->addClient($clientData);

                    $this->eventDispatcher->notifyEvent('run.import.syspass',
                        new Event($this,
                            EventMessage::factory()
                                ->addDetail(__('Cliente importado'), $clientData->getName()))
                    );
                } catch (\Exception $e) {
                    processException($e);
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
            function (\DOMElement $tag) {
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
                    $this->tags[$tag->getAttribute('id')] = $this->addTag($tagData);

                    $this->eventDispatcher->notifyEvent('run.import.syspass',
                        new Event($this,
                            EventMessage::factory()
                                ->addDetail(__('Etiqueta importada'), $tagData->getName()))
                    );
                } catch (\Exception $e) {
                    processException($e);
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
            function (\DOMElement $account) {
                $accountRequest = new AccountRequest();

                /** @var \DOMElement $node */
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
                                $accountRequest->categoryId = isset($this->categories[(int)$node->nodeValue]) ? $this->categories[(int)$node->nodeValue] : null;
                                break;
                            case 'clientId';
                            case 'customerId';
                                $accountRequest->clientId = isset($this->clients[(int)$node->nodeValue]) ? $this->clients[(int)$node->nodeValue] : null;
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

                    $this->eventDispatcher->notifyEvent('run.import.syspass',
                        new Event($this,
                            EventMessage::factory()
                                ->addDetail(__('Cuenta importada'), $accountRequest->name))
                    );
                } catch (\Exception $e) {
                    processException($e);
                }
            });
    }

    /**
     * Procesar las etiquetas de la cuenta
     *
     * @param \DOMNodeList $nodes
     * @return array
     */
    protected function processAccountTags(\DOMNodeList $nodes)
    {
        $tags = [];

        if ($nodes->length > 0) {
            /** @var \DOMElement $node */
            foreach ($nodes as $node) {
                if (isset($node->tagName)) {
                    $tags[] = $node->getAttribute('id');
                }
            }
        }

        return $tags;
    }
}