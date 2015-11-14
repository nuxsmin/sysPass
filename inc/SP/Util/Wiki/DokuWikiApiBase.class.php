<?php
/**
 * sysPass
 *
 * @author    nuxsmin
 * @link      http://syspass.org
 * @copyright 2012-2015 Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Util\Wiki;

use DOMDocument;
use DOMException;
use SP\Config\Config;
use SP\Core\Session;
use SP\Core\SPException;
use SP\Http\XMLRPCResponseParse;
use SP\Log\Log;
use SP\Log\LogLevel;
use SP\Util\Util;

/**
 * Class DokuWikiApiBase
 *
 * @package SP\Util\Wiki
 */
abstract class DokuWikiApiBase
{
    /**
     * @var string
     */
    protected $_apiUser = '';
    /**
     * @var string
     */
    protected $_apiPassword = '';
    /**
     * @var string
     */
    protected $_apiUrl = '';
    /**
     * @var DOMDocument
     */
    private $_xml;
    /**
     * @var \DOMElement
     */
    private $_root;
    /**
     * @var \DOMElement
     */
    private $_params;

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->_xml->saveXML();
    }

    /**
     * Establecer la autorización
     *
     * @return bool|string
     * @throws SPException
     */
    protected function doLogin()
    {
        try {
            $this->createMsg('dokuwiki.login');
            $this->addParam($this->_apiUser);
            $this->addParam($this->_apiPassword);
            return $this->callWiki();
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Crear la llamada al método de DokuWiki
     *
     * @param $function
     * @throws SPException
     */
    protected function createMsg($function)
    {
        try {
            $this->_xml = new DOMDocument('1.0', 'UTF-8');

            $xmlMethodCall = $this->_xml->createElement('methodCall');
            $this->_root = $this->_xml->appendChild($xmlMethodCall);

            $xmlMethodName = $this->_xml->createElement('methodName', $function);
            $this->_root->appendChild($xmlMethodName);

            $this->_params = $this->_xml->createElement('params');
            $this->_root->appendChild($this->_params);
        } catch (DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Añadir un parámetro
     *
     * @param $value
     * @throws SPException
     */
    protected function addParam($value)
    {
        try {
            $xmlParam = $this->_xml->createElement('param');
            $xmlValue = $this->_xml->createElement('value');

            if (is_numeric($value)) {
                $xmlValue->appendChild($this->_xml->createElement('int', intval($value)));
            } elseif (is_string($value)) {
                $xmlValue->appendChild($this->_xml->createElement('string', $value));
            } elseif (is_bool($value)) {
                $xmlValue->appendChild($this->_xml->createElement('boolean', intval($value)));
            }

            $xmlParam->appendChild($xmlValue);
            $this->_params->appendChild($xmlParam);
        } catch (DOMException $e) {
            throw new SPException(SPException::SP_WARNING, $e->getMessage(), __FUNCTION__);
        }
    }

    /**
     * Enviar el XML a la wiki y devolver la respuesta
     */
    protected function callWiki()
    {
        try {
            $data['type'] = array('Content-Type: text/xml');
            $data['data'] = $this->_xml->saveXML();

            return Util::getDataFromUrl($this->_apiUrl, $data, true);
        } catch (SPException $e) {
            throw $e;
        }
    }

    /**
     * Capturar si han habido errores en la consulta XML
     *
     * @param XMLRPCResponseParse $Res
     * @throws SPException
     */
    protected function catchError(XMLRPCResponseParse $Res)
    {
        $error = $Res->getError();

        if (count($error) > 0) {
            throw new SPException(
                SPException::SP_WARNING,
                _('Error al realizar la consulta'),
                $error['faultString']
            );
        }
    }

    /**
     * Escribir el error en el registro de eventos
     *
     * @param SPException $e
     */
    protected function logException(SPException $e)
    {
        $Log = new Log('DokuWiki API', $e->getMessage(), LogLevel::ERROR);

        if ($e->getHint()) {
            $Log->addDetails('Error', $e->getHint());
        }

        $Log->writeLog();
    }

    /**
     * Establecer los datos de conexión a la API de DokuWiki
     *
     * @param string $url La URL de conexión
     * @param string $user El usuario de conexión
     * @param string $pass La clave de conexión
     * @throws SPException
     */
    protected function setConnectionData($url, $user, $pass)
    {
        $this->_apiUrl = (empty($url)) ? Config::getValue('dokuwiki_url') : $url;
        $this->_apiUser = (empty($user)) ? Config::getValue('dokuwiki_user') : $user;
        $this->_apiPassword = (empty($pass)) ? Config::getValue('dokuwiki_pass') : $pass;

        if (empty($this->_apiUrl)){
            throw new SPException(SPException::SP_WARNING, _('URL de conexión no establecida'));
        }
    }
}