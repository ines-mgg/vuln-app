<?php
/**
 * Utilitaires JWT pour l'application vulnérable
 */

/**
 * Génère/récupère une clé JWT persistante et sécurisée
 * La clé est générée aléatoirement une seule fois puis stockée
 */
function getSecureJwtKey() {
    $keyFile = '/tmp/jwt_key.txt';
    
    if (!file_exists($keyFile)) {
        // Générer une nouvelle clé forte aléatoire
        $key = 'demo_key_for_vulnerability_' . bin2hex(random_bytes(32));
        file_put_contents($keyFile, $key);
        error_log("Nouvelle clé JWT générée : " . substr($key, 0, 20) . "...");
    }
    
    return file_get_contents($keyFile);
}

/**
 * Réinitialise la clé JWT (pour les tests)
 */
function resetJwtKey() {
    $keyFile = '/tmp/jwt_key.txt';
    if (file_exists($keyFile)) {
        unlink($keyFile);
        error_log("Clé JWT réinitialisée");
    }
}
