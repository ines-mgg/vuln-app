<?php
require 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$key = "123";
$token = $_COOKIE['token'] ?? '';

try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));

    // Seuls les admins peuvent supprimer
    if ($decoded->role !== 'admin') {
        http_response_code(403);
        echo "🚫 Action interdite";
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
        $username = $_POST['username'];

        // ⚠️ PAS de vérification de l’origine de la requête → vulnérable CSRF
        if ($username === 'admin') {
            echo "⚠️ Vous ne pouvez pas supprimer l'admin principal.";
        } else {
            $bulk = new MongoDB\Driver\BulkWrite;
            $bulk->delete(['username' => $username]);
            $result = $manager->executeBulkWrite('vuln.users', $bulk);
            echo "✅ Utilisateur supprimé : $username";
        }
    } else {
        echo "❌ Requête invalide";
    }
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
