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

namespace SP\Domain\Plugin\Services;

use Defuse\Crypto\Exception\CryptoException;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Ports\Plugin;
use SP\Domain\Plugin\Ports\PluginCompatilityService;
use SP\Domain\Plugin\Ports\PluginLoaderService;
use SP\Domain\Plugin\Ports\PluginOperationInterface;
use SP\Infrastructure\Common\Repositories\NoSuchItemException;

/**
 * Class PluginBase
 */
abstract class PluginBase implements Plugin
{
    protected ?string $base = null;
    protected ?string $themeDir = null;
    protected mixed   $data = null;

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    public function __construct(
        protected readonly PluginOperationInterface $pluginOperation,
        private readonly PluginCompatilityService $pluginCompatilityService,
        private readonly PluginLoaderService      $pluginLoaderService
    ) {
        $this->load();
    }

    /**
     * @throws ConstraintException
     * @throws NoSuchItemException
     * @throws QueryException
     */
    private function load(): void
    {
        if ($this->pluginCompatilityService->checkFor($this)) {
            $this->pluginLoaderService->loadFor($this);
        }
    }

    public function getThemeDir(): ?string
    {
        return $this->themeDir;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    /**
     * @param int $id
     * @param mixed $data
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    final public function saveData(int $id, mixed $data): void
    {
        if ($this->data === null) {
            $this->pluginOperation->create($id, $data);
        } else {
            $this->pluginOperation->update($id, $data);
        }

        $this->data = $data;
    }

    protected function setLocales(): void
    {
        $locales = sprintf('%s%slocales', $this->getBase(), DIRECTORY_SEPARATOR);
        $name = strtolower($this->getName());

        bindtextdomain($name, $locales);
        bind_textdomain_codeset($name, 'UTF-8');
    }

    public function getBase(): ?string
    {
        return $this->base;
    }
}
