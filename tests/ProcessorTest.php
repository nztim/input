<?php namespace NZTim\Input\Tests;

use Carbon\Carbon;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    /** @test */
    public function validationSuccess()
    {
        $request = $this->fakeRequest();
        $factory = $this->factory();
        $processor = new TestProcessor($request, $factory);
        $this->assertTrue($processor->validate());
    }

    /** @test */
    public function filtersAndSetsDefaults()
    {
        $input = $this->input();
        unset($input['address']);
        $input['id'] = 123;
        $request = $this->fakeRequest($input);
        $processor = new TestProcessor($request, $this->factory());
        $output = $processor->getInput(false); // Get raw input
        $this->assertFalse(isset($output['id']));
        $this->assertTrue(isset($output['address']));
        $this->assertEquals('', $output['address']);
    }

    /** @test */
    public function castsCorrectly()
    {
        $processor = new TestProcessor($this->fakeRequest(), $this->factory());
        $input = $processor->getInput();
        $this->assertTrue(is_bool($input['subscribe']));
        $this->assertTrue(is_float($input['pi']));
        $this->assertTrue(is_int($input['age']));
        $this->assertEquals(strtoupper($this->input()['name']), $input['name']);
        $oneday = $input['oneday'];
        $this->assertInstanceOf(Carbon::class, $oneday);
        $this->assertTrue($oneday->eq(Carbon::parse($input['oneday'])));
    }

    /** @test */
    public function setAndRemoveInput()
    {
        $processor = new TestProcessor($this->fakeRequest(), $this->factory());
        $processor->removeInput('name');
        $processor->setInput('age', 88);
        $input = $processor->getInput(false);
        $this->assertFalse($processor->validate());
        $this->assertFalse(isset($input['name']));
        $this->assertEquals(88, $input['age']);
    }

    /** @test */
    public function removesPlaceholderWhenIdIsNotPresent()
    {
        $processor = new TestUniqueHandling($this->fakeRequest(), $this->factory());
        $rules = ['name' => 'required'];
        $this->assertEquals($rules, $processor->processRules($rules));
        $this->assertEquals($rules, $processor->processRules(['name' => 'required,{:id}']));
    }

    /** @test */
    public function replacesPlaceholderWhenIdIsPresent()
    {
        $processor = new TestUniqueHandling($this->fakeRequest(), $this->factory());
        $rules = ['name' => 'required,{:id}'];
        $processor->setInput('id', 99);
        $this->assertEquals(['name' => 'required,99'], $processor->processRules($rules));
    }

    /** @test */
    public function replacementWorksWithArraysOfRules()
    {
        // Remove
        $processor = new TestUniqueHandling($this->fakeRequest(), $this->factory());
        $rules = ['name' => ['string', 'required,{:id}']];
        $this->assertEquals(['name' => ['string', 'required']], $processor->processRules($rules));
        // Replace
        $processor = new TestUniqueHandling($this->fakeRequest(), $this->factory());
        $processor->setInput('id', 99);
        $this->assertEquals(['name' => ['string', 'required,99']], $processor->processRules($rules));
    }

    private function input() : array
    {
        return [
            'name'      => 'Barry White',       // 'required'
            'password'  => '12345678',          // 'required|min:8'
            'address'   => '123 Queen Street',  // ''
            'age'       => '21',                // 'required|integer'
            'pi'        => '3.141',             // 'required'
            'subscribe' => '1',                 // 'required|in:0,1'
            'oneday'    => '1 June 2020',       // ''
        ];
    }

    private function fakeRequest(array $input = null): FakeRequest
    {
        $request = new FakeRequest();
        $request->setInput($input ?? $this->input());
        return $request;
    }

    private function factory(): Factory
    {
        $loader = new FileLoader(new Filesystem(), 'lang');
        $translator = new Translator($loader, 'en');
        return new Factory($translator);
    }
}
