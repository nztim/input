<?php namespace NZTim\Input\Tests;

use Carbon\Carbon;
use PHPUnit_Framework_TestCase;

class ProcessorTest extends PHPUnit_Framework_TestCase
{
    /** @var FakeRequest */
    protected $request;

    public function setUp()
    {
        $request = new FakeRequest;
        $request->setInput($this->input());
        $this->request = $request;
    }

    protected function input() : array
    {
        return [
            'name'      => 'Barry White',
            'password'  => '12345678',
            'address'   => '123 Queen Street',
            'age'       => '21',
            'pi'        => '3.141',
            'subscribe' => '1',
            'oneday'    => '1 June 2020',
        ];
    }

    /** @test */
    public function isInstantiable()
    {
        $this->assertTrue(class_exists(TestProcessor::class));
    }

    /** @test */
    public function filtersAndSetsDefaults()
    {
        $input = $this->input();
        unset($input['address']);
        $input['id'] = 123;
        $this->request->setInput($input);
        $processor = new TestProcessor($this->request);
        $output = $processor->getInput(false);
        $this->assertFalse(isset($output['id']));
        $this->assertTrue(isset($output['address']));
        $this->assertEquals('', $output['address']);
    }

    /** @test */
    public function castsCorrectly()
    {
        $processor = new TestProcessor($this->request);
        $this->assertTrue(is_bool($processor->getInput()['subscribe']));
        $this->assertTrue(is_float($processor->getInput()['pi']));
        $this->assertTrue(is_int($processor->getInput()['age']));
        $this->assertEquals(strtoupper($this->input()['name']), $processor->getInput()['name']);
        /** @var Carbon $oneday */
        $oneday = $processor->getInput()['oneday'];
        $this->assertInstanceOf(Carbon::class, $oneday);
        $this->assertTrue($oneday->eq(Carbon::parse($this->input()['oneday'])));
    }

    /** @test */
    public function setAndRemoveInput()
    {
        $processor = new TestProcessor($this->request);
        $processor->removeInput('name');
        $processor->setInput('age', 88);
        $input = $processor->getInput(false);
        $this->assertFalse(isset($input['name']));
        $this->assertEquals($input['age'], 88);
    }
}
