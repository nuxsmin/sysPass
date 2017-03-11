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

namespace SP\Mgmt;

use SP\Core\DiFactory;
use SP\Core\Exceptions\InvalidClassException;
use SP\Core\Exceptions\SPException;
use SP\DataModel\DataModelInterface;

/**
 * Class ItemBaseTrait
 *
 * @package SP\Mgmt
 */
trait ItemBaseTrait
{
    /**
     * @var string
     */
    protected $dataModel;
    /**
     * @var mixed|DataModelInterface
     */
    protected $itemData;

    /**
     * Constructor.
     *
     * @param null $itemData
     * @throws InvalidClassException
     */
    public function __construct($itemData = null)
    {
        $this->init();

        if (null !== $itemData) {
            $this->setItemData($itemData);
        } else {
            $this->itemData = new $this->dataModel();
        }
    }

    /**
     * Devolver la instancia almacenada de la clase. Si no existe, se crea
     *
     * @param $itemData
     * @return static
     */
    public final static function getItem($itemData = null)
    {
        return DiFactory::getItem(get_called_class(), $itemData);
    }

    /**
     * Devolver una nueva instancia de la clase
     *
     * @param null $itemData
     * @return static
     * @throws \SP\Core\Exceptions\InvalidClassException
     */
    public static final function getNewItem($itemData = null)
    {
        return new static($itemData);
    }

    /**
     * Devolver los datos del elemento
     *
     * @return mixed|DataModelInterface
     */
    public function getItemData()
    {
        return is_object($this->itemData) ? $this->itemData : new $this->dataModel();
    }

    /**
     * @param $itemData
     * @return $this
     * @throws InvalidClassException
     */
    public final function setItemData($itemData)
    {
        if (null !== $this->dataModel && ($itemData instanceof $this->dataModel) === false) {
            throw new InvalidClassException(SPException::SP_ERROR, $this->dataModel);
        }

        $this->itemData = $itemData;

        return $this;
    }

    /**
     * @return string
     */
    public function getDataModel()
    {
        return $this->dataModel;
    }

    /**
     * @param string $dataModel
     * @return static
     * @throws InvalidClassException
     */
    protected final function setDataModel($dataModel)
    {
        if (false === class_exists($dataModel)) {
            throw new InvalidClassException(SPException::SP_ERROR, $dataModel);
        }

        $this->dataModel = $dataModel;

        return $this;
    }

    /**
     * Inicializar la clase
     *
     * @return void
     */
    protected abstract function init();
}