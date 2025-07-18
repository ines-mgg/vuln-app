<?php 
// Configuration - Changer pour activer/désactiver la vulnérabilité
$VULNERABILITY = true; // true = vulnérable, false = sécurisé

include 'includes/header.php'; 
?>

<h2>Visualisation des logs</h2>

<form method="GET">
  <label for="file">Nom du fichier log :</label>
  <input type="text" name="file" id="file" placeholder="ex: access.log">
  <button type="submit">Afficher</button>
</form>

<?php
if (isset($_GET['file'])) {
  $file = $_GET['file'];
  
  if ($VULNERABILITY) {
    // ❌ CODE VULNÉRABLE - LFI
    $path = __DIR__ . '/logs/' . $file;
    
    if (file_exists($path)) {
      echo "<pre>" . file_get_contents($path) . "</pre>";
    } else {
      if (file_exists($file)) {
        echo "<pre>" . file_get_contents($file) . "</pre>";
      } else {
        echo "<p>Fichier introuvable.</p>";
      }
    }
  } else {
    // ✅ CODE SÉCURISÉ - Protection LFI
    $allowed_files = ['access.log', 'error.log', 'app.log'];
    
    if (!in_array($file, $allowed_files, true)) {
      echo "<p>❌ Fichier non autorisé.</p>";
      error_log("Tentative LFI : {$file} depuis {$_SERVER['REMOTE_ADDR']}");
      exit;
    }
    
    $file = basename($file);
    $log_dir = realpath(__DIR__ . '/logs');
    $path = realpath($log_dir . '/' . $file);
    
    if ($path === false || strpos($path, $log_dir) !== 0) {
      echo "<p>❌ Accès interdit.</p>";
      error_log("Tentative LFI (realpath) : {$file} depuis {$_SERVER['REMOTE_ADDR']}");
      exit;
    }
    
    if (file_exists($path)) {
      $file_size = filesize($path);
      if ($file_size > 1024 * 1024) {
        echo "<p>⚠️ Fichier trop volumineux. Affichage des 100 dernières lignes :</p>";
        $content = shell_exec("tail -n 100 " . escapeshellarg($path));
      } else {
        $content = file_get_contents($path);
      }
      
      echo "<pre>" . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . "</pre>";
    } else {
      echo "<p>Fichier de log introuvable.</p>";
    }
  }
}
?>

<?php include 'includes/footer.php'; ?>
