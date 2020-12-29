<?php namespace NZTim\Input;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Factory;
use Illuminate\Validation\Validator;

use InvalidArgumentException;

abstract class BaseProcessor
{
    protected array $input;
    protected Factory $laravelValidation;
    protected ?Validator $validation;

    public function __construct(Request $request, Factory $laravelValidation)
    {
        $this->input = $this->normalize($request->all());
        $this->laravelValidation = $laravelValidation;
        $this->validation = null;
    }

    // Data and overrides -----------------------------------------------------

    protected array $defaultMessages = [
        'required'     => 'This field is required',
        'in'           => 'Please make a selection', // For select inputs
        'email'        => 'Please enter a valid email address',
        'email.unique' => 'This email address is already registered',
    ];

    abstract protected function rules(): array;

    protected function messages(): array
    {
        return [];
    }

    protected function casts(): array
    {
        return [];
    }

    // ------------------------------------------------------------------------

    public function setInput(string $key, $value)
    {
        $this->input[$key] = $value;
    }

    public function removeInput(string $key)
    {
        unset($this->input[$key]);
    }

    public function replaceInput(array $input)
    {
        $this->input = $this->normalize($input);
    }

    /**
     * Accepts optional rules and messages arrays
     * $merge = true - rules are merged with existing rules
     * $merge = false - existing rules are ignored
     */
    public function validate(array $rules = [], array $messages = [], bool $merge = true): bool
    {
        if ($merge) {
            $rules = array_merge($this->rules(), $rules);
            $messages = array_merge($this->defaultMessages, $this->messages(), $messages);
        }
        $rules = $this->uniqueUpdates($rules);
        $validation = $this->laravelValidation->make($this->input, $rules, $messages);
        if ($validation->fails()) {
            $this->validation = $validation;
            return false;
        }
        return true;
    }

    public function getValidation(): Validator
    {
        if (is_null($this->validation)) {
            $this->validate();
        }
        return $this->validation;
    }

    public function getInput(bool $cast = true): array
    {
        return $cast ? $this->castInput() : $this->input;
    }

    // Remove unexpected fields and ensure all fields in rules array are present
    protected function normalize(array $input): array
    {
        $normalized = [];
        foreach ($this->rules() as $field => $value) {
            $normalized[$field] = $input[$field] ?? '';
        }
        return $normalized;
    }

    /**
     * Updates the rules array to handle unique updates
     * Example rule: 'email' => 'required|integer|unique:users,email,{:id}'
     * If $input['id'] is set, i.e. input has an ID, then ',{:id}' is replaced with the ID, e.g. ',123'
     * If $input['id'] is not set then the extra part of the rule ',{:id}' is removed
     * Each rules value can be a string, an object, or an array of either
     * Walk through the entire array, if a value is a string then replace the placeholder
     * Passing $value as a reference allows it to be altered by the callback
     */
    protected function uniqueUpdates(array $rules): array
    {
        $replace = empty($this->input['id']) ? '' : ',' . $this->input['id'];
        array_walk_recursive($rules, function (&$value) use ($replace) {
            if (is_string($value)) {
                $value = str_replace(',{:id}', $replace, $value);
            }
        });
        return $rules;
    }

    protected function castInput(): array
    {
        $output = [];
        $casts = $this->casts();
        foreach ($this->input as $key => $value) {
            if (!isset($casts[$key])) {
                $output[$key] = $value;
                continue;
            }
            if (is_callable($casts[$key])) {
                $output[$key] = $casts[$key]($value);
            }
            if ($casts[$key] == 'int') {
                $output[$key] = intval($value);
            }
            if ($casts[$key] == 'bool') {
                $output[$key] = (bool) $value;
            }
            if ($casts[$key] == 'float') {
                $output[$key] = floatval($value);
            }
            if ($casts[$key] == 'carbon') {
                $output[$key] = Carbon::parse($value);
            }
            if (!isset($output[$key])) {
                throw new InvalidArgumentException('$casts array value for ' . $key . ' is not valid: ' . $value);
            }
        }
        return $output;
    }
}
