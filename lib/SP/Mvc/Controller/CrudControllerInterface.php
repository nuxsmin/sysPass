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
     * View action
     *
     * @param $id
     */
    public function viewAction($id);

    /**
     * Search action
     */
    public function searchAction();

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
     * Delete action
     *
     * @param $id
     */
    public function deleteAction($id = null);

    /**
     * Saves create action
     */
    public function saveCreateAction();

    /**
     * Saves edit action
     *
     * @param $id
     */
    public function saveEditAction($id);
}