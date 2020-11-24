<?php

namespace Gone\UUID\Tests;

class Stringable
{
    private string $value;

    public function __toString(): string
    {
        return strtoupper($this->value);
    }

    public function setValue(string $value): self
    {
        $this->value = $value;

        return $this;
    }
}
