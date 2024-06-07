<?php

declare(strict_types=1);
/**
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

namespace SP\Domain\Common\Providers;

use GdImage;
use SP\Domain\Core\Exceptions\InvalidImageException;
use SP\Domain\Core\Exceptions\SPException;
use SP\Domain\Core\PhpExtensionCheckerService;
use SP\Domain\Image\Ports\ImageService;
use SP\Infrastructure\File\FileHandler;

use function SP\__u;
use function SP\processException;

/**
 * Class Image
 */
final class Image implements ImageService
{
    private const IMAGE_WIDTH = 48;
    private const IMAGE_FONT  = PUBLIC_PATH . '/vendor/fonts/NotoSans-Regular-webfont.ttf';
    private const TMP_PREFFIX = 'syspass';

    public function __construct(PhpExtensionCheckerService $checker, private readonly string $font = self::IMAGE_FONT)
    {
        $checker->checkCurl(true);
    }

    /**
     * @inheritDoc
     * @throws InvalidImageException
     * @throws SPException
     */
    public function createThumbnail(string $image): string
    {
        $im = @imagecreatefromstring($image);

        if ($im === false) {
            throw InvalidImageException::error(__u('Invalid image'));
        }

        $width = imagesx($im) ?: self::IMAGE_WIDTH;
        $height = imagesy($im) ?: self::IMAGE_WIDTH;

        $newHeight = (int)floor($height * (self::IMAGE_WIDTH / $width));

        if (($tempImage = imagecreatetruecolor(self::IMAGE_WIDTH, $newHeight)) === false
            || !imagecopyresized($tempImage, $im, 0, 0, 0, 0, self::IMAGE_WIDTH, $newHeight, $width, $height)
        ) {
            throw SPException::error(__u('Unable to create image'));
        }

        return $this->createPngImage($tempImage);
    }

    /**
     * @throws SPException
     */
    private function createPngImage(GdImage $gdImage): string
    {
        if (($tmpFile = tempnam(TMP_PATH, self::TMP_PREFFIX)) !== false
            && imagepng($gdImage, $tmpFile)
        ) {
            $file = new FileHandler($tmpFile);
            $out = base64_encode($file->readToString());
            $file->delete();

            return $out;
        }

        throw SPException::error(__u('Unable to create image'));
    }

    /**
     * @inheritDoc
     */
    public function convertText(string $text): false|string
    {
        try {
            $width = strlen($text) * 10;

            if (($im = @imagecreatetruecolor($width, 30)) === false
                || ($bgColor = imagecolorallocate($im, 245, 245, 245)) === false
                || ($fgColor = imagecolorallocate($im, 128, 128, 128)) === false
                || !imagefilledrectangle($im, 0, 0, $width, 30, $bgColor) ||
                !imagefttext($im, 10, 0, 10, 20, $fgColor, $this->font, $text)
            ) {
                throw SPException::error(__u('Unable to create image'));
            }

            return $this->createPngImage($im);
        } catch (SPException $e) {
            processException($e);

            return false;
        }
    }
}
