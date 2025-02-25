<?php

class Validation {
    private $errors = [];

    public function validate($data, $rules) {
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule => $parameter) {
                $methodName = 'validate' . ucfirst($rule);
                if (method_exists($this, $methodName)) {
                    if (!$this->$methodName($data[$field] ?? null, $parameter)) {
                        $this->addError($field, $rule);
                    }
                }
            }
        }

        return empty($this->errors);
    }

    private function validateRequired($value, $parameter) {
        return !empty($value);
    }

    private function validateEmail($value, $parameter) {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    private function validateMin($value, $parameter) {
        return strlen($value) >= $parameter;
    }

    private function validateMax($value, $parameter) {
        return strlen($value) <= $parameter;
    }

    private function validateMatch($value, $parameter) {
        return $value === $parameter;
    }

    private function validatePattern($value, $parameter) {
        return preg_match($parameter, $value) === 1;
    }

    private function addError($field, $rule) {
        $this->errors[$field][] = $this->getErrorMessage($field, $rule);
    }

    private function getErrorMessage($field, $rule) {
        $messages = [
            'required' => 'The ' . $field . ' field is required',
            'email' => 'Please enter a valid email address',
            'min' => 'The ' . $field . ' must be at least :min characters',
            'max' => 'The ' . $field . ' must not exceed :max characters',
            'match' => 'The ' . $field . ' does not match',
            'pattern' => 'The ' . $field . ' format is invalid'
        ];

        return $messages[$rule] ?? 'Validation failed for ' . $field;
    }

    public function getErrors() {
        return $this->errors;
    }

    public function hasErrors() {
        return !empty($this->errors);
    }
}
