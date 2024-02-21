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

namespace SP\Domain\Import\Services;

use Defuse\Crypto\Exception\CryptoException;
use DOMDocument;
use DOMElement;
use DOMNodeList;
use DOMXPath;
use Exception;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Events\Event;
use SP\Core\Events\EventMessage;
use SP\Domain\Account\Dtos\AccountRequest;
use SP\Domain\Category\Models\Category;
use SP\Domain\Client\Models\Client;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Export\Services\XmlVerify;
use SP\Domain\Tag\Models\Tag;
use SP\Util\VersionUtil;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de importar cuentas desde sysPass
 */
final class SyspassImport extends XmlImportBase implements Import
{
    /**
     * Iniciar la importación desde sysPass.
     *
     * @throws ImportException
     */
    public function doImport(): Import
    {
        try {
            $this->eventDispatcher->notify(
                'run.import.syspass',
                new Event($this, EventMessage::factory()->addDescription(__u('sysPass XML Import')))
            );

            if (!empty($this->importParams->getMasterPassword())) {
                $this->mPassValidHash = Hash::checkHashKey(
                    $this->importParams->getMasterPassword(),
                    $this->configService->getByParam('masterPwd')
                );
            }

            $this->version = $this->getXmlVersion();

            if ($this->detectEncrypted()) {
                if ($this->importParams->getPassword() === '') {
                    throw new ImportException(__u('Encryption password not set'), SPException::INFO);
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
            throw new ImportException(
                $e->getMessage(),
                SPException::CRITICAL
            );
        }
    }

    /**
     * Obtener la versión del XML
     */
    protected function getXmlVersion()
    {
        return VersionUtil::versionToInteger(
            (new DOMXPath($this->xmlDOM))
                ->query('/Root/Meta/Version')->item(0)->nodeValue
        );
    }

    /**
     * Verificar si existen datos encriptados
     */
    protected function detectEncrypted(): bool
    {
        return $this->xmlDOM->getElementsByTagName('Encrypted')->length > 0;
    }

    /**
     * Procesar los datos encriptados y añadirlos al árbol DOM desencriptados
     *
     * @throws ImportException
     */
    protected function processEncrypted(): void
    {
        $hash = $this->xmlDOM
            ->getElementsByTagName('Encrypted')
            ->item(0)
            ->getAttribute('hash');

        if (!empty($hash)
            && !Hash::checkHashKey($this->importParams->getPassword(), $hash)
        ) {
            throw new ImportException(__u('Wrong encryption password'));
        }

        /** @var DOMElement $node */
        foreach ($this->xmlDOM->getElementsByTagName('Data') as $node) {
            try {
                if ($this->version >= 210 && $this->version <= 310) {
                    $xmlDecrypted = Crypt::decrypt(
                        base64_decode($node->nodeValue),
                        $node->getAttribute('key'),
                        $this->importParams->getPassword()
                    );
                } else {
                    if ($this->version >= 320) {
                        $xmlDecrypted = Crypt::decrypt(
                            $node->nodeValue,
                            $node->getAttribute('key'),
                            $this->importParams->getPassword()
                        );
                    } else {
                        throw new ImportException(__u('The file was exported with an old sysPass version (<= 2.10).'));
                    }
                }
            } catch (CryptoException $e) {
                processException($e);

                $this->eventDispatcher->notify('exception', new Event($e));

                continue;
            }

            $newXmlData = new DOMDocument();

            if ($newXmlData->loadXML($xmlDecrypted) === false) {
                throw new ImportException(__u('Wrong encryption password'));
            }

            $this->xmlDOM->documentElement
                ->appendChild(
                    $this->xmlDOM->importNode($newXmlData->documentElement, true)
                );
        }

        // Eliminar los datos encriptados tras desencriptar los mismos
        if ($this->xmlDOM->getElementsByTagName('Data')->length > 0) {
            $nodeData = $this->xmlDOM
                ->getElementsByTagName('Encrypted')
                ->item(0);
            $nodeData->parentNode->removeChild($nodeData);
        }

        $this->eventDispatcher->notify(
            'run.import.syspass.process.decryption',
            new Event($this, EventMessage::factory()->addDescription(__u('Data unencrypted')))
        );
    }

    /**
     * Checks XML file's data integrity using the signed hash
     */
    protected function checkIntegrity(): void
    {
        $key = $this->importParams->getPassword() ?: sha1($this->configData->getPasswordSalt());

        if (!XmlVerify::checkXmlHash($this->xmlDOM, $key)) {
            $this->eventDispatcher->notify(
                'run.import.syspass.process.verify',
                new Event(
                    $this,
                    EventMessage::factory()
                        ->addDescription(__u('Error while checking integrity hash'))
                        ->addDescription(
                            __u(
                                'If you are importing an exported file from the same origin, the data could be compromised.'
                            )
                        )
                )
            );
        }
    }

    /**
     * Obtener las categorías y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processCategories(): void
    {
        $this->getNodesData(
            'Categories',
            'Category',
            function (DOMElement $category) {
                $categoryData = new Category();

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
                    $this->addWorkingItem(
                        'category',
                        (int)$category->getAttribute('id'),
                        $this->addCategory($categoryData)
                    );

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.category',
                        new Event(
                            $this,
                            EventMessage::factory()
                                ->addDetail(__u('Category imported'), $categoryData->getName())
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     */
    protected function processClients(): void
    {
        $this->getNodesData(
            'Clients',
            'Client',
            function (DOMElement $client) {
                $clientData = new Client();

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
                    $this->addWorkingItem(
                        'client',
                        (int)$client->getAttribute('id'),
                        $this->addClient($clientData)
                    );

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.client',
                        new Event(
                            $this,
                            EventMessage::factory()
                                ->addDetail(__u('Client imported'), $clientData->getName())
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener los clientes y añadirlos a sysPass.
     *
     * @throws ImportException
     * @deprecated
     */
    protected function processCustomers(): void
    {
        $this->getNodesData(
            'Customers',
            'Customer',
            function (DOMElement $client) {
                $clientData = new Client();

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
                    $this->addWorkingItem(
                        'client',
                        (int)$client->getAttribute('id'),
                        $this->addClient($clientData)
                    );

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.customer',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Client imported'), $clientData->getName())
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Obtener las etiquetas y añadirlas a sysPass.
     *
     * @throws ImportException
     */
    protected function processTags(): void
    {
        $this->getNodesData(
            'Tags',
            'Tag',
            function (DOMElement $tag) {
                $tagData = new Tag();

                foreach ($tag->childNodes as $node) {
                    if (isset($node->tagName) && $node->tagName === 'name') {
                        $tagData->setName($node->nodeValue);
                    }
                }

                try {
                    $this->addWorkingItem(
                        'tag',
                        (int)$tag->getAttribute('id'),
                        $this->addTag($tagData)
                    );

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.tag',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Tag imported'), $tagData->getName())
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            },
            false
        );
    }

    /**
     * Obtener los datos de las cuentas de sysPass y crearlas.
     *
     * @throws ImportException
     */
    protected function processAccounts(): void
    {
        $this->getNodesData(
            'Accounts',
            'Account',
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
                                $accountRequest->categoryId =
                                    $this->getWorkingItem('category', (int)$node->nodeValue);
                                break;
                            case 'clientId';
                            case 'customerId';
                                $accountRequest->clientId =
                                    $this->getWorkingItem('client', (int)$node->nodeValue);
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

                    $this->eventDispatcher->notify(
                        'run.import.syspass.process.account',
                        new Event(
                            $this,
                            EventMessage::factory()->addDetail(__u('Account imported'), $accountRequest->name)
                        )
                    );
                } catch (Exception $e) {
                    processException($e);

                    $this->eventDispatcher->notify('exception', new Event($e));
                }
            }
        );
    }

    /**
     * Procesar las etiquetas de la cuenta
     */
    protected function processAccountTags(DOMNodeList $nodes): array
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
