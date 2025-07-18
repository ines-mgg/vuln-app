<?php 
// Configuration - Changer pour activer/désactiver la vulnérabilité
$VULNERABILITY = true; // true = vulnérable, false = sécurisé
include 'includes/header.php';
?>

<h2>Contactez-nous</h2>
<form method="POST">
  <input type="text" name="name" placeholder="Votre nom">
  <textarea name="message" placeholder="Votre message"></textarea>
  <button type="submit">Envoyer</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($VULNERABILITY) {
    // ❌ CODE VULNÉRABLE - XSS
    echo "<p>Merci <strong>{$_POST['name']}</strong> pour votre message :</p>";
    echo "<div>{$_POST['message']}</div>";
  } else {
    // ✅ CODE SÉCURISÉ - Protection XSS
    $name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
    $message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');
    
    if (strlen($_POST['name']) > 100 || strlen($_POST['message']) > 1000) {
      echo "<p>❌ Entrée trop longue.</p>";
      exit;
    }
    
    echo "<p>Merci <strong>{$name}</strong> pour votre message :</p>";
    echo "<div class='message-content'>{$message}</div>";
  }
}
?>

<?php include 'includes/footer.php'; ?>