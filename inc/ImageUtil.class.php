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

defined('APP_ROOT') || die(_('No es posible acceder directamente a este archivo'));

/**
 * Class ImageUtil para la manipulación de imágenes
 *
 * @package SP
 */
class ImageUtil
{
    /**
     * Convertir un texto a imagen
     *
     * @param $text string El texto a convertir
     * @return bool|string
     */
    public static function convertText($text)
    {
        if(!function_exists('imagepng')){
            return false;
        }

        $im = imagecreatetruecolor(strlen($text) * 20, 30);

        // Colores de la imagen
        $bgColor = imagecolorallocate($im, 255, 255, 255);
//        $shadowColor = imagecolorallocate($im, 128, 128, 128);
        $fgColor = imagecolorallocate($im, 128, 128, 128);

        imagefilledrectangle($im, 0, 0, strlen($text) * 20, 29, $bgColor);

        // Ruta de la fuente
        $font = Init::$SERVERROOT . '/imgs/NotoSansUI-Regular.ttf';

        // Sombra
//        imagettftext($im, 14, 0, 13, 23, $shadowColor, $font, $text);

        // Crear el texto
        imagettftext($im, 12, 0, 10, 20, $fgColor, $font, $text);

        // Guardar la imagen
        ob_start();
        imagepng($im);
        $image = ob_get_contents();
        ob_end_clean();

        imagedestroy($im);

        return base64_encode($image);
    }
}