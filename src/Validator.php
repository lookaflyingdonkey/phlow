<?php namespace Phlow;

use Phlow\Workers\Handlers\Handler;

use JsonSchema\Validator as JsonValidator;
use JsonSchema\Uri\UriRetriever;

/**
 * Takes a Workflow and validates it
 *
 * @author Dean Collins <lookaflyingdonkey@icloud.com>
 * @package Phlow
 */
class Validator
{

    private $schema;

    private $handlers = [];

    /**
     * Loads a JSON Schema from an external JSON file to validate the overall workflow
     *
     * @param $name
     */
    public function setSchemaFromFile($name)
    {
        $retriever = new UriRetriever;

        $schema = $retriever->retrieve('file://' . realpath($name));

        $this->schema = $schema;
    }

    /**
     * Adds a handlers meta and schema for validation
     *
     * @param Handler $handler
     */
    public function addHandler(Handler $handler)
    {
        $meta = $handler->describe()['meta'];
        $this->handlers[$this->makeName($meta['name'], $meta['version'])] = $handler->describe();
    }

    /**
     * Un registers a handler description from the validator
     *
     * @param $name
     * @param $version
     * @throws \Exception
     */
    public function removeHandler($name, $version)
    {
        $key = $this->makeName($name, $version);

        if(isset($key)) {
            unset($key);
        }
    }

    /**
     * Returns a string concatenated name for a task including its version
     *
     * @param $name
     * @param $version
     * @return string
     * @throws \Exception
     */
    private function makeName($name, $version)
    {
        if(!isset($name) || !isset($version)) {
            throw new \Exception('Name and Version are required on a task');
        }

        return $name . '-' . $version;
    }

    /**
     * Takes in the overall JSON workflow and validates its structure
     * against the predefined JSONSchema.
     *
     * @param $workflow
     * @return array|bool
     */
    private function validateOverall($workflow)
    {
        $validator = new JsonValidator();

        $validator->check($workflow, $this->schema);

        if($validator->isValid()) {
            return true;
        } else {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            return $errors;
        }
    }

    /**
     * Takes in a Task name and its inputs and validates that it matches the
     * defined schema for that Task
     *
     * @param $name
     * @param $inputs
     * @return array|bool
     * @throws \Exception
     */
    private function validateTask($name, $inputs)
    {
        if (!isset($this->handlers[$name])) {
            throw new \Exception("Handler {$name} isn't set");
        }

        $validator = new JsonValidator();

        $handler = $this->handlers[$name];

        $validator->check($inputs, json_decode($handler["schema"]));

        if ($validator->isValid()) {
            return true;
        } else {
            $errors = [];
            foreach ($validator->getErrors() as $error) {
                $errors[] = sprintf("[%s] %s\n", $error['property'], $error['message']);
            }
            return $errors;
        }
    }

    /**
     * Validates a JSON workflow and its steps against the related schemas
     *
     * @param $workflow
     * @return array|bool
     * @throws \Exception
     */
    public function validate($workflow)
    {
        $workflow = json_decode($workflow);

        $results = $this->validateOverall($workflow);

        if($results !== true) {
            return $results;
        }

        foreach($workflow->steps as $key => $step) {
            $results = $this->validateTask($this->makeName($step->task->name, $step->task->version), $step->inputs);

            if($results !== true) {
                return $results;
            }
        }

        return true;
    }

}