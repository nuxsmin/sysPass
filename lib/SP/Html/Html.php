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

namespace SP\Html;

defined('APP_ROOT') || die();

/**
 * Esta clase es la encargada de mostrar el HTML
 */
final class Html
{
    /**
     * Limpia los datos recibidos de un formulario.
     *
     * @param string $data con los datos a limpiar
     *
     * @return false|string con los datos limpiados
     */
    public static function sanitize(&$data)
    {
        if (empty($data)) {
            return $data;
        }

        if (is_array($data)) {
            array_walk_recursive($data, '\SP\Html\Html::sanitize');
        } else {
            $data = strip_tags($data);

            // Fix &entity\n;
            $data = str_replace(['&amp;', '&lt;', '&gt;'], ['&amp;amp;', '&amp;lt;', '&amp;gt;'], $data);
            $data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
            $data = preg_replace(/** @lang RegExp */
                '/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');

            // Remove any attribute starting with "on" or xmlns
            $data = preg_replace(/** @lang RegExp */
                '#(<[^>]+?[\x00-\x20\x2f"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);

            // Remove javascript: and vbscript: protocols
            $data = preg_replace(/** @lang RegExp */
                '#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
            $data = preg_replace(/** @lang RegExp */
                '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
            $data = preg_replace(/** @lang RegExp */
                '#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);

            // Only works in IE: <span style="width: expression(alert('Ping!'));"></span>
            $data = preg_replace(/** @lang RegExp */
                '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace(/** @lang RegExp */
                '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
            $data = preg_replace(/** @lang RegExp */
                '#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);

            // Remove namespaced elements (we do not need them)
            $data = preg_replace(/** @lang RegExp */
                '#</*\w+:\w[^>]*+>#i', '', $data);

            do {
                // Remove really unwanted tags
                $old_data = $data;
                $data = preg_replace(/** @lang RegExp */
                    '#</*(?:applet|b(?:ase|gsound|link)|embed|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
            } while ($old_data !== $data);
        }

        return $data;
    }

    /**
     * Truncar un texto a una determinada longitud.
     *
     * @param string $text  la cadena a truncar
     * @param int    $limit la longitud máxima de la cadena
     * @param string $ellipsis
     *
     * @return string con el texto truncado
     *
     * @link http://www.pjgalbraith.com/truncating-text-html-with-php/
     */
    public static function truncate($text, $limit, $ellipsis = '...')
    {
        if (mb_strlen($text) > $limit) {
            return trim(mb_substr($text, 0, $limit)) . $ellipsis;
        }

        return $text;
    }

    /**
     * Convertir un color RGB a HEX
     * From: http://bavotasan.com/2011/convert-hex-color-to-rgb-using-php/
     *
     * @param array $rgb con color en RGB
     *
     * @return string
     */
    public static function rgb2hex(array $rgb)
    {
        $hex = "#";

        foreach ($rgb as $val) {
            $hex .= str_pad(dechex($val), 2, "0", STR_PAD_LEFT);
        }

        return $hex;
    }

    /**
     * Devolver una cadena con el tag HTML strong.
     *
     * @param string $text con la cadena de texto
     *
     * @return string
     */
    public static function strongText($text)
    {
        return '<strong>' . $text . '</strong>';
    }

    /**
     * Devolver un link HTML.
     *
     * @param string $text    con la cadena de texto
     * @param string $link    con el destino del enlace
     * @param string $title   con el título del enlace
     * @param string $attribs con atributos del enlace
     *
     * @return string
     */
    public static function anchorText($text, $link = null, $title = null, $attribs = '')
    {
        $alink = $link !== null ? $link : $text;
        $atitle = $title !== null ? $title : $text;

        return sprintf('<a href="%s" title="%s" %s>%s</a>', $alink, $atitle, $attribs, $text);
    }

    /**
     * Strips out HTML tags preserving some spaces
     *
     * @param $text
     *
     * @return string
     */
    public static function stripTags(string $text): string
    {
        if (empty($text)) {
            return $text;
        }

        // Replace tags, then new lines, tabs and return chars, and then 2 or more spaces
        return trim(preg_replace(['/<[^>]*>/', '/[\n\t\r]+/', '/\s{2,}/'], ' ', $text));
    }
}
