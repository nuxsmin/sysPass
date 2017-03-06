<?php
/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
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

namespace SP\Core;

use Defuse\Crypto\Exception\CryptoException;
use SP\Account\AccountUtil;
use SP\Config\Config;
use SP\Core\Crypt\Crypt;
use SP\Core\Crypt\Hash;
use SP\Core\Exceptions\SPException;
use SP\DataModel\CategoryData;
use SP\Log\Email;
use SP\Log\Log;
use SP\Mgmt\Categories\Category;
use SP\Mgmt\Customers\Customer;
use SP\Mgmt\Tags\Tag;
use SP\Util\Util;

defined('APP_ROOT') || die();

/**
 * Clase XmlExport para realizar la exportación de las cuentas de sysPass a formato XML
 *
 * @package SP
 */
class XmlExport
{
    /**
     * @var \DOMDocument
     */
    private $xml;
    /**
     * @var \DOMElement
     */
    private $root;
    /**
     * @var string
     */
    private $exportPass;
    /**
     * @var bool
     */
    private $encrypted = false;
    /**
     * @var string
     */
    private $exportDir = '';
    /**
     * @var string
     */
    private $exportFile = '';

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->xml = new \DOMDocument('1.0', 'UTF-8');
    }

    /**
     * Realiza la exportación de las cuentas a XML
     *
     * @param null $pass string La clave de exportación
     * @return bool
     */
    public static function doExport($pass = null)
    {
        $xml = new XmlExport();

        if (null !== $pass && !empty($pass)) {
            $xml->setExportPass($pass);
            $xml->setEncrypted(true);
        }

        $xml->setExportDir(Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'backup');
        $xml->setExportFile();
        $xml->deleteOldExports();

        return $xml->makeXML();
    }

    /**
     * Establecer la clave de exportación
     *
     * @param string $exportPass
     */
    public function setExportPass($exportPass)
    {
        $this->exportPass = $exportPass;
    }

    /**
     * @param boolean $encrypted
     */
    public function setEncrypted($encrypted)
    {
        $this->encrypted = $encrypted;
    }

    /**
     * Crear el documento XML y guardarlo
     *
     * @return bool
     * @throws \phpmailer\phpmailerException
     */
    public function makeXML()
    {
        $Log = new Log();
        $LogMessage = $Log->getLogMessage();
        $LogMessage->setAction(__('Exportar XML', false));

        try {
            $this->checkExportDir();
            $this->createRoot();
            $this->createMeta();
            $this->createCategories();
            $this->createCustomers();
            $this->createTags();
            $this->createAccounts();
            $this->createHash();
            $this->writeXML();
        } catch (SPException $e) {
            $LogMessage->addDescription(__('Error al realizar la exportación de cuentas', false));
            $LogMessage->addDetails($e->getMessage(), $e->getHint());
            $Log->setLogLevel(Log::ERROR);
            $Log->writeLog();

            Email::sendEmail($LogMessage);
            return false;
        }

        $LogMessage->addDescription(__('Exportación de cuentas realizada correctamente', false));
        $Log->writeLog();

        Email::sendEmail($LogMessage);

        return true;
    }

    /**
     * Crear el nodo raíz
     *
     * @throws SPException
     */
    private function createRoot()
    {
        try {
            $root = $this->xml->createElement('Root');
            $this->root = $this->xml->appendChild($root);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con metainformación del archivo XML
     *
     * @throws SPException
     */
    private function createMeta()
    {
        try {
            $nodeMeta = $this->xml->createElement('Meta');
            $metaGenerator = $this->xml->createElement('Generator', 'sysPass');
            $metaVersion = $this->xml->createElement('Version', implode('.', Util::getVersion()));
            $metaTime = $this->xml->createElement('Time', time());
            $metaUser = $this->xml->createElement('User', Session::getUserData()->getUserLogin());
            $metaUser->setAttribute('id', Session::getUserData()->getUserId());
            $metaGroup = $this->xml->createElement('Group', Session::getUserData()->getUsergroupName());
            $metaGroup->setAttribute('id', Session::getUserData()->getUserGroupId());

            $nodeMeta->appendChild($metaGenerator);
            $nodeMeta->appendChild($metaVersion);
            $nodeMeta->appendChild($metaTime);
            $nodeMeta->appendChild($metaUser);
            $nodeMeta->appendChild($metaGroup);

            $this->root->appendChild($nodeMeta);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las categorías
     *
     * @throws SPException
     */
    private function createCategories()
    {
        $Category = new Category();
        $categories = $Category->getAll();

        if (count($categories) === 0) {
            return;
        }

        try {
            // Crear el nodo de categorías
            $nodeCategories = $this->xml->createElement('Categories');

            foreach ($categories as $CategoryData) {
                /** @var $CategoryData CategoryData */
                $categoryName = $this->xml->createElement('name', $this->escapeChars($CategoryData->getCategoryName()));
                $categoryDescription = $this->xml->createElement('description', $this->escapeChars($CategoryData->getCategoryDescription()));

                // Crear el nodo de categoría
                $nodeCategory = $this->xml->createElement('Category');
                $nodeCategory->setAttribute('id', $CategoryData->getCategoryId());
                $nodeCategory->appendChild($categoryName);
                $nodeCategory->appendChild($categoryDescription);

                // Añadir categoría al nodo de categorías
                $nodeCategories->appendChild($nodeCategory);
            }

            $this->appendNode($nodeCategories);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Escapar carácteres no válidos en XML
     *
     * @param $data string Los datos a escapar
     * @return mixed
     */
    private function escapeChars($data)
    {
        $arrStrFrom = ['&', '<', '>', '"', '\''];
        $arrStrTo = ['&#38;', '&#60;', '&#62;', '&#34;', '&#39;'];

        return str_replace($arrStrFrom, $arrStrTo, $data);
    }

    /**
     * Añadir un nuevo nodo al árbol raíz
     *
     * @param \DOMElement $node El nodo a añadir
     * @throws SPException
     */
    private function appendNode(\DOMElement $node)
    {
        try {
            // Si se utiliza clave de encriptación los datos se encriptan en un nuevo nodo:
            // Encrypted -> Data
            if ($this->encrypted === true) {
                // Obtener el nodo en formato XML
                $nodeXML = $this->xml->saveXML($node);

                // Crear los datos encriptados con la información del nodo
                $securedKey = Crypt::makeSecuredKey($this->exportPass);
                $encrypted = Crypt::encrypt($nodeXML, $securedKey, $this->exportPass);

                // Buscar si existe ya un nodo para el conjunto de datos encriptados
                $encryptedNode = $this->root->getElementsByTagName('Encrypted')->item(0);

                if (!$encryptedNode instanceof \DOMElement) {
                    $encryptedNode = $this->xml->createElement('Encrypted');
                    $encryptedNode->setAttribute('hash', Hash::hashKey($this->exportPass));
                }

                // Crear el nodo hijo con los datos encriptados
                $encryptedData = $this->xml->createElement('Data', base64_encode($encrypted));

                $encryptedDataIV = $this->xml->createAttribute('key');
                $encryptedDataIV->value = $securedKey;

                // Añadir nodos de datos
                $encryptedData->appendChild($encryptedDataIV);
                $encryptedNode->appendChild($encryptedData);

                // Añadir el nodo encriptado
                $this->root->appendChild($encryptedNode);
            } else {
                $this->root->appendChild($node);
            }
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        } catch (CryptoException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de los clientes
     *
     * #@throws SPException
     */
    private function createCustomers()
    {
        $customers = Customer::getItem()->getAll();

        if (count($customers) === 0) {
            return;
        }

        try {
            // Crear el nodo de clientes
            $nodeCustomers = $this->xml->createElement('Customers');

            foreach ($customers as $CustomerData) {
                $customerName = $this->xml->createElement('name', $this->escapeChars($CustomerData->getCustomerName()));
                $customerDescription = $this->xml->createElement('description', $this->escapeChars($CustomerData->getCustomerDescription()));

                // Crear el nodo de clientes
                $nodeCustomer = $this->xml->createElement('Customer');
                $nodeCustomer->setAttribute('id', $CustomerData->getCustomerId());
                $nodeCustomer->appendChild($customerName);
                $nodeCustomer->appendChild($customerDescription);

                // Añadir cliente al nodo de clientes
                $nodeCustomers->appendChild($nodeCustomer);
            }

            $this->appendNode($nodeCustomers);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las etiquetas
     *
     * #@throws SPException
     */
    private function createTags()
    {
        $Tags = Tag::getItem()->getAll();

        if (count($Tags) === 0) {
            return;
        }

        try {
            // Crear el nodo de etiquetas
            $nodeTags= $this->xml->createElement('Tags');

            foreach ($Tags as $TagData) {
                $tagName = $this->xml->createElement('name', $this->escapeChars($TagData->getTagName()));

                // Crear el nodo de etiquetas
                $nodeTag = $this->xml->createElement('Tag');
                $nodeTag->setAttribute('id', $TagData->getTagId());
                $nodeTag->appendChild($tagName);

                // Añadir etiqueta al nodo de etiquetas
                $nodeTags->appendChild($nodeTag);
            }

            $this->appendNode($nodeTags);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el nodo con los datos de las cuentas
     *
     * @throws SPException
     */
    private function createAccounts()
    {
        $accounts = AccountUtil::getAccountsData();

        if (count($accounts) === 0) {
            return;
        }

        try {
            // Crear el nodo de cuentas
            $nodeAccounts = $this->xml->createElement('Accounts');

            foreach ($accounts as $account) {
                $accountName = $this->xml->createElement('name', $this->escapeChars($account->account_name));
                $accountCustomerId = $this->xml->createElement('customerId', $account->account_customerId);
                $accountCategoryId = $this->xml->createElement('categoryId', $account->account_categoryId);
                $accountLogin = $this->xml->createElement('login', $this->escapeChars($account->account_login));
                $accountUrl = $this->xml->createElement('url', $this->escapeChars($account->account_url));
                $accountNotes = $this->xml->createElement('notes', $this->escapeChars($account->account_notes));
                $accountPass = $this->xml->createElement('pass', $this->escapeChars($account->account_pass));
                $accountIV = $this->xml->createElement('key', $this->escapeChars($account->account_key));

                // Crear el nodo de cuenta
                $nodeAccount = $this->xml->createElement('Account');
                $nodeAccount->setAttribute('id', $account->account_id);
                $nodeAccount->appendChild($accountName);
                $nodeAccount->appendChild($accountCustomerId);
                $nodeAccount->appendChild($accountCategoryId);
                $nodeAccount->appendChild($accountLogin);
                $nodeAccount->appendChild($accountUrl);
                $nodeAccount->appendChild($accountNotes);
                $nodeAccount->appendChild($accountPass);
                $nodeAccount->appendChild($accountIV);

                // Añadir cuenta al nodo de cuentas
                $nodeAccounts->appendChild($nodeAccount);
            }

            $this->appendNode($nodeAccounts);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Crear el hash del archivo XML e insertarlo en el árbol DOM
     *
     * @throws \SP\Core\Exceptions\SPException
     */
    private function createHash()
    {
        try {
            if ($this->encrypted === true) {
                $hash = sha1($this->getNodeXML('Encrypted'));
            } else {
                $hash = sha1($this->getNodeXML('Categories') . $this->getNodeXML('Customers') . $this->getNodeXML('Accounts'));
            }

            $metaHash = $this->xml->createElement('Hash', $hash);

            $nodeMeta = $this->root->getElementsByTagName('Meta')->item(0);
            $nodeMeta->appendChild($metaHash);
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Devuelve el código XML de un nodo
     *
     * @param $node string El nodo a devolver
     * @return string
     * @throws SPException
     */
    private function getNodeXML($node)
    {
        try {
            $nodeXML = $this->xml->saveXML($this->root->getElementsByTagName($node)->item(0));
            return $nodeXML;
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Generar el archivo XML
     *
     * @return bool
     * @throws SPException
     */
    private function writeXML()
    {
        try {
            $this->xml->formatOutput = true;
            $this->xml->preserveWhiteSpace = false;

            if (!$this->xml->save($this->exportFile)) {
                throw new SPException(SPException::SP_CRITICAL, __('Error al crear el archivo XML', false));
            }
        } catch (\DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Genera el nombre del archivo usado para la exportación.
     */
    private function setExportFile()
    {
        // Generar hash unico para evitar descargas no permitidas
        $exportUniqueHash = sha1(uniqid('sysPassExport', true));
        Config::getConfig()->setExportHash($exportUniqueHash);
        Config::saveConfig();

        $this->exportFile = $this->exportDir . DIRECTORY_SEPARATOR . Util::getAppInfo('appname') . '-' . $exportUniqueHash . '.xml';
    }

    /**
     * @param string $exportDir
     */
    public function setExportDir($exportDir)
    {
        $this->exportDir = $exportDir;
    }

    /**
     * Comprobar y crear el directorio de exportación.
     *
     * @throws SPException
     * @return bool
     */
    private function checkExportDir()
    {
        if (@mkdir($this->exportDir, 0750) === false && is_dir($this->exportDir) === false) {
            throw new SPException(SPException::SP_CRITICAL, sprintf(__('No es posible crear el directorio de backups ("%s")'), $this->exportDir));
        }

        clearstatcache(true, $this->exportDir);

        if (!is_writable($this->exportDir)) {
            throw new SPException(SPException::SP_CRITICAL, __('Compruebe los permisos del directorio de backups', false));
        }

        return true;
    }

    /**
     * Eliminar los archivos de exportación anteriores
     */
    private function deleteOldExports()
    {
        array_map('unlink', glob($this->exportDir . DIRECTORY_SEPARATOR . '*.xml'));
    }
}