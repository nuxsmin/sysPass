<?php
/**
 * Created by PhpStorm.
 * User: Rubén Domínguez
 * Date: 16/05/15
 * Time: 11:24
 */

/**
 * Clase Template para la manipulación de plantillas
 *
 * Motor de plantillas muy básico...
 *
 * Idea original de http://www.sitepoint.com/author/agervasio/
 * publicada en http://www.sitepoint.com/flexible-view-manipulation-1/
 * y modificada por Rubén Domínguez
 */
class Template {
    /**
     * @var array Variable con los archivos de plantilla a cargar
     */
    private $file = array();
    /**
     * @var array Variable con las variables a incluir en la plantilla
     */
    private $vars = array();

    /**
     * @param null $file Archivo de plantilla a añadir
     * @param array $vars Variables a inicializar
     */
    public function __construct($file = null, array $vars = array()) {
        $this->addTemplate($file);

        if(!empty($vars)){
            $this->setVars($vars);
        }
    }

    /**
     * Overloading para añadir nuevas variables en al array de variables dela plantilla
     * pasadas como atributos dinámicos de la clase
     *
     * @param string $name Nombre del atributo
     * @param string $value Valor del atributo
     * @return null
     */
    public function __set($name, $value) {
        $this->vars[$name] = $value;
        return null;
    }

    /**
     * Overloadig para controlar la devolución de atributos dinámicos.
     *
     * @param string $name Nombre del atributo
     * @return null
     * @throws InvalidArgumentException
     */
    public function __get($name) {
        if (!isset($this->vars[$name])) {
            throw new InvalidArgumentException('No es posible obtener la variable "' . $name . '"');
        }
        return null;
    }

    /**
     * Overloading para comprobar si el atributo solicitado está declarado como variable
     * en el array de variables de la plantilla.
     *
     * @param string $name Nombre del atributo
     * @return bool
     */
    public function __isset($name) {
        return isset($this->vars[$name]);
    }

    /**
     * Overloading para eliminar una variable del array de variables de la plantilla pasado como
     * atributo dinámico de la clase
     *
     * @param string $name Nombre del atributo
     * @return $this
     * @throws InvalidArgumentException
     */
    public function __unset($name) {
        if (!isset($this->vars[$name])) {
            throw new InvalidArgumentException('No es posible destruir la variable "' . $name . '"');
        }

        unset($this->vars[$name]);
        return $this;
    }

    /**
     * Mostrar la plantilla solicitada.
     * La salida se almacena en buffer y se devuelve el contenido
     *
     * @return string Con el contenido del buffer de salida
     */
    public function render() {
        extract($this->vars);

        ob_start();

        foreach ( $this->file as $template) {
            include $template;
        }

        return ob_get_clean();
    }

    /**
     * Comprobar si un archivo de plantilla existe y se puede leer
     *
     * @param string $file Con el nombre del archivo
     * @throws InvalidArgumentException
     */
    private function checkTemplate($file){
        $template = __DIR__ . DIRECTORY_SEPARATOR . 'tpl' . DIRECTORY_SEPARATOR . $file;

        if (!is_file($template) || !is_readable($template)) {
            throw new InvalidArgumentException('No es posible obtener la plantilla "' . $file .'"');
        }

        $this->setTemplate($template);
    }

    /**
     * Añadir un nuevo archivo de plantilla al array de plantillas de la clase.
     *
     * @param string $file Con el nombre del archivo
     */
    private function setTemplate($file){
        $this->file[] = $file;
    }

    /**
     * Establecer los atributos de la clase a partir de un array.
     *
     * @param array $vars Con los atributos de la clase
     */
    private function setVars(&$vars){
        foreach ($vars as $name => $value) {
            $this->$name = $value;
        }
    }

    /**
     * Añadir una nueva plantilla al array de plantillas de la clase
     *
     * @param string $file Con el nombre del archivo de plantilla
     */
    public function addTemplate($file){
        if (!is_null($file) && $this->checkTemplate($file)){
            $this->setTemplate($file);
        }
    }
}