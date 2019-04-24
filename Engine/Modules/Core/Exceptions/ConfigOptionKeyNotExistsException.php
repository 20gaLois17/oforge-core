<?php
/**
 * Created by PhpStorm.
 * User: Matthaeus.Schmedding
 * Date: 11.10.2018
 * Time: 09:17
 */

namespace Oforge\Engine\Modules\Core\Exceptions;

class ConfigOptionKeyNotExistsException extends \Exception
{
    /**
     * ServiceNotFoundException constructor.
     * @param $name
     */
    public function __construct($name)
    {
        parent::__construct("Config key $name not found in options");
    }
}
