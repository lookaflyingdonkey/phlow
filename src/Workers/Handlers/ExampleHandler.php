<?php namespace Phlow\Workers\Handlers;

use Aws\Common\Aws;
use Phlow\Task;

class ExampleHandler extends Handler implements IHandler {

    private $name = 'Example';
    private $version = '1.0';
    private $description = 'An example handler that will take a string and return the uppercase version of it';
    private $icon = '';


    /**
     * Processes this job and returns a result or throws an exception
     *
     * @param Task $task
     * @return array
     */
    public function handle(Aws $aws, Task $task)
    {

        $result = strtoupper($task->getInput('string'));

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
    private function getSchema()
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
