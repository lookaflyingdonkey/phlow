<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Phlow\Task;

/**
 * Basic functions required for a Worker Handler Class
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow\Workers\Handlers
 */
interface IHandler {

    public function handle(SwfClient $swfClient, Task $task);

    public function describe();

}
