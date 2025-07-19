<?php
// Configuration - Changer pour activer/désactiver la vulnérabilité CSRF
$VULNERABILITY = true; // true = vulnérable (pas de protection CSRF), false = sécurisé

require 'vendor/autoload.php';
require_once '../csrf_helper.php';
require_once 'jwt_utils.php';
csrf_start();
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 🔐 Clé de signature selon le mode vulnérable/sécurisé
if ($VULNERABILITY) {
    $key = "123"; // ❌ Clé faible pour test de faille
} else {
    $key = $_ENV['JWT_SECRET'] ?? getSecureJwtKey(); // ✅ Clé forte générée aléatoirement
}

// 🔍 Récupérer le token depuis le cookie
$token = $_COOKIE['token'] ?? '';

?>
<?php include 'includes/header.php'; ?>

<?php
try {
    // ⚠️ Attention : pas de vérification de 'alg' ici
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $username = $decoded->user ?? 'inconnu';
    $role = $decoded->role ?? 'user';

    if ($role !== 'admin') {
        echo "<p>🚫 Accès refusé. Seuls les admins peuvent voir cette page.</p>";
    } else {
        echo "<h1>👑 Panneau d'administration</h1>";
        echo "<p>Bienvenue <strong>$username</strong> !</p>";

        // Handle admin actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$VULNERABILITY) {
                csrf_check(); // ✅ CSRF Protection when secure
            }
            
            if (isset($_POST['clear_logs'])) {
                if ($VULNERABILITY) {
                    echo "<p>⚠️ Logs cleared (CSRF vulnerable)</p>";
                } else {
                    echo "<p>✅ Logs cleared successfully (CSRF-protected)</p>";
                }
            }
        }
        
        echo "<ul>
            <li><a href='users.php'>Gestion des utilisateurs</a></li>
            <li><a href='csrf.html'>Exemple de faille CSRF</a></li>
        </ul>";
        
        // Admin form with conditional CSRF protection
        echo "<h3>Admin Actions</h3>";
        echo "<form method='POST'>";
        if (!$VULNERABILITY) {
            echo csrf_field(); // ✅ CSRF token only when secure
        }
        echo "<button type='submit' name='clear_logs' onclick='return confirm(\"Clear logs?\")'>Clear System Logs</button>";
        echo "</form>";
    }

} catch (Exception $e) {
    echo "<p>❌ Token invalide ou expiré : " . $e->getMessage() . "</p>";
    echo "<a href='login.php'>Retour au login</a>";
}
?>

<?php include 'includes/footer.php'; ?>
