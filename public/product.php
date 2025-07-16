<?php
// Récupérer les produits depuis MongoDB
try {
    $manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");
    $query = new MongoDB\Driver\Query([]);
    $cursor = $manager->executeQuery("vulnshop.products", $query);
    $products = [];
    foreach ($cursor as $document) {
        $products[$document->id] = $document;
    }
} catch (Exception $e) {
    echo "Erreur MongoDB: " . $e->getMessage();
    $products = [];
}

$id = $_GET['id'] ?? null;
$product = $products[$id] ?? null;
include 'includes/header.php';
?>

<?php if ($product): ?>
  <h2><?= htmlspecialchars($product->name) ?></h2>
  <img src="assets/<?= htmlspecialchars($product->image) ?>" alt="" style="max-width: 300px; max-height: 300px; object-fit: cover;">
  <p><?= htmlspecialchars($product->description) ?></p>
  <p><strong><?= htmlspecialchars($product->price) ?> €</strong></p>
  <button disabled>Ajouter au panier</button>
<?php else: ?>
  <p>Produit introuvable.</p>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
