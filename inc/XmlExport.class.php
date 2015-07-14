<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@syspass.org
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
 *
 */

namespace SP;

// TODO: error catching...

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
    private $_xml;
    /**
     * @var \DOMElement
     */
    private $_root;
    /**
     * @var string
     */
    private $_exportFile;
    /**
     * @var string
     */
    private $_exportPass = null;
    /**
     * @var bool
     */
    private $_encrypted = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->_xml = new \DOMDocument('1.0', 'UTF-8');
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

        if (!is_null($pass) && !empty($pass)) {
            $xml->setExportPass($pass);
            $xml->setEncrypted(true);
        }

        return $xml->makeXML();
    }

    /**
     * Establecer la clave de exportación
     *
     * @param string $exportPass
     */
    public function setExportPass($exportPass)
    {
        $this->_exportPass = $exportPass;
    }

    /**
     * Crear el documento XML y guardarlo
     *
     * @return bool
     */
    public function makeXML()
    {
        try {
            $this->createRoot();
            $this->createMeta();
            $this->createCategories();
            $this->createCustomers();
            $this->createAccounts();
            $this->createHash();
            $this->writeXML();
        } catch (SPException $e) {
            return false;
        }

        return true;
    }

    /**
     * Crear el nodo raíz
     */
    private function createRoot()
    {
        $root = $this->_xml->createElement('root');
        $this->_root = $this->_xml->appendChild($root);
    }

    /**
     * Crear el nodo con metainformación del archivo XML
     */
    private function createMeta()
    {
        $nodeMeta = $this->_xml->createElement('Meta');
        $metaGenerator = $this->_xml->createElement('Generator', 'sysPass');
        $metaVersion = $this->_xml->createElement('Version', implode('.', Util::getVersion()));
        $metaTime = $this->_xml->createElement('Time', time());

        $nodeMeta->appendChild($metaGenerator);
        $nodeMeta->appendChild($metaVersion);
        $nodeMeta->appendChild($metaTime);

        $this->_root->appendChild($nodeMeta);
    }

    /**
     * Crear el nodo con los datos de las categorías
     */
    private function createCategories()
    {
        // Crear el nodo de categorías
        $nodeCategories = $this->_xml->createElement('Categories');

        $categories = Category::getCategories();

        foreach ($categories as $category) {
            $categoryId = $this->_xml->createElement('id', $category->category_id);
            $categoryName = $this->_xml->createElement('name', $this->escapeChars($category->category_name));

            // Crear el nodo de categoría
            $nodeCategory = $this->_xml->createElement('Category');
            $nodeCategory->appendChild($categoryId);
            $nodeCategory->appendChild($categoryName);

            // Añadir categoría al nodo de categorías
            $nodeCategories->appendChild($nodeCategory);
        }

        $this->appendNode($nodeCategories);
    }

    /**
     * Escapar carácteres no válidos en XML
     *
     * @param $data string Los datos a escapar
     * @return mixed
     */
    private function escapeChars($data)
    {
        $arrStrFrom = array("&", "<", ">", "\"", "\'");
        $arrStrTo = array("&#38;", "&#60;", "&#62;", "&#34;", "&#39;");

        return str_replace($arrStrFrom, $arrStrTo, $data);
    }

    /**
     * Añadir un nuevo nodo al árbol raíz
     *
     * @param \DOMElement $node El nodo a añadir
     */
    private function appendNode(\DOMElement $node)
    {
        // Si se utiliza clave de encriptación los datos se encriptan en un nuevo nodo:
        // Encrypted -> Data
        if ($this->_encrypted === true) {
            // Obtener el nodo en formato XML
            $nodeXML = $this->_xml->saveXML($node);

            // Crear los datos encriptados con la información del nodo
            $encrypted = Crypt::mkEncrypt($nodeXML, $this->_exportPass);
            $encryptedIV = Crypt::$strInitialVector;

            // Buscar si existe ya un nodo para el conjunto de datos encriptados
            $encryptedNode = $this->_root->getElementsByTagName('Encrypted')->item(0);

            if (!$encryptedNode instanceof \DOMElement) {
                $encryptedNode = $this->_xml->createElement('Encrypted');
            }

            // Crear el nodo hijo con los datos encriptados
            $encryptedData = $this->_xml->createElement('Data', base64_encode($encrypted));

            $encryptedDataIV = $this->_xml->createAttribute('iv');
            $encryptedDataIV->value = base64_encode($encryptedIV);

            // Añadir nodos de datos
            $encryptedData->appendChild($encryptedDataIV);
            $encryptedNode->appendChild($encryptedData);

            // Añadir el nodo encriptado
            $this->_root->appendChild($encryptedNode);
        } else {
            $this->_root->appendChild($node);
        }

    }

    /**
     * Crear el nodo con los datos de los clientes
     */
    private function createCustomers()
    {
        // Crear el nodo de categorías
        $nodeCustomers = $this->_xml->createElement('Customers');

        $customers = Customer::getCustomers();

        foreach ($customers as $customer) {
            $customerId = $this->_xml->createElement('id', $customer->customer_id);
            $customerName = $this->_xml->createElement('name', $this->escapeChars($customer->customer_name));

            // Crear el nodo de categoría
            $nodeCustomer = $this->_xml->createElement('Customer');
            $nodeCustomer->appendChild($customerId);
            $nodeCustomer->appendChild($customerName);

            // Añadir categoría al nodo de categorías
            $nodeCustomers->appendChild($nodeCustomer);
        }

        $this->appendNode($nodeCustomers);
    }

    /**
     * Crear el nodo con los datos de las cuentas
     */
    private function createAccounts()
    {
        // Crear el nodo de cuentas
        $nodeAccounts = $this->_xml->createElement('Accounts');

        $accounts = Account::getAccountsData();

        foreach ($accounts as $account) {
            $accountId = $this->_xml->createAttribute('id');
            $accountId->value = $account->account_id;
            $accountName = $this->_xml->createElement('name', $this->escapeChars($account->account_name));
            $accountCustomerId = $this->_xml->createElement('customerId', $account->account_customerId);
            $accountCategoryId = $this->_xml->createElement('categoryId', $account->account_categoryId);
            $accountLogin = $this->_xml->createElement('login', $this->escapeChars($account->account_login));
            $accountUrl = $this->_xml->createElement('url', $this->escapeChars($account->account_url));
            $accountNotes = $this->_xml->createElement('notes', $this->escapeChars($account->account_notes));
            $accountPass = $this->_xml->createElement('pass', $this->escapeChars(base64_encode($account->account_pass)));
            $accountIV = $this->_xml->createElement('passiv', $this->escapeChars(base64_encode($account->account_IV)));

            // Crear el nodo de cuenta
            $nodeAccount = $this->_xml->createElement('Account');
            $nodeAccount->appendChild($accountId);
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
    }

    /**
     * Crear el hash del archivo XML e insertarlo en el árbol DOM
     */
    private function createHash()
    {
        if ( $this->_encrypted === true ){
            $hash = md5($this->getNodeXML('Encrypted'));
        } else {
            $hash = md5($this->getNodeXML('Categories') . $this->getNodeXML('Customers') . $this->getNodeXML('Accounts'));
        }

        $metaHash = $this->_xml->createElement('Hash', $hash);

        $nodeMeta = $this->_root->getElementsByTagName('Meta')->item(0);
        $nodeMeta->appendChild($metaHash);
    }

    /**
     * Devuelve el código XML de un nodo
     *
     * @param $node string El nodo a devolver
     * @return string
     */
    private function getNodeXML($node)
    {
        return $this->_xml->saveXML($this->_root->getElementsByTagName($node)->item(0));
    }

    /**
     * Generar el archivo XML
     *
     * @return bool
     * @throws SPException
     */
    private function writeXML()
    {
        $siteName = Util::getAppInfo('appname');

        $exportDstDir = Init::$SERVERROOT . DIRECTORY_SEPARATOR . 'backup';
        $this->_exportFile = $exportDstDir . DIRECTORY_SEPARATOR . $siteName . '.xml';

        $this->_xml->formatOutput = true;
        $this->_xml->preserveWhiteSpace = false;

        if (!$this->_xml->save($this->_exportFile)) {
            throw new SPException(SPException::SP_CRITICAL, _('Error al crear el archivo XML'));
        }

        return true;
    }

    /**
     * @param boolean $encrypted
     */
    public function setEncrypted($encrypted)
    {
        $this->_encrypted = $encrypted;
    }

    /**
     * Devolver la cabecera HTTP para documentos XML
     */
    private function setHeader()
    {
        Header('Content-type: text/xml');
    }
}