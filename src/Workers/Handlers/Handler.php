<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Phlow\Task;

/**
 * Basic functions required for a Worker Handler Class
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow\Workers\Handlers
 */
abstract class Handler {

    protected $name = '';
    protected $version = '';
    protected $description = '';
    protected $icon = '';

    /**
     * Returns the meta and schema for this handler, used for UI rendering and validation
     *
     * @return array
     */
    public function describe()
    {
        return [
            'meta' => [
                'name' => $this->name,
                'version' => $this->version,
                'description' => $this->description,
                'icon' => $this->icon,
            ],
            'schema' => $this->getSchema()
        ];
    }
}
