<?php include 'includes/header.php'; ?>

<h2>Contactez-nous</h2>
<form method="POST">
  <input type="text" name="name" placeholder="Votre nom">
  <textarea name="message" placeholder="Votre message"></textarea>
  <button type="submit">Envoyer</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  echo "<p>Merci <strong>{$_POST['name']}</strong> pour votre message :</p>";
  echo "<div>{$_POST['message']}</div>";
}
?>

<?php include 'includes/footer.php'; ?>