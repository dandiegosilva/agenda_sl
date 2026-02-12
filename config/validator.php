<?php

class Validator {
    
    /**
     * Validar e sanitizar nome
     */
    public static function nome(string $nome, int $minLength = 3, int $maxLength = 100): ?string {
        $nome = trim($nome);
        
        // Remover tags HTML
        $nome = strip_tags($nome);
        
        // Remover caracteres especiais perigosos mantendo acentos
        $nome = preg_replace('/[^a-zA-ZÀ-ÿ\s\-]/u', '', $nome);
        
        // Verificar comprimento
        if (mb_strlen($nome) < $minLength || mb_strlen($nome) > $maxLength) {
            return null;
        }
        
        return $nome;
    }
    
    /**
     * Validar telefone brasileiro
     */
    public static function telefone(string $telefone): ?string {
        // Remover formatação
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        
        // Validar formato brasileiro (10 ou 11 dígitos)
        if (!preg_match('/^[1-9]{2}9?[0-9]{8}$/', $telefone)) {
            return null;
        }
        
        return $telefone;
    }
    
    /**
     * Validar cidade
     */
    public static function cidade(string $cidade, int $minLength = 2, int $maxLength = 100): ?string {
        return self::nome($cidade, $minLength, $maxLength);
    }
    
    /**
     * Validar data no formato dd/mm/yyyy
     */
    public static function data(string $data): ?DateTime
{
    if (empty($data)) {
        return null;
    }

    // Aceita formato BR (d/m/Y)
    $dt = DateTime::createFromFormat('d/m/Y', $data);

    if (!$dt) {
        return null;
    }

    // Zerar horário
    $dt->setTime(0, 0, 0);

    // Não permitir datas passadas
    $hoje = new DateTime('today');
    if ($dt < $hoje) {
        return null;
    }

    return $dt;
}
    
    /**
     * Validar inteiro positivo
     */
    public static function inteiroPositivo($valor, int $min = 1, int $max = PHP_INT_MAX): ?int {
        $valor = filter_var($valor, FILTER_VALIDATE_INT);
        
        if ($valor === false || $valor < $min || $valor > $max) {
            return null;
        }
        
        return $valor;
    }
    
    /**
     * Validar valor monetário
     */
    public static function valorMonetario($valor, float $min = 0.01, float $max = 999999.99): ?float {
        // Converter formato brasileiro para float
        if (is_string($valor)) {
            $valor = str_replace(['.', ','], ['', '.'], $valor);
        }
        
        $valor = filter_var($valor, FILTER_VALIDATE_FLOAT);
        
        if ($valor === false || $valor < $min || $valor > $max) {
            return null;
        }
        
        return round($valor, 2);
    }
    
    /**
     * Sanitizar string para output HTML
     */
    public static function sanitizeHtml(string $string): string {
        return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    /**
     * Validar email
     */
    public static function email(string $email): ?string {
        $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
        
        if (!$email || mb_strlen($email) > 255) {
            return null;
        }
        
        return strtolower($email);
    }
    
    /**
     * Validar URL
     */
    public static function url(string $url): ?string {
        $url = filter_var(trim($url), FILTER_VALIDATE_URL);
        
        if (!$url) {
            return null;
        }
        
        // Apenas HTTPS em produção
        if (getenv('APP_ENV') === 'production' && !str_starts_with($url, 'https://')) {
            return null;
        }
        
        return $url;
    }
    
    /**
     * Validar senha forte
     */
    public static function senha(string $senha): array {
        $errors = [];
        
        if (strlen($senha) < 8) {
            $errors[] = 'A senha deve ter no mínimo 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $senha)) {
            $errors[] = 'A senha deve conter ao menos uma letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $senha)) {
            $errors[] = 'A senha deve conter ao menos uma letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $senha)) {
            $errors[] = 'A senha deve conter ao menos um número';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $senha)) {
            $errors[] = 'A senha deve conter ao menos um caractere especial';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Prevenir SQL Injection em strings de busca
     */
    public static function searchTerm(string $term, int $maxLength = 100): ?string {
        $term = trim($term);
        
        // Remover caracteres perigosos
        $term = preg_replace('/[%_\'\"]/', '', $term);
        
        if (mb_strlen($term) > $maxLength) {
            return null;
        }
        
        return $term;
    }
}
