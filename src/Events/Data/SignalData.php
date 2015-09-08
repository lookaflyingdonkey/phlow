<?php namespace Phlow\Events\Data;

class SignalData {
    private $inputs = array();

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
     * @param $path
     * @return bool
     */
    public function getInput($path)
    {
        var_dump($path);
        $path = ltrim($path, '/');
        var_dump($path);
        var_dump($this->inputs);
        // @todo: Need to make it handle deep objects with dot notation
        return (isset($this->inputs[$path])) ? $this->inputs[$path] : false;
    }

}