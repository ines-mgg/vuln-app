<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 🔐 Clé de signature (faible exprès pour test de faille)
$key = "123";

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

        echo "<ul>
            <li><a href='users.php'>Gestion des utilisateurs</a></li>
            <li><a href='csrf.html'>Exemple de faille CSRF</a></li>
        </ul>";
    }

} catch (Exception $e) {
    echo "<p>❌ Token invalide ou expiré : " . $e->getMessage() . "</p>";
    echo "<a href='login.php'>Retour au login</a>";
}
?>

<?php include 'includes/footer.php'; ?>
