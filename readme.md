### Input Processor 

Define input filtering, validation rules and type casting all in one place, and ensure that the input array has all the desired keys.

### Installation

`composer require nztim/input`

### Usage

Extend BaseProcessor and override as follows:

  * `protected function rules()` - returns an array of Laravel validation rules. This method is abstract and must be implemented. 
  * The input data is normalized so that all fields in the rules array are certain to be present, and any other fields are filtered out
    * Note: this means all valid fields must have a rule, even if it's empty.
  * `protected function messages()` - returns an array of associated validation messages.
  * `protected function casts()` - returns an array of validation messages. Valid possibilities are `bool`, `int`, `float`, and a callable. E.g. `['age' => 'int', 'subscribe' => 'bool']`

Then, to handle form input:

  * Inject the Processor class into your controller, instead of injecting Request.
  * `$processor->setInput()` and `$processor->removeInput()` can directly modify the input array as required.
  * `$processor->validate()` returns boolean success/failure. Arguments allow you to override/merge rules/messages as needed.
  * `$processor->getInput()` returns input casted to desired types.
  
  
