<?php include 'includes/header.php'; ?>

<h2>Visualisation des logs</h2>

<form method="GET">
  <label for="file">Nom du fichier log :</label>
  <input type="text" name="file" id="file" placeholder="ex: access.log">
  <button type="submit">Afficher</button>
</form>

<?php
if (isset($_GET['file'])) {
  $file = $_GET['file'];

  // ⚠️ Faille LFI ici (aucune validation du chemin)
  $path = __DIR__ . '/logs/' . $file;

  if (file_exists($path)) {
    echo "<pre>" . file_get_contents($path) . "</pre>";
  } else {
    // On autorise aussi les chemins absolus (faille)
    if (file_exists($file)) {
      echo "<pre>" . file_get_contents($file) . "</pre>";
    } else {
      echo "<p>Fichier introuvable.</p>";
    }
  }
}
?>

<?php include 'includes/footer.php'; ?>
