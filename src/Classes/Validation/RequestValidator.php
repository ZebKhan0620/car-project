<?php
namespace Classes\Validation;

class RequestValidator {
    private $rules;
    private $errors = [];

    public function __construct(array $rules) {
        $this->rules = $rules;
    }

    public function validate(array $data): bool {
        $this->errors = [];

        foreach ($this->rules as $field => $ruleset) {
            $rules = explode('|', $ruleset);
            
            foreach ($rules as $rule) {
                if (strpos($rule, ':') !== false) {
                    [$ruleName, $ruleValue] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $ruleValue = null;
                }

                if (!$this->validateField($field, $data[$field] ?? null, $ruleName, $ruleValue)) {
                    break;
                }
            }
        }

        return empty($this->errors);
    }

    private function validateField(string $field, $value, string $rule, $ruleValue = null): bool {
        switch ($rule) {
            case 'required':
                if (empty($value)) {
                    $this->errors[$field][] = "$field is required";
                    return false;
                }
                break;

            case 'numeric':
                if (!is_numeric($value)) {
                    $this->errors[$field][] = "$field must be a number";
                    return false;
                }
                break;

            case 'min':
                if ($value < $ruleValue) {
                    $this->errors[$field][] = "$field must be at least $ruleValue";
                    return false;
                }
                break;

            case 'in':
                $allowedValues = explode(',', $ruleValue);
                if (!in_array($value, $allowedValues)) {
                    $this->errors[$field][] = "$field must be one of: $ruleValue";
                    return false;
                }
                break;
        }

        return true;
    }

    public function getErrors(): array {
        return $this->errors;
    }
}
