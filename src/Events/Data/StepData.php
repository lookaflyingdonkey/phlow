<?php namespace Phlow\Events\Data;

class StepData {
    private $inputs = array();
    private $outputs = array();

    /**
     * @return array
     */
    public function getInputs()
    {
        return $this->inputs;
    }

    /**
     * @param array $inputs
     */
    public function setInputs($inputs)
    {
        $this->inputs = $inputs;
    }

    /**
     * @return array
     */
    public function getOutputs()
    {
        return $this->outputs;
    }

    /**
     * @param array $outputs
     */
    public function setOutputs($outputs)
    {
        $this->outputs = $outputs;
    }

    /**
     * @param $path
     * @return bool
     */
    public function getInput($path)
    {
        $path = ltrim($path, '/');

        // @todo: Need to make it handle deep objects with dot notation
        return (isset($this->inputs[$path])) ? $this->inputs[$path] : false;
    }

    /**
     * @param $path
     * @return bool
     */
    public function getOutput($path)
    {
        $path = ltrim($path, '/');
        // @todo: Need to make it handle deep objects with dot notation
        return (isset($this->outputs[$path])) ? $this->outputs[$path] : false;
    }


}