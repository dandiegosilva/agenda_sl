<?php
/**
 * CSRF Protection Class
 * Sistema de proteção contra ataques CSRF
 * 
 * @version 2.1 - COMPLETO
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

class CSRF {
    
    /**
     * Gerar token CSRF
     */
    public static function gerarToken(): string {
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validar token CSRF
     */
    public static function validarToken(?string $token): bool {
        if (!isset($_SESSION['csrf_token']) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        
        if (empty($token)) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * ✅ ADICIONADO: Rotacionar token após uso
     */
    public static function rotacionarToken(): void {
        unset($_SESSION['csrf_token']);
        self::gerarToken();
    }
    
    /**
     * Gerar campo hidden HTML
     */
    public static function campoHidden(): string {
        $token = self::gerarToken();
        return sprintf(
            '<input type="hidden" name="csrf_token" value="%s">',
            htmlspecialchars($token, ENT_QUOTES, 'UTF-8')
        );
    }
    
    /**
     * Destruir token
     */
    public static function destruirToken(): void {
        unset($_SESSION['csrf_token']);
    }
}
