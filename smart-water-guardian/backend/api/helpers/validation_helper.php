<?php
/**
 * Validation Helper Class
 */

class Validator {
    private $errors = [];
    
    /**
     * Validate data against rules
     */
    public function validate($data, $rules) {
        $this->errors = [];
        
        foreach ($rules as $field => $rule_set) {
            $rules_array = explode('|', $rule_set);
            foreach ($rules_array as $rule) {
                $this->applyRule($field, $data[$field] ?? null, $rule);
            }
        }
        
        return $this->errors;
    }
    
    private function applyRule($field, $value, $rule) {
        if (strpos($rule, ':') !== false) {
            list($rule_name, $parameter) = explode(':', $rule);
        } else {
            $rule_name = $rule;
            $parameter = null;
        }
        
        $method = 'validate' . ucfirst($rule_name);
        if (method_exists($this, $method)) {
            $result = $this->$method($field, $value, $parameter);
            if ($result !== true) {
                $this->errors[$field][] = $result;
            }
        }
    }
    
    private function validateRequired($field, $value, $parameter) {
        if (empty($value) && $value !== '0') {
            return ucfirst($field) . ' is required';
        }
        return true;
    }
    
    private function validateEmail($field, $value, $parameter) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return ucfirst($field) . ' must be a valid email address';
        }
        return true;
    }
    
    private function validateMin($field, $value, $parameter) {
        if (!empty($value) && strlen($value) < $parameter) {
            return ucfirst($field) . ' must be at least ' . $parameter . ' characters';
        }
        return true;
    }
    
    private function validateMax($field, $value, $parameter) {
        if (!empty($value) && strlen($value) > $parameter) {
            return ucfirst($field) . ' must be at most ' . $parameter . ' characters';
        }
        return true;
    }
    
    private function validateNumeric($field, $value, $parameter) {
        if (!empty($value) && !is_numeric($value)) {
            return ucfirst($field) . ' must be numeric';
        }
        return true;
    }
    
    private function validatePhone($field, $value, $parameter) {
        if (!empty($value) && !preg_match('/^[0-9]{10,15}$/', $value)) {
            return ucfirst($field) . ' must be a valid phone number';
        }
        return true;
    }
    
    private function validatePassword($field, $value, $parameter) {
        if (!empty($value)) {
            if (strlen($value) < 8) {
                return ucfirst($field) . ' must be at least 8 characters';
            }
            if (!preg_match('/[A-Z]/', $value)) {
                return ucfirst($field) . ' must contain at least one uppercase letter';
            }
            if (!preg_match('/[a-z]/', $value)) {
                return ucfirst($field) . ' must contain at least one lowercase letter';
            }
            if (!preg_match('/[0-9]/', $value)) {
                return ucfirst($field) . ' must contain at least one number';
            }
            if (!preg_match('/[!@#$%^&*]/', $value)) {
                return ucfirst($field) . ' must contain at least one special character (!@#$%^&*)';
            }
        }
        return true;
    }
    
    private function validateIn($field, $value, $parameter) {
        if (!empty($value)) {
            $allowed = explode(',', $parameter);
            if (!in_array($value, $allowed)) {
                return ucfirst($field) . ' must be one of: ' . implode(', ', $allowed);
            }
        }
        return true;
    }
    
    private function validateDate($field, $value, $parameter) {
        if (!empty($value)) {
            $format = $parameter ?: 'Y-m-d';
            $d = DateTime::createFromFormat($format, $value);
            if (!$d || $d->format($format) !== $value) {
                return ucfirst($field) . ' must be a valid date';
            }
        }
        return true;
    }
    
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getFirstError() {
        if (empty($this->errors)) {
            return null;
        }
        $first_field = array_keys($this->errors)[0];
        return $this->errors[$first_field][0] ?? null;
    }
    
    public function sanitize($data) {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)));
    }
}
?>
