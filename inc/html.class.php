<?php

/**
 * sysPass
 *
 * @author nuxsmin
 * @link http://syspass.org
 * @copyright 2012-2014 Rubén Domínguez nuxsmin@syspass.org
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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Esta clase es la encargada de mostrar el HTML
 */
class SP_Html
{
    public static $htmlBodyOpts = "";
    private static $htmlPage = array();

    /**
     * @brief Crear un elemento del tipo SELECT
     * @param array $arrValues con los valores del select
     * @param array $arrSelectProp con las propiedades del select
     * @param bool $useValue para usar el Id como valor
     * @return none
     *
     * Esta función genera un elemento SELECT con las propiedades y valores pasados.
     */
    public static function printSelect($arrValues, $arrSelectProp, $useValue = true)
    {

        if (!is_array($arrSelectProp)) {
            return;
        }

        $strAttrs = (is_array($arrSelectProp["attribs"])) ? implode(" ", $arrSelectProp["attribs"]) : "";
        $strClass = ($arrSelectProp["class"]) ? 'class="' . $arrSelectProp["class"] . '"' : "";

        if (!is_array($arrValues)) {
            echo '<label for=' . $arrSelectProp["id"] . '">' . $arrSelectProp["label"] . '</label>';
            echo '<select name="' . $arrSelectProp["name"] . '" id="' . $arrSelectProp["id"] . '" ' . $strClass . ' size="' . $arrSelectProp["size"] . '" ' . $arrSelectProp["js"] . ' ' . $strAttrs . ' >';
            echo '<option value="0">' . $arrSelectProp["default"] . '</option>';
            echo '</select>';
            return;
        }

        if ($arrSelectProp["label"]) {
            echo '<label for=' . $arrSelectProp["id"] . '">' . $arrSelectProp["label"] . '</label>';
        }

        echo '<select name="' . $arrSelectProp["name"] . '" id="' . $arrSelectProp["id"] . '" ' . $strClass . ' size="' . $arrSelectProp["size"] . '" ' . $arrSelectProp["js"] . ' ' . $strAttrs . ' >';
        echo '<option value="0">' . $arrSelectProp["default"] . '</option>';

        $selectedId = (isset($arrSelectProp["selected"])) ? $arrSelectProp["selected"] : "";

        foreach ($arrValues as $valueId => $valueName) {
            if ($useValue) {
                $selected = ($valueId == $selectedId) ? "SELECTED" : "";
                echo '<option value="' . $valueId . '" ' . $selected . '>' . $valueName . '</option>';
            } else {
                $selected = ($valueName == $selectedId) ? "SELECTED" : "";
                echo '<option ' . $selected . '>' . $valueName . '</option>';
            }
        }

        echo '</select>';
    }

    /**
     * @brief Mostrar la página HTML
     * @param string $page opcional con la página a mostar
     * @param array $err con los errores generados
     * @return none
     *
     * Esta función es la encargada de devolver el código HTML al navegador.
     */
    public static function render($page = "main", $err = NULL)
    {
        $data['showlogo'] = 1;

        // UTF8 Headers
        header("Content-Type: text/html; charset=UTF-8");

        if (!is_null($err) && is_array($err) && count($err) > 0) {
            $data['errors'] = $err;
        }

        // Start the page
        self::$htmlPage[] = '<!DOCTYPE html>';
        self::$htmlPage[] = '<html lang="es">';

        self::makeHeader();
        self::makeBody($page);

        self::$htmlPage[] = '</html>';

        foreach (self::$htmlPage as $html) {
            if (is_array($html) && array_key_exists('include', $html)) {
                self::getTemplate($html['include'], $data);
            } else {
                echo $html . PHP_EOL;
            }
        }
    }

    /**
     * @brief Crear el header en HTML
     * @return none
     *
     * Esta función crea la cabecera de una página HTML
     */
    private static function makeHeader()
    {
        $info = self::getAppInfo();

        self::$htmlPage[] = '<head>';
        self::$htmlPage[] = '<title>' . $info['appname'] . ' :: ' . $info['appdesc'] . '</title>';
        self::$htmlPage[] = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        self::$htmlPage[] = '<link rel="icon" TYPE="image/png" href="' . SP_Init::$WEBROOT . '/imgs/logo.png">';
        self::setCss();
        self::setJs();
        self::$htmlPage[] = '</head>';
    }

    /**
     * @brief Devuelve información sobre la aplicación
     * @param string $index con la key a devolver
     * @return array con las propiedades de la aplicación
     */
    public static function getAppInfo($index = NULL)
    {
        $appinfo = array(
            'appname' => 'sysPass',
            'appdesc' => 'Sysadmin Password Manager',
            'appwebsite' => 'http://www.syspass.org',
            'appblog' => 'http://www.cygnux.org',
            'appdoc' => 'http://wiki.syspass.org',
            'appupdates' => 'http://sourceforge.net/api/file/index/project-id/775555/mtime/desc/limit/20/rss',
            'apphelp' => 'help.syspass.org',
            'appchangelog' => '');

        if (!is_null($index) && array_key_exists($index, $appinfo)) {
            return $appinfo[$index];
        }

        return $appinfo;
    }

    /**
     * @brief Establece los enlaces CSS de la página HTML
     * @return none
     */
    public static function setCss()
    {
        $versionParameter = '?v=' . md5(implode(SP_Util::getVersion()));

        $cssProp = array(
            array("href" => "css/reset.css", "media" => ""),
            array("href" => "css/smoothness/jquery-ui.css", "media" => "screen"),
            array("href" => "css/jquery.powertip.css", "media" => "screen"),
            array("href" => "css/jquery.powertip-yellow.min.css", "media" => "screen"),
            array("href" => "css/chosen.css", "media" => "screen"),
            array("href" => "css/alertify.core.css", "media" => "screen"),
            array("href" => "css/alertify.default.css", "media" => "screen"),
            array("href" => "css/jquery.tagsinput.css", "media" => "screen"),
            array("href" => "js/fancybox/jquery.fancybox.css", "media" => "screen"),
            array("href" => "css/styles.css", "media" => ""));

        foreach ($cssProp as $css) {
            self::$htmlPage[] = '<link rel="stylesheet" href="' . SP_Init::$WEBROOT . "/" . $css["href"] . $versionParameter . '" media="' . $css["media"] . '" />';
        }
    }

    /**
     * @brief Establece los enlaces JAVASCRIPT de la página HTML
     * @return none
     */
    public static function setJs()
    {
        $versionParameter = md5(implode(SP_Util::getVersion()));

        $js_files = self::getJs();

        foreach ($js_files as $js) {
            self::$htmlPage[] = '<script type="text/javascript" src="' . SP_Init::$WEBROOT . "/" . $js["src"] . '?v=' . $versionParameter . $js["params"] . '"></script>';
        }
    }

    /**
     * @brief Devuelve un array con los archivos JS a incluir
     * @return array con los archivos js y parámetros
     */
    public static function getJs()
    {

        $jsProp = array(
            array("src" => "js/jquery.js", "params" => ""),
            array("src" => "js/jquery.placeholder.js", "params" => ""),
            array("src" => "js/jquery-ui.js", "params" => ""),
            array("src" => "js/fancybox/jquery.fancybox.pack.js", "params" => ""),
            array("src" => "js/jquery.powertip.min.js", "params" => ""),
            array("src" => "js/chosen.jquery.min.js", "params" => ""),
            array("src" => "js/alertify.js", "params" => ""),
            array("src" => "js/jquery.fileDownload.js", "params" => ""),
            array("src" => "js/jquery.filedrop.js", "params" => ""),
            array("src" => "js/jquery.tagsinput.js", "params" => ""),
            array("src" => "js/functions.php", "params" => "&l=" . SP_Init::$LANG . "&r=" . urlencode(base64_encode(SP_Init::$WEBROOT)))
        );

        return $jsProp;
    }

    /**
     * @brief Crear el body en HTML
     * @param string $page con la página a cargar
     * @return none
     *
     * Esta función crea el cuerpo de una página HTML
     */
    private static function makeBody($page)
    {
        self::$htmlPage[] = '<body ' . self::$htmlBodyOpts . '>';
        self::$htmlPage[] = '<div id="wrap">';
        self::$htmlPage[] = '<noscript><div id="nojs">' . _('Javascript es necesario para el correcto funcionamiento') . '</div></noscript>';
        self::$htmlPage[] = '<div id="container" class="' . $page . '">';

        self::$htmlPage[] = array('include' => $page);

        self::$htmlPage[] = '</div> <!-- Close container -->';
        self::makeFooter($page);
        self::$htmlPage[] = '</div> <!-- Close wrap -->';
        self::$htmlPage[] = '</body>';
    }

    /**
     * @brief Crear el pie de la página HTML
     * @param string $page opcional con la paǵina a mostrar
     * @return none
     *
     * Esta función crea el pie de página en HTML
     */
    public static function makeFooter($page = "main")
    {
        $info = self::getAppInfo();

        self::$htmlPage[] = '<div id="footer">';
        self::$htmlPage[] = '<div id="updates"></div>';
        self::$htmlPage[] = '<div id="project">';
        self::$htmlPage[] = '<a href="' . $info['appwebsite'] . '" target="_blank" title="' . _('Ayuda :: FAQ :: Changelog') . '">' . $info['appname'] . ' ' . SP_Util::getVersionString() . '</a> ';
        self::$htmlPage[] = '&nbsp;::&nbsp;';
        self::$htmlPage[] = '<a href="' . $info['appblog'] . '" target="_blank" title="' . _('Un proyecto de cygnux.org') . '" >cygnux.org</a>';
        self::$htmlPage[] = '</div> <!-- Close Project -->';
        self::$htmlPage[] = '</div> <!-- Close footer -->';
        self::$htmlPage[] = '<script>$(\'input[type="text"], select, textarea\').placeholder().mouseenter(function(){ $(this).focus(); });</script>';
    }

    /**
     * @brief Cargar un archivo de plantilla
     * @param string $template con el nombre de la plantilla
     * @param array $tplvars con los datos a pasar a la plantilla
     * @return none
     */
    public static function getTemplate($template, $tplvars = array())
    {
        $tpl = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $template . '.php';

        if (file_exists($tpl)) {
            $data = $tplvars;
            include_once $tpl;
            //self::$htmlPage[] = array('include' => $tpl);
        }
    }

    /**
     * @brief Crea la barra de navegación para búsqueda de cuentas
     * @param int $intSortKey con el número de campo del filro
     * @param int $intCur con el número de página actual
     * @param int $intTotal con el número total de páginas
     * @param int $intLimit con el límite de registros a mostrar
     * @param int $intTime con el tiempo de carga de los resultados
     * @param bool $filterOn opcional con el estado del filtrado
     * @return none
     */
    public static function printQuerySearchNavBar($intSortKey, $intCur, $intTotal, $intLimit, $intTime, $filterOn = false)
    {
        $firstPage = ceil(($intCur + 1) / $intLimit);
        $lastPage = ceil($intTotal / $intLimit);
        $globalOn = SP_Common::parseParams('p', 'gsearch', 0, false, 1);

        echo '<div id="pageNav" class="round shadow">';
        echo '<div id="pageNavLeft">';
        echo $intTotal . ' @ ' . abs($intTime) . ' s ';
        echo ($filterOn) ? '<span class="filterOn round">' . _('Filtro ON') . '</span>' : '';
        echo '&nbsp;';
        echo ($globalOn) ? '<span class="globalOn round">' . _('Global ON') . '</span>' : '';
        echo '</div>';
        echo '<div id="pageNavRight">';

        if ($intCur > 1) {
            echo '<img src="imgs/arrow_first.png" onClick="searchSort(' . $intSortKey . ',0,1);" title="' . _('Primera página') . '" />';
            echo '<img src="imgs/arrow_left.png" onClick="searchSort(' . $intSortKey . ',' . ($intCur - $intLimit) . ',1);" title="' . _('Página anterior') . '" />';
        }

        echo "&nbsp; $firstPage / $lastPage &nbsp;";

        if ($intCur < $intTotal && $firstPage != $lastPage) {
            $intLimitLast = (($intTotal % $intLimit) == 0) ? $intTotal - $intLimit : floor($intTotal / $intLimit) * $intLimit;
            echo '<img src="imgs/arrow_right.png" onClick="searchSort(' . $intSortKey . ',' . ($intCur + $intLimit) . ',1);" title="' . _('Página siguiente') . '" />';
            echo '<img src="imgs/arrow_last.png" onClick="searchSort(' . $intSortKey . ',' . $intLimitLast . ',1);" title="' . _('Última página') . '" />';
        }

        echo '</div></div>';
    }

    /**
     * @brief Crea la barra de navegación para el registro de eventos
     * @param int $intCur con el número de página actual
     * @param int $intTotal con el número total de páginas
     * @param int $intTime con el tiempo de carga de los resultados
     * @return none
     */
    public static function printQueryLogNavBar($intCur, $intTotal, $intTime = 0)
    {
        $intLimit = 50;
        $firstPage = ceil(($intCur + 1) / $intLimit);
        $lastPage = ceil($intTotal / $intLimit);

        echo '<div id="pageNav" class="round shadow">';
        echo '<div id="pageNavLeft">' . $intTotal . ' @ ' . $intTime . ' s</div>';
        echo '<div id="pageNavRight">';

        if ($intCur > 1) {
            echo '<img src="imgs/arrow_first.png" onClick="navLog(0,' . $intCur . ');" title="' . _('Primera página') . '" />';
            echo '<img src="imgs/arrow_left.png" onClick="navLog(' . ($intCur - $intLimit) . ',' . $intCur . ');" title="' . _('Página anterior') . '" />';
        }

        echo "&nbsp; $firstPage / $lastPage &nbsp;";

        if ($intCur < $intTotal && $firstPage != $lastPage) {
            $intLimitLast = (($intTotal % $intLimit) == 0) ? $intTotal - $intLimit : floor($intTotal / $intLimit) * $intLimit;
            echo '<img src="imgs/arrow_right.png" onClick="navLog(' . ($intCur + $intLimit) . ',' . $intCur . ');" title="' . _('Página siguiente') . '" />';
            echo '<img src="imgs/arrow_last.png" onClick="navLog(' . $intLimitLast . ',' . $intCur . ');" title="' . _('Última página') . '" />';
        }

        echo '</div></div>';
    }

    /**
     * @brief Limpia los datos recibidos de un formulario
     * @param string $data con los datos a limpiar
     * @return string con los datos limpiados
     */
    public static function sanitize(&$data)
    {
        if (!$data) {
            return false;
        }

        if (is_array($data)) {
            array_walk_recursive($data, 'SP_Html::sanitize');
        } else {
            $data = strip_tags($data);

            // Fix &entity\n;
            $data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
            $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
            $data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

            // Remove any attribute starting with "on" or xmlns
            $data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

            // Remove javascript: and vbscript: protocols
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
            $data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

            // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

            // Remove namespaced elements (we do not need them)
            $data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);

            do {
                // Remove really unwanted tags
                $old_data = $data;
                $data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
            } while ($old_data !== $data);
        }
        return $data;
    }

    /**
     * @brief Muestra una barra de información con los registros y tiempo de la consulta
     * @param int $intTotal con el total de registros devueltos
     * @param int $startTime con el tiempo de inicio de la consulta
     * @return none
     */
    public static function printQueryInfoBar($intTotal, $startTime)
    {
        $endTime = microtime();
        $totalTime = round($endTime - $startTime, 5);

        echo '<div id="pageNav" class="round shadow">';
        echo '<div id="pageNavLeft">' . $intTotal . ' @ ' . $totalTime . ' s</div>';
        echo '</div>';
    }

    /**
     * @brief Truncar un texto a una determinada longitud
     * @param string $str con la cadena a truncar
     * @param int $len con la longitud máxima de la cadena
     * @return string con el texto truncado
     */
    public static function truncate($str, $len)
    {
        $tail = max(0, $len - 10);
        $truncate = substr($str, 0, $tail);
        $truncate .= strrev(preg_replace('~^..+?[\s,:]\b|^...~', '...', strrev(substr($str, $tail, $len - $tail))));

        return $truncate;
    }

    /**
     * @brief Devolver errores comunes
     * @param string $code con el código de error a mostrar
     * @return none
     *
     * Esta función muestra la página de error con el error indicado.
     */
    public static function showCommonError($code)
    {
        $commonErrors = array(
            'unavailable' => array('txt' => _('Opción no disponible'), 'hint' => _('Consulte con el administrador')),
            'noaccpermission' => array('txt' => _('No tiene permisos para acceder a esta cuenta'), 'hint' => _('Consulte con el administrador')),
            'nopermission' => array('txt' => _('No tiene permisos para acceder a esta página'), 'hint' => _('Consulte con el administrador')),
            'updatempass' => array('txt' => _('Clave maestra actualizada'), 'hint' => _('Reinicie la sesión para cambiarla'))
        );

        $data['errors'][] = array(
            'type' => 'critical',
            'description' => $commonErrors[$code]['txt'],
            'hint' => $commonErrors[$code]['hint']);

        self::getTemplate('error', $data);
        exit();
    }

    /**
     * @brief Convertir un color RGB a HEX
     * @param array $rgb con color en RGB
     * @return string
     *
     * From: http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
     */
    public static function rgb2hex($rgb)
    {
        $hex = "#";
        $hex .= str_pad(dechex($rgb[0]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[1]), 2, "0", STR_PAD_LEFT);
        $hex .= str_pad(dechex($rgb[2]), 2, "0", STR_PAD_LEFT);

        return $hex; // returns the hex value including the number sign (#)
    }

    /**
     * @brief Devolver una tabla con el resultado de una consulta y acciones
     * @param array $arrTableProp con las propiedades de la tabla
     * @param array $queryItems con los resultados de la consulta
     * @return none
     */
    public static function getQueryTable($arrTableProp, $queryItems)
    {
        $sk = SP_Common::getSessionKey(true);

        echo '<div class="action fullWidth">';
        echo '<ul>';
        echo '<LI><img src="imgs/add.png" title="' . _('Nuevo') . ' ' . $arrTableProp['itemName'] . '" class="inputImg" OnClick="' . $arrTableProp["actions"]['edit'] . '(0,' . $arrTableProp["newActionId"] . ',\'' . $sk . '\',' . $arrTableProp["activeTab"] . ',0);" /></LI>';
        echo '</ul>';
        echo '</div>';

        if ($arrTableProp["header"]) {
            echo '<div id="title" class="midroundup titleNormal">' . $arrTableProp["header"] . '</div>';
        }

        echo '<form name="' . $arrTableProp["frmId"] . '" id="' . $arrTableProp["frmId"] . '" OnSubmit="return false;" >';
        echo '<div id="' . $arrTableProp["tblId"] . '" class="data-header" >';
        echo '<ul class="round header-grey">';

        $cellWidth = floor(65 / count($arrTableProp["tblHeaders"]));

        foreach ($arrTableProp["tblHeaders"] as $header) {
            if (is_array($header)) {
                echo '<li class="' . $header['class'] . '" style="width: ' . $cellWidth . '%;">' . $header['name'] . '</li>';
            } else {
                echo '<li style="width: ' . $cellWidth . '%;">' . $header . '</li>';
            }
        }

        echo '</ul>';
        echo '</div>';

        echo '<div class="data-rows">';

        foreach ($queryItems as $item) {
            $intId = $item->$arrTableProp["tblRowSrcId"];
            $action_check = array();
            $numActions = count($arrTableProp["actions"]);
            $classActionsOptional = ($numActions > 2) ? 'actions-optional' : '';

            echo '<ul>';

            foreach ($arrTableProp["tblRowSrc"] as $rowSrc) {
                // If row is an array handle images in it
                if (is_array($rowSrc)) {
                    echo '<li class="cell-nodata" style="width: ' . $cellWidth . '%;">';
                    foreach ($rowSrc as $rowName => $imgProp) {
                        if ($item->$rowName) {
                            echo '<img src="imgs/' . $imgProp['img_file'] . '" title="' . $imgProp['img_title'] . '" />';
                            $action_check[$rowName] = 1;
                        }
                    }
                    echo '</li>';
                } else {
                    echo '<li class="cell-data" style="width: ' . $cellWidth . '%;">';
                    echo ($item->$rowSrc) ? $item->$rowSrc : '&nbsp;'; // Fix height
                    echo '</li>';
                }
            }

            echo '<li class="cell-actions round" style="width: ' . ($numActions * 5 + 2) . '%;">';
            //echo '<li class="cell-actions round" style="width: 175px;">';
            foreach ($arrTableProp["actions"] as $action => $function) {
                switch ($action) {
                    case "view":
                        echo '<img src="imgs/view.png" title="' . _('Ver Detalles') . '" class="inputImg" Onclick="return ' . $arrTableProp["actions"]['view'] . '(' . $intId . ',' . $arrTableProp["actionId"] . ',\'' . $sk . '\', ' . $arrTableProp["activeTab"] . ',1);" />';
                        break;
                    case "edit":
                        echo '<img src="imgs/edit.png" title="' . _('Editar') . ' ' . $arrTableProp['itemName'] . '" class="inputImg" Onclick="return ' . $arrTableProp["actions"]['edit'] . '(' . $intId . ',' . $arrTableProp["actionId"] . ',\'' . $sk . '\', ' . $arrTableProp["activeTab"] . ',0);" />';
                        break;
                    case "del":
                        echo '<img src="imgs/delete.png" title="' . _('Eliminar') . ' ' . $arrTableProp['itemName'] . '" class="inputImg ' . $classActionsOptional . '" Onclick="return ' . $arrTableProp["actions"]['del'] . '(' . $arrTableProp["activeTab"] . ',1,' . $intId . ',' . $arrTableProp["actionId"] . ',\'' . $sk . '\', \'' . $arrTableProp["onCloseAction"] . '\');" />';
                        break;
                    case "pass":
                        if (isset($action_check['user_isLdap'])) {
                            break;
                        }

                        echo '<img src="imgs/key.png" title="' . _('Cambiar clave') . '" class="inputImg ' . $classActionsOptional . '" Onclick="return ' . $arrTableProp["actions"]['pass'] . '(' . $intId . ');" />';
                        break;
                }
            }
            echo ($numActions > 2) ? '<img src="imgs/action.png" title="' . _('Más Acciones') . '" OnClick="showOptional(this)" />' : '';
            echo '</li>';
            echo '</ul>';
        }

        echo '</div></form>';
    }

    /**
     * @brief Devolver una cadena con el tag HTML strong
     * @param string $text con la cadena de texto
     * @return string
     */
    public static function strongText($text)
    {
        return ('<strong>' . $text . '</strong>');
    }

    /**
     * @brief Devolver un link HTML
     * @param string $text con la cadena de texto
     * @param string $link con el destino del enlace
     * @param string $title con el título del enlace
     * @param string $attribs con atributos del enlace
     * @return string
     */
    public static function anchorText($text, $link = '', $title = '', $attribs = '')
    {
        $alink = (!empty($link)) ? $link : $text;
        $atitle = (!empty($title)) ? $title : '';

        $anchor = '<a href="' . $alink . '" title="' . $atitle . '" ' . $attribs . '>' . $text . '</a>';

        return $anchor;
    }

    private static function minifier($files)
    {
        if (!is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            //$output_min .= file_get_contents($file['src']);
            include_once SP_Init::$SERVERROOT . DIRECTORY_SEPARATOR . $file['src'];
        }

        //return $output_min;
    }
}
