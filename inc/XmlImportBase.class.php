<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rub�n Dom�nguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

abstract class XmlImportBase
{
    /**
     * El id de usuario propietario de la cuenta.
     *
     * @var int
     */
    public $userId = 0;
    /**
     * El id del grupo propietario de la cuenta.
     *
     * @var int
     */
    public $userGroupId = 0;
    /**
     * Nombre de la cuenta.
     *
     * @var string
     */
    protected $_accountName = '';
    /**
     * Id del cliente.
     *
     * @var int
     */
    protected $_customerId = 0;
    /**
     * Id de categoria.
     *
     * @var int
     */
    protected $_categoryId = 0;
    /**
     * Login de la cuenta.
     *
     * @var string
     */
    protected $_accountLogin = '';
    /**
     * Url de la cuenta.
     *
     * @var string
     */
    protected $_accountUrl = '';
    /**
     * Notas de la cuenta.
     *
     * @var string
     */
    protected $_accountNotes = '';
    /**
     * Clave de la cuenta.
     *
     * @var string
     */
    protected $_accountPass = '';
    /**
     * IV de la clave de la cuenta.
     *
     * @var string
     */
    protected $_accountPassIV = '';
    /**
     * Nombre de la categoría.
     *
     * @var string
     */
    protected $_categoryName = '';
    /**
     * Nombre del cliente.
     *
     * @var string
     */
    protected $_customerName = '';
    /**
     * Descrición de la categoría.
     *
     * @var string
     */
    protected $_categoryDescription = '';
    /**
     * Descripción del cliente.
     *
     * @var string
     */
    protected $_customerDescription = '';
    /**
     * @var \SimpleXMLElement
     */
    protected $_xml;
    /**
     * @var FileImport
     */
    protected $_file;

    /**
     * Constructor
     *
     * @param $file FileImport Instancia de la clase FileImport
     * @throws SPException
     */
    public function __construct($file)
    {
        try {
            $this->_file = $file;
            $this->readXMLFile();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Leer el archivo a un objeto XML.
     *
     * @throws SPException
     * @return \SimpleXMLElement Con los datos del archivo XML
     */
    protected function readXMLFile()
    {
        $this->_xml = simplexml_load_file($this->_file->getTmpFile());

        if ($this->_xml === false) {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Error interno'),
                _('No es posible procesar el archivo XML')
            );
        }
    }

    /**
     * Detectar la aplicación que generó el XML.
     *
     * @throws SPException
     */
    public function detectXMLFormat()
    {
        if ($this->_xml->Meta->Generator == 'KeePass') {
            return 'keepass';
        } else if ($this->_xml->Meta->Generator == 'sysPass') {
            return 'syspass';
        } else if ($xmlApp = $this->parseFileHeader()) {
            switch ($xmlApp) {
                case 'keepassx_database':
                    return 'keepassx';
                case 'revelationdata':
                    return 'revelation';
                default:
                    break;
            }
        } else {
            throw new SPException(
                SPException::SP_CRITICAL,
                _('Archivo XML no soportado'),
                _('No es posible detectar la aplicación que exportó los datos')
            );
        }

        return '';
    }

    /**
     * Leer la cabecera del archivo XML y obtener patrones de aplicaciones conocidas.
     *
     * @return bool
     */
    protected function parseFileHeader()
    {
        $handle = @fopen($this->_file->getTmpFile(), "r");
        $headersRegex = '/(KEEPASSX_DATABASE|revelationdata)/i';

        if ($handle) {
            // No. de líneas a leer como máximo
            $maxLines = 5;
            $count = 0;

            while (($buffer = fgets($handle, 4096)) !== false && $count <= $maxLines) {
                if (preg_match($headersRegex, $buffer, $app)) {
                    fclose($handle);
                    return strtolower($app[0]);
                }
                $count++;
            }

            fclose($handle);
        }

        return false;
    }

    /**
     * Iniciar la importación desde XML.
     *
     * @throws SPException
     * @return bool
     */
    public abstract function doImport();

    /**
     * Añadir una cuenta desde XML.
     *
     * @return bool
     */
    protected function addAccount()
    {
        if (is_null($this->getUserId()) || $this->getUserId() === 0) {
            $this->setUserId(Session::getUserId());
        }

        if (is_null($this->getUserGroupId()) || $this->getUserGroupId() === 0) {
            $this->setUserGroupId(Session::getUserGroupId());
        }

        $account = new Account;
        $account->setAccountName($this->getAccountName());
        $account->setAccountCustomerId($this->getCustomerId());
        $account->setAccountCategoryId($this->getCategoryId());
        $account->setAccountLogin($this->getAccountLogin());
        $account->setAccountUrl($this->getAccountUrl());
        $account->setAccountPass($this->getAccountPass());
        $account->setAccountIV($this->getAccountPassIV());
        $account->setAccountNotes($this->getAccountNotes());
        $account->setAccountUserId($this->getUserId());
        $account->setAccountUserGroupId($this->getUserGroupId());

        return $account->createAccount();
    }

    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }

    /**
     * @return int
     */
    public function getUserGroupId()
    {
        return $this->userGroupId;
    }

    /**
     * @param int $userGroupId
     */
    public function setUserGroupId($userGroupId)
    {
        $this->userGroupId = $userGroupId;
    }

    /**
     * @return string
     */
    public function getAccountName()
    {
        return $this->_accountName;
    }

    /**
     * @param string $_accountName
     */
    public function setAccountName($_accountName)
    {
        $this->_accountName = $_accountName;
    }

    /**
     * @return int
     */
    public function getCustomerId()
    {
        return $this->_customerId;
    }

    /**
     * @param int $_customerId
     */
    public function setCustomerId($_customerId)
    {
        $this->_customerId = $_customerId;
    }

    /**
     * @return int
     */
    public function getCategoryId()
    {
        return $this->_categoryId;
    }

    /**
     * @param int $_categoryId
     */
    public function setCategoryId($_categoryId)
    {
        $this->_categoryId = $_categoryId;
    }

    /**
     * @return string
     */
    public function getAccountLogin()
    {
        return $this->_accountLogin;
    }

    /**
     * @param string $_accountLogin
     */
    public function setAccountLogin($_accountLogin)
    {
        $this->_accountLogin = $_accountLogin;
    }

    /**
     * @return string
     */
    public function getAccountUrl()
    {
        return $this->_accountUrl;
    }

    /**
     * @param string $_accountUrl
     */
    public function setAccountUrl($_accountUrl)
    {
        $this->_accountUrl = $_accountUrl;
    }

    /**
     * @return string
     */
    public function getAccountPass()
    {
        return $this->_accountPass;
    }

    /**
     * @param string $_accountPass
     */
    public function setAccountPass($_accountPass)
    {
        $this->_accountPass = $_accountPass;
    }

    /**
     * @return string
     */
    public function getAccountPassIV()
    {
        return $this->_accountPassIV;
    }

    /**
     * @param string $_accountPassIV
     */
    public function setAccountPassIV($_accountPassIV)
    {
        $this->_accountPassIV = $_accountPassIV;
    }

    /**
     * @return string
     */
    public function getAccountNotes()
    {
        return $this->_accountNotes;
    }

    /**
     * @param string $_accountNotes
     */
    public function setAccountNotes($_accountNotes)
    {
        $this->_accountNotes = $_accountNotes;
    }

    /**
     * Añadir una categoría y devolver el Id
     * @return int
     */
    protected function addCategory()
    {
        return Category::addCategoryReturnId($this->getCategoryName(), $this->getCategoryDescription());
    }

    /**
     * @return string
     */
    public function getCategoryName()
    {
        return $this->_categoryName;
    }

    /**
     * @param string $_categoryName
     */
    public function setCategoryName($_categoryName)
    {
        $this->_categoryName = $_categoryName;
    }

    /**
     * @return string
     */
    public function getCategoryDescription()
    {
        return $this->_categoryDescription;
    }

    /**
     * @param string $categoryDescription
     */
    public function setCategoryDescription($categoryDescription)
    {
        $this->_categoryDescription = $categoryDescription;
    }

    /**
     * Añadir un cliente y devolver el Id
     * @return int
     */
    protected function addCustomer()
    {
        return Customer::addCustomerReturnId($this->getCustomerName(), $this->getCustomerDescription());
    }

    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->_customerName;
    }

    /**
     * @param string $_customerName
     */
    public function setCustomerName($_customerName)
    {
        $this->_customerName = $_customerName;
    }

    /**
     * @return string
     */
    public function getCustomerDescription()
    {
        return $this->_customerDescription;
    }

    /**
     * @param string $customerDescription
     */
    public function setCustomerDescription($customerDescription)
    {
        $this->_customerDescription = $customerDescription;
    }
}