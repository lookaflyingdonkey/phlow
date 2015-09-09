<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Phlow\Task;

/**
 * Basic functions required for a Worker Handler Class
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow\Workers\Handlers
 */
interface IHandler {

    public function handle(Aws $aws, Task $task);

    public function describe();

}
