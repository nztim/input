<?php namespace NZTim\Input\Tests;

use Illuminate\Http\Request;

class FakeRequest extends Request
{
    protected array $input;

    public function setInput(array $input)
    {
        $this->input = $input;
    }

    public function all($keys = null): array
    {
        return $this->input;
    }
}
