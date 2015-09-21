<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Aws\Swf\SwfClient;
use Phlow\Task;

class ExampleHandler extends Handler implements IHandler {

    protected $name = 'Example';
    protected $version = '1.0';
    protected $description = 'An example handler that will take a string and return the uppercase version of it';
    protected $icon = '';


    /**
     * Processes this job and returns a result or throws an exception
     *
     * @param SwfClient $swfClient
     * @param Task $task
     * @return array
     */
    public function handle(SwfClient $swfClient, Task $task)
    {

        $result = strtoupper($task->getInput('string'));

        echo "Converted {$task->getInput('string')} to {$result}";

        return array(
            'string' => $result
        );

    }

    /**
     * Schema to validate this tasks inputs
     *
     * @todo Refactor a bit to avoid having it as one huge string
     * @return string
     */
    protected function getSchema()
    {
        return '{
          "$schema":"http://json-schema.org/draft-04/schema#",
          "title":"' . $this->name . ' Schema",
          "description":"' . $this->description . '",
          "type":"object",
          "additionalProperties":false,
          "properties":{
            "string":{
              "type":"string",
              "description": "The input string to process"
            }
          },
          "required":[
            "string"
          ]
        }';
    }
}
