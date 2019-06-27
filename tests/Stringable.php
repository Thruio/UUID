<?php
namespace Gone\UUID\Tests;

class Stringable
{
    private $value;

    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }
    public function __toString()
    {
        return strtoupper($this->value);
    }
}
