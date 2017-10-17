<?php

namespace SP\Mvc\Controller;

/**
 * Interface CrudControllerInterface
 *
 * @package SP\Mvc\Controller
 */
interface CrudControllerInterface
{

    /**
     * Create action
     */
    public function createAction();

    /**
     * Edit action
     *
     * @param $id
     */
    public function editAction($id);

    /**
     * Save or modify action
     *
     * @param $id
     */
    public function saveAction($id);

    /**
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id);
}