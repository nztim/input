### Input Processor

Define input filtering, validation rules and type casting all in one place, and ensure that the input array has all the desired keys.

### Installation

`composer require nztim/input`

### Usage

Create a Processor class that extends `NZTim\Input\BaseProcessor` and add overrides as follows:

* `protected function rules()` - returns an array of Laravel validation rules. This method is abstract so must be implemented.
* The input data is normalized so that all fields in the rules array are certain to be present, and any other fields are filtered out
* Note: this means **all valid fields must have a rule**, even if it's empty.
* `protected function messages()` - returns an array of your validation message overrides.
* `protected function casts()` - returns an array of fields to typecast.
* Valid possibilities are `bool`, `int`, `float`, and a callable. E.g. `['age' => 'int', 'subscribe' => 'bool', 'foo' => function($value) { //... }]`

Then, to handle form input:

* Inject the Processor class into your controller.
* The request is used to set the input values.
* `$processor->setInput()` and `$processor->removeInput()` can directly modify the input array as required.
    * Can be used to set input values not in the rules() array.
* `$processor->validate()` returns boolean success/failure. Arguments allow you to override/merge rules/messages as needed.
* `$processor->getValidation()` returns the validation object, or false if validation was successful.
* `$processor->getInput()` returns input casted to desired types, use `getInput(false)` to return uncasted input.

To handle other situations, inject the Processor class and use `$processor->replaceInput($inputArray)` to set the entire input array at once.
