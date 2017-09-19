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

namespace SP\Util;

use SP\Core\Init;
use SP\Log\LogUtil;

defined('APP_ROOT') || die();

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
     * @throws \SP\Core\Exceptions\SPException
     */
    public static function convertText($text)
    {
        if (!Checks::gdIsAvailable()) {
            LogUtil::extensionNotLoaded('GD');

            return false;
        }

        $width = strlen($text) * 10;

        $im = @imagecreatetruecolor($width, 30);

        if ($im === false) {
            return false;
        }

        // Colores de la imagen
        $bgColor = imagecolorallocate($im, 245, 245, 245);
//        $shadowColor = imagecolorallocate($im, 128, 128, 128);
        $fgColor = imagecolorallocate($im, 128, 128, 128);

        imagefilledrectangle($im, 0, 0, $width, 30, $bgColor);

        // Ruta de la fuente
        $font = Init::$SERVERROOT . '/css/fonts/NotoSans-Regular-webfont.ttf';

        // Sombra
//        imagettftext($im, 14, 0, 13, 23, $shadowColor, $font, $text);

        // Crear el texto
        imagettftext($im, 10, 0, 10, 20, $fgColor, $font, $text);

        // Devolver la imagen
        ob_start();
        imagepng($im);
        $image = ob_get_contents();
        ob_end_clean();

        imagedestroy($im);

        return base64_encode($image);
    }

    /**
     * Crear miniatura de una imagen
     *
     * @param $image string La imagen a redimensionar
     * @return bool|string
     */
    public static function createThumbnail($image)
    {
        if (!Checks::gdIsAvailable()) {
            LogUtil::extensionNotLoaded('GD', __FUNCTION__);

            return false;
        }

        $im = imagecreatefromstring($image);

        $width = imagesx($im);
        $height = imagesy($im);

        // Calcular el tamaño de la miniatura
        $new_width = 48;
        $new_height = floor($height * ($new_width / $width));

        // Crear nueva imagen
        $imTmp = imagecreatetruecolor($new_width, $new_height);

        // Redimensionar la imagen
        imagecopyresized($imTmp, $im, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Devolver la imagen
        ob_start();
        imagepng($imTmp);
        $thumbnail = ob_get_contents();
        ob_end_clean();

        imagedestroy($imTmp);
        imagedestroy($im);

        return base64_encode($thumbnail);
    }
}