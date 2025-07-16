<?php include 'includes/header.php'; ?>

<h2>Uploader une image de profil</h2>

<form method="POST" enctype="multipart/form-data">
  <input type="file" name="avatar">
  <button type="submit">Uploader</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $uploadDir = __DIR__ . '/uploads/';
  $fileName = basename($_FILES['avatar']['name']);
  $filePath = $uploadDir . $fileName;

  // ⚠️ Vulnérabilité : aucune vérification du type ou extension
  if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
    echo "<p>Fichier uploadé avec succès : <a href='uploads/$fileName'>$fileName</a></p>";
  } else {
    echo "<p>Échec de l'upload.</p>";
  }
}
?>

<?php include 'includes/footer.php'; ?>
