<?php
// Configuration - Changer pour activer/désactiver les vulnérabilités
$VULNERABILITY = true; // true = vulnérable, false = sécurisé

require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

// Configuration JWT selon le mode vulnérable/sécurisé
if ($VULNERABILITY) {
    // ❌ CODE VULNÉRABLE - Clé JWT faible
    $key = "123";
} else {
    // ✅ CODE SÉCURISÉ - Clé JWT forte
    $key = $_ENV['JWT_SECRET'] ?? 'demo_key_for_vulnerability_' . bin2hex(random_bytes(32));
    if (strlen($key) < 32) {
        error_log("ATTENTION: Clé JWT trop faible en production !");
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($VULNERABILITY) {
        // ❌ CODE VULNÉRABLE - NoSQL Injection
        function parseInput($input) {
            $json = json_decode($input, true);
            return $json !== null ? $json : $input;
        }
        
        $username = parseInput($_POST['username'] ?? '');
        $password = parseInput($_POST['password'] ?? '');
        
        $filter = ['username' => $username, 'password' => $password];
        $query = new MongoDB\Driver\Query($filter);
        $cursor = $manager->executeQuery("vuln.users", $query);
        $users = $cursor->toArray();
        $user = $users[0] ?? null;
    } else {
        // ✅ CODE SÉCURISÉ - Protection NoSQL Injection
        
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $filter = [
            'username' => ['$eq' => $username],
            'password' => ['$eq' => $password]
        ];
        $query = new MongoDB\Driver\Query($filter, ['limit' => 1]);
        $cursor = $manager->executeQuery("vuln.users", $query);
        $users = $cursor->toArray();
        $user = $users[0] ?? null;
    }

    if ($user) {
        if ($VULNERABILITY) {
            // ❌ CODE VULNÉRABLE - JWT faible
            $payload = [
                "user" => $user->username,
                "role" => $user->username === 'admin' ? 'admin' : 'user',
                "iat" => time()
            ];
            $jwt = JWT::encode($payload, $key, 'HS256');
            setcookie("token", $jwt, time()+3600, "/");
        } else {
            // ✅ CODE SÉCURISÉ - JWT avec claims complets
            $now = time();
            $payload = [
                "iss" => "https://localhost",
                "aud" => "https://localhost",
                "iat" => $now,
                "nbf" => $now,
                "exp" => $now + 3600,
                "jti" => bin2hex(random_bytes(16)),
                "sub" => (string)$user->_id,
                "username" => $user->username,
                "role" => $user->username === 'admin' ? 'admin' : 'user',
                "ip" => $_SERVER['REMOTE_ADDR'],
                "user_agent" => $_SERVER['HTTP_USER_AGENT'] ?? ''
            ];
            
            $jwt = JWT::encode($payload, $key, 'HS256');
            
            setcookie("token", $jwt, [
                'expires' => time() + 3600,
                'path' => '/',
                'domain' => 'localhost',
                'secure' => true,
                'httponly' => true,
                'samesite' => 'Strict'
            ]);
            
            error_log("Connexion réussie : {$user->username} depuis {$_SERVER['REMOTE_ADDR']}");
        }
        
        header("Location: admin.php");
        exit;
    } else {
        echo "❌ Identifiants invalides";
    }
}
?>

<?php 
include 'includes/header.php'; 
?>

<h2>Connexion</h2>
<form method="POST">
  <input type="text" name="username" placeholder="Nom d'utilisateur"><br>
  <input type="password" name="password" placeholder="Mot de passe"><br>
  <button type="submit">Connexion</button>
</form>

<?php include 'includes/footer.php'; ?>