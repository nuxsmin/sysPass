<?php
/*
 * sysPass
 *
 * @author nuxsmin
 * @link https://syspass.org
 * @copyright 2012-2021, Rubén Domínguez nuxsmin@$syspass.org
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

namespace SP\Plugin;

use Defuse\Crypto\Exception\CryptoException;
use Psr\Container\ContainerInterface;
use SP\Domain\Common\Services\ServiceException;
use SP\Domain\Core\Exceptions\ConstraintException;
use SP\Domain\Core\Exceptions\NoSuchPropertyException;
use SP\Domain\Core\Exceptions\QueryException;
use SP\Domain\Plugin\Services\PluginService;

/**
 * Class PluginBase
 *
 * @package SP\Plugin
 */
abstract class PluginBase implements PluginInterface
{
    /**
     * @var string|null Directorio base
     */
    protected ?string $base = null;
    /**
     * @var string|null Tipo de plugin
     */
    protected ?string $type = null;
    protected ?string $themeDir = null;
    /**
     * @var mixed
     */
    protected $data;
    protected ?int $enabled;
    protected PluginOperation $pluginOperation;
    private PluginService $pluginService;

    /**
     * PluginBase constructor.
     *
     * @param ContainerInterface $dic
     * @param PluginOperation    $pluginOperation
     */
    final public function __construct(
        ContainerInterface $dic,
        PluginOperation    $pluginOperation
    )
    {
        /** @noinspection UnusedConstructorDependenciesInspection */
        $this->pluginService = $dic->get(PluginService::class);
        $this->pluginOperation = $pluginOperation;
        $this->init($dic);
    }

    /**
     * @return string
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getThemeDir(): ?string
    {
        return $this->themeDir;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getEnabled(): ?int
    {
        return $this->enabled;
    }

    /**
     * @param int $enabled
     */
    public function setEnabled(int $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @param int   $id
     * @param mixed $data
     *
     * @throws CryptoException
     * @throws ConstraintException
     * @throws NoSuchPropertyException
     * @throws QueryException
     * @throws ServiceException
     */
    final public function saveData(int $id, $data): void
    {
        if ($this->data === null) {
            $this->pluginOperation->create($id, $data);
        } else {
            $this->pluginOperation->update($id, $data);
        }

        $this->data = $data;
    }

    /**
     * Establecer las locales del plugin
     */
    protected function setLocales(): void
    {
        $locales = $this->getBase() . DIRECTORY_SEPARATOR . 'locales';
        $name = strtolower($this->getName());

        bindtextdomain($name, $locales);
        bind_textdomain_codeset($name, 'UTF-8');
    }

    /**
     * @return string
     */
    public function getBase(): ?string
    {
        return $this->base;
    }
}
