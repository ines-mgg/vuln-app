<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$key = "123"; // même clé faible pour JWT
$token = $_COOKIE['token'] ?? '';
?>
<?php include 'includes/header.php'; ?>

<h1>👥 Liste des utilisateurs</h1>

<?php
try {
    $decoded = JWT::decode($token, new Key($key, 'HS256'));
    $role = $decoded->role ?? 'user';

    if ($role !== 'admin') {
        echo "<p>🚫 Accès refusé. Réservé aux administrateurs.</p>";
    } else {
        // Récupérer tous les utilisateurs avec MongoDB Driver
        $query = new MongoDB\Driver\Query([]);
        $cursor = $manager->executeQuery("vuln.users", $query);
        $users = $cursor->toArray();

        echo "<table border='1' cellpadding='6'>";
        echo "<tr><th>Nom d'utilisateur</th><th>Mot de passe</th><th>Actions</th></tr>";

        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user->username) . "</td>";
            echo "<td>" . htmlspecialchars($user->password) . "</td>";
            echo "<td>
                    <form method='POST' action='delete_user.php'>
                        <input type='hidden' name='username' value='" . $user->username . "'>
                        <button type='submit'>🗑 Supprimer</button>
                    </form>
                  </td>";
            echo "</tr>";
        }

        echo "</table>";
    }

} catch (Exception $e) {
    echo "<p>❌ Erreur d’authentification : " . $e->getMessage() . "</p>";
}
?>

<?php include 'includes/footer.php'; ?>
