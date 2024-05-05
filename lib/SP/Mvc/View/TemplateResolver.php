<?php
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

declare(strict_types=1);

namespace SP\Mvc\View;

use SP\Domain\Core\Exceptions\FileNotFoundException;
use SP\Domain\Core\UI\ThemeInterface;
use SP\Infrastructure\File\FileSystem;

use function SP\__;
use function SP\logger;

/**
 * Class TemplateResolver
 */
final readonly class TemplateResolver implements TemplateResolverInterface
{
    private const  TEMPLATE_EXTENSION = '.inc';

    public function __construct(private ThemeInterface $theme)
    {
    }

    /**
     * @throws FileNotFoundException
     */
    public function getTemplateFor(string $base, string $name): string
    {
        $template = FileSystem::buildPath(
            $this->theme->getViewsPath(),
            $base,
            $name . self::TEMPLATE_EXTENSION
        );

        if (!is_readable($template)) {
            $msg = sprintf(__('Unable to retrieve "%s" template: %s'), $template, $name);

            logger($msg);

            throw FileNotFoundException::warning($msg);
        }

        return $template;
    }
}
