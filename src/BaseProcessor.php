<?php namespace NZTim\Input;

use Carbon\Carbon;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Validator as LaravelValidator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

abstract class BaseProcessor
{
    protected $input;
    protected $validation;

    // Data and overrides -----------------------------------------------------

    protected $defaultMessages = [
        'required'     => 'This field is required',
        'in'           => 'Please make a selection', // Used for select inputs
        'email'        => 'Please enter a valid email address',
        'email.unique' => 'This email address is already registered',
    ];

    abstract protected function rules() : array;

    protected function messages() : array
    {
        return [];
    }

    protected function casts() : array
    {
        return [];
    }

    // Public methods ---------------------------------------------------------

    public function __construct(Request $request)
    {
        $this->input = $this->normalize($request->all());
    }

    public function setInput(string $key, $value)
    {
        $this->input[$key] = $value;
    }

    public function removeInput(string $key)
    {
        unset($this->input[$key]);
    }

    /**
     * Accepts optional rules and messages arrays, how they are handled depends on $merge parameter
     * $merge determines if the provided rules are merged with or replace the existing rules
     * @param array $rules
     * @param array $messages
     * @param bool $merge
     * @return bool
     */
    public function validate(array $rules = [], array $messages = [], bool $merge = true) : bool
    {
        if ($merge) {
            $rules = array_merge($this->rules(), $rules);
            $messages = array_merge($this->defaultMessages, $this->messages(), $messages);
        }
        $rules = $this->uniqueUpdates($rules);
        $validation = LaravelValidator::make($this->input, $rules, $messages);
        if ($validation->fails()) {
            $this->validation = $validation;
            return false;
        } else {
            $this->validation = false;
            return true;
        }
    }

    /**
     * @return ValidatorContract|false
     */
    public function getValidation()
    {
        if (is_null($this->validation)) {
            $this->validate();
        }
        return $this->validation;
    }

    public function getInput(bool $cast = true) : array
    {
        return $cast ? $this->castInput() : $this->input;
    }

    // ------------------------------------------------------------------------

    /**
     * Remove unexpected fields and ensure all fields in rules array are present
     * @param $input
     * @return array
     */
    protected function normalize(array $input) : array
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
     * @param array $rules
     * @return array
     */
    protected function uniqueUpdates($rules) : array
    {
        $replace = empty($this->input['id']) ? '' : ',' . $this->input['id'];
        $output = [];
        foreach ($rules as $key => $rule) {
            $output[$key] = str_replace(',{:id}', $replace, $rule);
        }
        return $output;
    }

    /**
     * @throws InvalidArgumentException
     * @return array
     */
    protected function castInput() : array
    {
        $output = [];
        $casts = $this->casts();
        foreach($this->input as $key => $value) {
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
                throw new InvalidArgumentException('$casts array value for '. $key . ' is not valid: ' . $value);
            }
        }
        return $output;
    }
}
