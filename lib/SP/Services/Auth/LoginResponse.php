<?php

namespace SP\Services\Auth;

/**
 * Class LoginResponse
 *
 * @package SP\Services\Auth
 */
class LoginResponse
{
    /**
     * @var int
     */
    private $status;
    /**
     * @var string
     */
    private $redirect;

    /**
     * LoginResponse constructor.
     * @param int $status
     * @param string $redirect
     */
    public function __construct($status, $redirect = null)
    {
        $this->status = $status;
        $this->redirect = $redirect;
    }

    /**
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getRedirect()
    {
        return $this->redirect;
    }
}