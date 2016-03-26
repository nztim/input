<?php namespace NZTim\Input\Tests;

use Illuminate\Http\Request;

class FakeRequest extends Request
{
    protected $input;

    public function setInput(array $input)
    {
        $this->input = $input;
    }

    public function all()
    {
        return $this->input;
    }
}