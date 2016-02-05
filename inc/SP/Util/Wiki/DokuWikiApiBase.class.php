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
    protected $apiUser = '';
    /**
     * @var string
     */
    protected $apiPassword = '';
    /**
     * @var string
     */
    protected $apiUrl = '';
    /**
     * @var DOMDocument
     */
    private $xml;
    /**
     * @var \DOMElement
     */
    private $root;
    /**
     * @var \DOMElement
     */
    private $params;

    /**
     * @return string
     */
    public function getXml()
    {
        return $this->xml->saveXML();
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
            $this->addParam($this->apiUser);
            $this->addParam($this->apiPassword);
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
            $this->xml = new DOMDocument('1.0', 'UTF-8');

            $xmlMethodCall = $this->xml->createElement('methodCall');
            $this->root = $this->xml->appendChild($xmlMethodCall);

            $xmlMethodName = $this->xml->createElement('methodName', $function);
            $this->root->appendChild($xmlMethodName);

            $this->params = $this->xml->createElement('params');
            $this->root->appendChild($this->params);
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
            $xmlParam = $this->xml->createElement('param');
            $xmlValue = $this->xml->createElement('value');

            if (is_numeric($value)) {
                $xmlValue->appendChild($this->xml->createElement('int', intval($value)));
            } elseif (is_string($value)) {
                $xmlValue->appendChild($this->xml->createElement('string', $value));
            } elseif (is_bool($value)) {
                $xmlValue->appendChild($this->xml->createElement('boolean', intval($value)));
            }

            $xmlParam->appendChild($xmlValue);
            $this->params->appendChild($xmlParam);
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
            $data['data'] = $this->xml->saveXML();

            return Util::getDataFromUrl($this->apiUrl, $data, true);
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
        $this->apiUrl = (empty($url)) ? Config::getConfig()->getDokuwikiUrl() : $url;
        $this->apiUser = (empty($user)) ? Config::getConfig()->getDokuwikiUser() : $user;
        $this->apiPassword = (empty($pass)) ? Config::getConfig()->getDokuwikiPass() : $pass;

        if (empty($this->apiUrl)){
            throw new SPException(SPException::SP_WARNING, _('URL de conexión no establecida'));
        }
    }
}