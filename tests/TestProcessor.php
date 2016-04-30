<?php namespace NZTim\Input\Tests;

use NZTim\Input\BaseProcessor;

class TestProcessor extends BaseProcessor
{
    protected function rules() : array
    {
        return [
            'name'      => 'required',
            'password'  => 'required|min:8',
            'address'   => '',
            'age'       => 'required|integer',
            'pi'        => 'required',
            'subscribe' => 'required|in:0,1',
            'oneday'    => '',
        ];
    }

    protected function messages() : array
    {
        return [
            'min' => 'Minimum 8 characters',
        ];
    }

    protected function casts() : array
    {
        $upper = function ($value) {
            return strtoupper($value);
        };

        return [
            'subscribe' => 'bool',
            'age'       => 'int',
            'pi'        => 'float',
            'name'      => $upper,
            'oneday'    => 'carbon',
        ];
    }
}
