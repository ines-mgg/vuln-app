<?php
try {
    $manager = new MongoDB\Driver\Manager("mongodb://mongo:27017");
    
    // === INSERTION DES UTILISATEURS ===
    $bulk = new MongoDB\Driver\BulkWrite;
    $bulk->insert(['username' => 'admin', 'password' => 'admin123']);
    $bulk->insert(['username' => 'user', 'password' => 'user123']);
    
    $result = $manager->executeBulkWrite("vuln.users", $bulk);
    echo "✅ " . $result->getInsertedCount() . " utilisateurs insérés dans MongoDB\n";
    
    // === INSERTION DES PRODUITS ===
    $productsFile = __DIR__ . '/products.json';
    if (file_exists($productsFile)) {
        $json = file_get_contents($productsFile);
        $products = json_decode($json, true);
        
        if ($products && is_array($products)) {
            $bulk = new MongoDB\Driver\BulkWrite;
            
            // Transformer les produits en tableau indexé
            $documents = array_values($products);
            foreach ($documents as $document) {
                $bulk->insert($document);
            }
            
            $result = $manager->executeBulkWrite("vulnshop.products", $bulk);
            echo "✅ " . $result->getInsertedCount() . " produits insérés dans MongoDB\n";
        } else {
            echo "❌ Erreur lors du décodage du fichier products.json\n";
        }
    } else {
        echo "⚠️ Fichier products.json non trouvé, seuls les utilisateurs ont été insérés\n";
    }
    
    echo "\n🎉 Seed terminé avec succès!\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}
