<?php
require 'vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");

$key = "123"; // clé faible exprès

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
function parseInput($input) {
    $json = json_decode($input, true);
    return $json !== null ? $json : $input;
}

$username = parseInput($_POST['username'] ?? '');
$password = parseInput($_POST['password'] ?? '');


    // Recherche de l'utilisateur avec MongoDB Driver
    $filter = ['username' => $username, 'password' => $password];
    $query = new MongoDB\Driver\Query($filter);
    $cursor = $manager->executeQuery("vuln.users", $query);
    $users = $cursor->toArray();
    $user = $users[0] ?? null;

    if ($user) {
        $payload = [
            "user" => $user->username,
            "role" => $user->username === 'admin' ? 'admin' : 'user',
            "iat" => time()
        ];
        $jwt = JWT::encode($payload, $key, 'HS256');
        setcookie("token", $jwt, time()+3600, "/");
        header("Location: admin.php");
        exit;
    } else {
        echo "❌ Identifiants invalides";
    }
}
?>

<?php include 'includes/header.php'; ?>

<h2>Connexion</h2>
<form method="POST">
  <input type="text" name="username" placeholder="Nom d'utilisateur"><br>
  <input type="password" name="password" placeholder="Mot de passe"><br>
  <button type="submit">Connexion</button>
</form>

<?php include 'includes/footer.php'; ?>