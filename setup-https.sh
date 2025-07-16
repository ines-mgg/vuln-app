#!/bin/bash

# Script simple pour configurer HTTPS localhost
set -e

echo "🔧 Configuration HTTPS pour localhost..."

# 1. Créer les répertoires nécessaires
mkdir -p nginx/ssl

# 2. Générer le certificat SSL auto-signé pour localhost
if [ ! -f "nginx/ssl/localhost.crt" ]; then
    echo "🔐 Génération du certificat SSL..."
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
        -keyout nginx/ssl/localhost.key \
        -out nginx/ssl/localhost.crt \
        -subj "/C=FR/ST=Paris/L=Paris/O=VulnApp/CN=localhost"
    echo "✅ Certificat SSL généré"
else
    echo "✅ Certificat SSL déjà présent"
fi

echo ""
echo "🚀 Configuration terminée !"
echo ""
echo "📋 Prochaines étapes:"
echo "1. Démarrer l'application: docker-compose up -d"
echo "2. Initialiser la DB: docker-compose exec web php seed.php"
echo "3. Ouvrir: https://localhost"
echo ""
echo "⚠️  Note: Acceptez l'avertissement de sécurité du navigateur (certificat auto-signé)"