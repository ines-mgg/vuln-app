<?php 
// Configuration - Changer pour activer/désactiver la vulnérabilité
$VULNERABILITY = true; // true = vulnérable, false = sécurisé
include 'includes/header.php'; 
?>

<h2>Uploader une image de profil</h2>

<form method="POST" enctype="multipart/form-data">
  <input type="file" name="avatar">
  <button type="submit">Uploader</button>
</form>

<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($VULNERABILITY) {
    // ❌ CODE VULNÉRABLE - RCE via upload
    $uploadDir = __DIR__ . '/uploads/';
    $fileName = basename($_FILES['avatar']['name']);
    $filePath = $uploadDir . $fileName;

    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
      echo "<p>Fichier uploadé avec succès : <a href='uploads/$fileName'>$fileName</a></p>";
    } else {
      echo "<p>Échec de l'upload.</p>";
    }
  } else {
    // ✅ CODE SÉCURISÉ - Protection RCE
    
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
      die("❌ Erreur lors de l'upload du fichier");
    }
    
    $uploadDir = __DIR__ . '/uploads/';
    
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0755, true);
      
      $htaccess_content = "php_flag engine off\nRemoveHandler .php .phtml .php3 .php4 .php5 .php7\nOptions -Indexes\n";
      file_put_contents($uploadDir . '.htaccess', $htaccess_content);
    }
    
    $maxSize = 2 * 1024 * 1024; // 2MB
    if ($_FILES['avatar']['size'] > $maxSize) {
      die("Fichier trop volumineux (max 2MB)");
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
    finfo_close($finfo);
    
    $allowedMimes = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png', 
      'image/gif' => 'gif',
      'image/webp' => 'webp'
    ];
    
    if (!array_key_exists($mimeType, $allowedMimes)) {
      die("Type de fichier non autorisé. Seules les images sont acceptées.");
    }
    
    $imageInfo = @getimagesize($_FILES['avatar']['tmp_name']);
    if ($imageInfo === false) {
      die("Le fichier n'est pas une image valide");
    }
    
    $extension = $allowedMimes[$mimeType];
    $fileName = 'avatar_' . uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $filePath)) {
      chmod($filePath, 0644);
      
      echo "<p>✅ Image uploadée avec succès !</p>";
      echo "<p>Nom : " . htmlspecialchars($fileName) . "</p>";
      echo "<p>Taille : " . number_format($_FILES['avatar']['size'] / 1024, 2) . " KB</p>";
      echo "<p>Type : " . htmlspecialchars($mimeType) . "</p>";
      echo "<p>Dimensions : {$imageInfo[0]} x {$imageInfo[1]} pixels</p>";
      echo "<img src='uploads/" . htmlspecialchars($fileName) . "' alt='Avatar' style='max-width: 200px; border: 1px solid #ddd; padding: 5px;'>";
      
      error_log("Upload réussi : {$fileName} ({$mimeType}) depuis {$_SERVER['REMOTE_ADDR']}");
    } else {
      die("Échec du déplacement du fichier");
    }
  }
}
?>

<?php include 'includes/footer.php'; ?>
