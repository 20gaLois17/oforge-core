<?php

namespace Oforge\Engine\Modules\Console\Commands\Example;

use Monolog\Logger;
use Oforge\Engine\Modules\Console\Abstracts\AbstractCommand;
use Oforge\Engine\Modules\Console\Lib\Input;

/**
 * Class ExampleCommandOne
 *
 * @package Oforge\Engine\Modules\Console\Commands\Development\Example
 */
class ExampleCommandOne extends AbstractCommand {

    /**
     * ExampleCommandOne constructor.
     */
    public function __construct() {
        parent::__construct('example:cmd1', self::TYPE_DEVELOPMENT);
        $this->setDescription('Example command 1');
    }

    /**
     * @inheritdoc
     */
    public function handle(Input $input, Logger $output) : void {
        $output->notice(ExampleCommandOne::class);
    }

}
