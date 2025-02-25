<?php

class ValidationService {
    private $errors = [];
    private $data = [];
    private $rules = [];

    public function __construct(array $data, array $rules) {
        $this->data = $data;
        $this->rules = $rules;
    }

    public function validate() {
        foreach ($this->rules as $field => $rules) {
            $rules = explode('|', $rules);
            
            foreach ($rules as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $parameter] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $parameter = null;
                }

                $methodName = 'validate' . ucfirst($ruleName);
                if (method_exists($this, $methodName)) {
                    $this->$methodName($field, $parameter);
                }
            }
        }

        return empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    private function validateRequired($field) {
        if (!isset($this->data[$field]) || empty(trim($this->data[$field]))) {
            $this->addError($field, ucfirst($field) . ' is required');
        }
    }

    private function validateEmail($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
            $this->addError($field, 'Invalid email format');
        }
    }

    private function validateMin($field, $parameter) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) < $parameter) {
            $this->addError($field, ucfirst($field) . ' must be at least ' . $parameter . ' characters');
        }
    }

    private function validateMax($field, $parameter) {
        if (isset($this->data[$field]) && strlen($this->data[$field]) > $parameter) {
            $this->addError($field, ucfirst($field) . ' must not exceed ' . $parameter . ' characters');
        }
    }

    private function validateMatch($field, $parameter) {
        if (isset($this->data[$field]) && isset($this->data[$parameter]) && 
            $this->data[$field] !== $this->data[$parameter]) {
            $this->addError($field, ucfirst($field) . ' must match ' . $parameter);
        }
    }

    private function validateAlpha($field) {
        if (isset($this->data[$field]) && !ctype_alpha(str_replace(' ', '', $this->data[$field]))) {
            $this->addError($field, ucfirst($field) . ' must contain only letters');
        }
    }

    private function validateAlphaNum($field) {
        if (isset($this->data[$field]) && !ctype_alnum(str_replace(' ', '', $this->data[$field]))) {
            $this->addError($field, ucfirst($field) . ' must contain only letters and numbers');
        }
    }

    private function validateNumeric($field) {
        if (isset($this->data[$field]) && !is_numeric($this->data[$field])) {
            $this->addError($field, ucfirst($field) . ' must be numeric');
        }
    }

    private function validateUrl($field) {
        if (isset($this->data[$field]) && !filter_var($this->data[$field], FILTER_VALIDATE_URL)) {
            $this->addError($field, ucfirst($field) . ' must be a valid URL');
        }
    }

    private function validateDate($field) {
        if (isset($this->data[$field])) {
            $date = date_parse($this->data[$field]);
            if ($date['error_count'] > 0) {
                $this->addError($field, ucfirst($field) . ' must be a valid date');
            }
        }
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }
} 