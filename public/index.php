<?php
// Récupérer les produits depuis MongoDB
try {
    $manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");
    $query = new MongoDB\Driver\Query([]);
    $cursor = $manager->executeQuery("vulnshop.products", $query);
    $products = $cursor->toArray();
} catch (Exception $e) {
    echo "Erreur MongoDB: " . $e->getMessage();
    $products = [];
}

include 'includes/header.php';
?>

<h2>Nos produits</h2>
<div class="grid">
<?php foreach ($products as $product): ?>
  <div class="card">
    <img src="assets/<?= htmlspecialchars($product->image) ?>" alt="">
    <h3><?= htmlspecialchars($product->name) ?></h3>
    <p><?= htmlspecialchars($product->price) ?> €</p>
    <a href="product.php?id=<?= $product->id ?>">Voir</a>
  </div>
<?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>
