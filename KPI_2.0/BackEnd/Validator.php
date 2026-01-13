<?php
/**
 * Classe Validator - Validações Centralizadas
 * Fornece métodos reutilizáveis para validação de dados
 */

class Validator {
    
    private $errors = [];
    
    /**
     * Valida se o campo não está vazio
     */
    public function required($value, $fieldName) {
        if (empty(trim($value))) {
            $this->errors[$fieldName] = "O campo {$fieldName} é obrigatório.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida email
     */
    public function email($email, $fieldName = 'email') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "Email inválido.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida email corporativo
     */
    public function corporateEmail($email, $domain = '@suntechdobrasil.com.br', $fieldName = 'email') {
        if (!$this->email($email, $fieldName)) {
            return false;
        }
        
        if (!str_ends_with($email, $domain)) {
            $this->errors[$fieldName] = "Somente e-mails {$domain} são permitidos.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida CNPJ
     */
    public function cnpj($cnpj, $fieldName = 'cnpj') {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            $this->errors[$fieldName] = "CNPJ deve ter 14 dígitos.";
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            $this->errors[$fieldName] = "CNPJ inválido.";
            return false;
        }
        
        // Validação dos dígitos verificadores
        $sum = 0;
        $weights = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        
        $digit1 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        if ($cnpj[12] != $digit1) {
            $this->errors[$fieldName] = "CNPJ inválido.";
            return false;
        }
        
        $sum = 0;
        $weights = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights[$i];
        }
        
        $digit2 = $sum % 11 < 2 ? 0 : 11 - ($sum % 11);
        
        if ($cnpj[13] != $digit2) {
            $this->errors[$fieldName] = "CNPJ inválido.";
            return false;
        }
        
        return true;
    }
    
    /**
     * Valida CPF
     */
    public function cpf($cpf, $fieldName = 'cpf') {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            $this->errors[$fieldName] = "CPF deve ter 11 dígitos.";
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $this->errors[$fieldName] = "CPF inválido.";
            return false;
        }
        
        // Validação dos dígitos verificadores
        for ($t = 9; $t < 11; $t++) {
            $d = 0;
            for ($c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) {
                $this->errors[$fieldName] = "CPF inválido.";
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Valida comprimento mínimo
     */
    public function minLength($value, $min, $fieldName) {
        if (strlen($value) < $min) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ter no mínimo {$min} caracteres.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida comprimento máximo
     */
    public function maxLength($value, $max, $fieldName) {
        if (strlen($value) > $max) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ter no máximo {$max} caracteres.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se é número inteiro
     */
    public function integer($value, $fieldName) {
        if (!filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ser um número inteiro.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se é número (int ou float)
     */
    public function numeric($value, $fieldName) {
        if (!is_numeric($value)) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ser numérico.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se é número positivo
     */
    public function positive($value, $fieldName) {
        if (!$this->numeric($value, $fieldName)) {
            return false;
        }
        
        if ($value <= 0) {
            $this->errors[$fieldName] = "O campo {$fieldName} deve ser positivo.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida data no formato Y-m-d ou d/m/Y
     */
    public function date($date, $fieldName = 'data', $format = 'Y-m-d') {
        $d = DateTime::createFromFormat($format, $date);
        if (!$d || $d->format($format) !== $date) {
            $this->errors[$fieldName] = "Data inválida. Use o formato " . $format;
            return false;
        }
        return true;
    }
    
    /**
     * Valida se valor está dentro de uma lista
     */
    public function inArray($value, $array, $fieldName) {
        if (!in_array($value, $array, true)) {
            $this->errors[$fieldName] = "Valor inválido para {$fieldName}.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida URL
     */
    public function url($url, $fieldName = 'url') {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[$fieldName] = "URL inválida.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida regex customizado
     */
    public function regex($value, $pattern, $fieldName, $message = null) {
        if (!preg_match($pattern, $value)) {
            $this->errors[$fieldName] = $message ?? "O campo {$fieldName} está em formato inválido.";
            return false;
        }
        return true;
    }
    
    /**
     * Valida se dois campos são iguais (útil para confirmação de senha)
     */
    public function match($value1, $value2, $fieldName) {
        if ($value1 !== $value2) {
            $this->errors[$fieldName] = "Os campos não coincidem.";
            return false;
        }
        return true;
    }
    
    /**
     * Verifica se há erros
     */
    public function hasErrors() {
        return !empty($this->errors);
    }
    
    /**
     * Retorna todos os erros
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Retorna o primeiro erro
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Retorna erros formatados como string
     */
    public function getErrorsAsString($separator = '<br>') {
        return implode($separator, $this->errors);
    }
    
    /**
     * Limpa erros
     */
    public function clearErrors() {
        $this->errors = [];
    }
    
    /**
     * Adiciona erro customizado
     */
    public function addError($fieldName, $message) {
        $this->errors[$fieldName] = $message;
    }
}

/**
 * Função helper para criar instância do validador
 */
function validator() {
    return new Validator();
}
?>
