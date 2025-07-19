# vuln-app

## Table des matières

1. [Démarrer le projet](#démarrer-le-projet)
2. [Les failles](#les-failles)
    2.1 [XSS](#xss-cross-site-scripting)
    2.2 [NoSQL](#injection-nosql)
    2.3 [LFI](#lfi-local-file-inclusion)
    2.4 [RCE](#rce-remote-code-execution)
    2.5 [JWT](#jwt-json-web-token)
    2.6 [CSRF](#csrf-cross-site-request-forgery)
3. [Les remédiations](#les-remediations)
    3.1 [XSS](#xss-cross-site-scripting)
    3.2 [NoSQL](#injection-nosql)
    3.3 [LFI](#lfi-local-file-inclusion)
    3.4 [RCE](#rce-remote-code-execution)
    3.5 [JWT](#jwt-json-web-token)
    3.6 [CSRF](#csrf-cross-site-request-forgery)

--

## Démarrer le projet

Après avoir cloner le projet, dans votre terminal, entrez les commandes suivantes :

```bash
./setup-https.sh

docker-compose down -v && docker-compose up --build -d

docker-compose exec web php seed.php

# docker-compose exec web composer install (en cas d'erreur vendor)
```

Aller sur l'url <https://localhost>

--

## Les failles

### XSS (Cross-Site Scripting)

**La cible** : `contact.php`

L'objectif ici est de permettre à un attaquant d’exécuter du JavaScript dans la réponse.
Ceci est possible car il est renvoyé sans filtrage ni `htmlspecialchars()`.

Exemple de test possible:

```HTML
<script>alert('XSS')</script>
<script>document.body.style.background = "red";</script>
```

### Injection NoSQL

**La cible** : `login.php`

L'objectif ici est de bypass la connexion, en injectant des requêtes NoSQL

Tester la faille en ajoutant dans les chanmps username et password :

```JSON
{"$ne": null}
```

Vous êtes maintenant connecté en tant qu'admin.
Ceci est possible car la collection `users`, aucun `username` ni `password` est `null`, ce qui connectera comme le premier utilisateur de notre base de données (dans notre cas, c'est le compte admin).

### LFI (Local File Inclusion)

**La cible** : `logviewer.php`

L'objectif ici est de permettre à un attaquant de lire n’importe quel fichier local via un paramètre manipulable.
Exemple typique t'attaque:

```Bash
logviewer.php?file=../../../../etc/passwd
```

Tester la faille comme ceci :

Cas normal : <https://localhost/logviewer.php?file=access.log>
Cas LFI : <https://localhost/logviewer.php?file=../../../../etc/passwd>

Ceci est possible car :

- Il y a aucun restriction aux fichiers du dossier `logs`
- Il y a aucun interdiction sur `..`, `/`, `php://`, etc.
- `basename()`n'est pas utiliser

### RCE (Remote Code Execution)

**La cible** : `upload.php`

L'objectif ici est de permettre à un attaquant d’uploader un fichier PHP malicieux (ex: `shell.php`) dans un dossier public, puis de l'exécuter depuis l'URL.
Un attaquant pourrait :

- lancer une reverse shell
- modifier des fichiers

Ceci est possible car :

- Les extensions ne sont pas filtrer (ex: `.jpg`, `.png`)
- Il y a aucun vérification du MIME avec `finfo_file`

Tester la faille comme ceci :

1. Créer un fichier `shell.php`

    ```PHP
    <?php
    if (isset($_GET['cmd'])) {
    system($_GET['cmd']);
    }
    ?>
    ```

2. Envoyer votre fichier sur <https://localhost/upload.php>
3. Aller ici <https://localhost/uploads/shell.php?cmd=whoami>

### JWT (JSON Web Token)

**Les cibles** : `login.php`

L'objectif ici est de montrer une mauvaise implémentation de JWT à cause :

- d'une clé secrète trop simple
- pas de vérification d'algorithme
- possibilité de falsifier un token (e.g. none ou brute-force)

Un attanquant peut brute force le token avec des outils comme [JWT Tool](https://github.com/ticarpi/jwt_tool) ou Burp, et signer son propre token avec la clé "123".

Tester la faille comme ceci :

1. Connectez-vous en tant que user simple (username: user et password: user123)
2. Récupérez le token créé dans les cookies du site web
3. Sur le site [10015.io](https://10015.io/tools/jwt-encoder-decoder)
    - Coller le token dans JWT Decoder
    - Modifier le payload dans JWT Encoder
    - Récuperer le nouveau token
4. Modifier votre token et recharger la page

Ceci est possible car :

- La clé secret est trop simple à deviner, idéalement il faut la générer avec `openssl rand -hex 64``
- Il manque un champ exp (expiration)

### CSRF (Cross-site Request Forgery)

**Les cibles** : `admin.php`

L'objectif ici est de permettre à un attaquant de forcer un utilisateur authentifié à effectuer une action à son insu (ex : changer un mot de passe, ajouter un utilisateur).

Ceci est possible car :

- Il n'y a pas de vérification de token CSRF dans les formulaires sensibles
- Les requêtes POST ne sont pas protégées contre les soumissions externes
- L'origin des requêtes (header Referer/Origin) ne sont pas vérifier

1. Connectez-vous en tant qu'admin (username: admin et password: admin123)
2. Cliquer sur "Exemple de faille CSRF" (lance csrf.html)

Comme vous êtes connecté, le formulaire sera soumis automatiquement et l'action sera réalisée.

--

## Les rémédiations

Toutes les vulnérabilités peuvent être activées ou désactivées en modifiant la variable `$VULNERABILITY` dans chaque fichier :
- `$VULNERABILITY = true` : Code vulnérable (démonstration)
- `$VULNERABILITY = false` : Code sécurisé (remédiation)

### XSS (Cross-Site Scripting)

**La cible** : `contact.php`

**Techniques de remédiation** :
- **Échappement HTML** : Utilisation de `htmlspecialchars($_POST['data'], ENT_QUOTES, 'UTF-8')`
- **Validation d'entrée** : Contrôle de la longueur des champs (max 100 chars nom, 1000 chars message)
- **Encodage de sortie** : Conversion des caractères spéciaux en entités HTML
- **Protection CSRF** : Token CSRF pour empêcher les soumissions externes

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans `contact.php`
2. Essayer d'injecter `<script>alert('XSS')</script>` dans le formulaire
3. Le script doit être affiché comme texte, pas exécuté

### Injection NoSQL

**La cible** : `login.php`

**Techniques de remédiation** :
- **Validation de type** : Vérification que les entrées sont des chaînes avec `is_string()`
- **Validation de format** : Regex `^[a-zA-Z0-9_]{3,20}$` pour le nom d'utilisateur
- **Requêtes paramétrées** : Utilisation de `['$eq' => $value]` pour forcer l'égalité stricte
- **Validation de longueur** : Mot de passe entre 6 et 100 caractères
- **Logging sécurisé** : Enregistrement des tentatives d'injection
- **Protection CSRF** : Token CSRF pour les formulaires de connexion

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans `login.php`
2. Essayer de se connecter avec `username={"$ne": null}&password={"$ne": null}`
3. La connexion doit échouer avec "Format d'entrée invalide"

### LFI (Local File Inclusion)

**La cible** : `logviewer.php`

**Techniques de remédiation** :
- **Liste blanche** : Seuls `access.log`, `error.log`, `app.log` sont autorisés
- **Validation stricte** : `in_array($file, $allowed_files, true)`
- **Nettoyage de chemin** : `basename()` pour supprimer les traversées de répertoire
- **Validation de chemin** : `realpath()` pour résoudre les liens symboliques
- **Confinement** : Vérification que le fichier reste dans `/logs/`
- **Gestion de taille** : Limite d'affichage pour les gros fichiers
- **Échappement HTML** : `htmlspecialchars()` sur le contenu affiché

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans `logviewer.php`
2. Essayer d'accéder à `?file=../../../../etc/passwd`
3. Doit afficher "Fichier non autorisé"

### RCE (Remote Code Execution)

**La cible** : `upload.php`

**Techniques de remédiation** :
- **Validation MIME** : Vérification du type réel avec `finfo_file()`
- **Liste blanche** : Seuls JPEG, PNG, GIF, WebP autorisés
- **Validation d'image** : `getimagesize()` pour confirmer que c'est une vraie image
- **Noms sécurisés** : Génération avec `uniqid()` et timestamp
- **Protection répertoire** : `.htaccess` pour désactiver l'exécution PHP
- **Permissions restrictives** : `chmod(0644)` sur les fichiers uploadés
- **Limite de taille** : Maximum 2MB par fichier
- **Protection CSRF** : Token CSRF pour les uploads
- **Logging sécurisé** : Enregistrement des uploads réussis

**Configuration `.htaccess` créée** :
```apache
php_flag engine off
RemoveHandler .php .phtml .php3 .php4 .php5 .php7
Options -Indexes
```

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans `upload.php`
2. Essayer d'uploader un fichier PHP déguisé en image
3. Doit rejeter avec "Le fichier n'est pas une image valide"

### JWT (JSON Web Token)

**Les cibles** : `login.php`

**Techniques de remédiation** :
- **Clé forte** : Utilisation de `$_ENV['JWT_SECRET']` ou génération aléatoire 64 chars
- **Claims complets** : `iss`, `aud`, `iat`, `nbf`, `exp`, `jti`, `sub`
- **Validation stricte** : Vérification de tous les claims obligatoires
- **Expiration** : Tokens valides 1 heure maximum
- **Algorithme forcé** : `JWT::$leeway = 0` pour éviter les attaques "none"
- **Cookie sécurisé** : `secure`, `httponly`, `samesite=Strict`
- **Validation IP** : Vérification de l'adresse IP dans le token
- **ID unique** : `jti` pour identifier chaque token

**Structure JWT sécurisée** :
```json
{
  "iss": "https://localhost",
  "aud": "https://localhost", 
  "iat": 1640995200,
  "nbf": 1640995200,
  "exp": 1640998800,
  "jti": "abc123...",
  "sub": "user_id",
  "username": "admin",
  "role": "admin",
  "ip": "127.0.0.1"
}
```

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans `login.php` et `admin.php`
2. Essayer de créer un token avec algorithme "none"
3. L'accès doit être refusé avec "Token invalide"

### CSRF (Cross-site Request Forgery)

**Les cibles** : `admin.php`

**Techniques de remédiation** :
- **Tokens CSRF** : Génération aléatoire 32 bytes par session
- **Validation stricte** : `hash_equals()` pour comparaison timing-safe
- **Champs cachés** : `<input type="hidden" name="csrf_token">`
- **Validation d'origine** : Vérification des headers `Origin`/`Referer`
- **Sessions sécurisées** : Configuration avec `httponly`, `secure`, `samesite`
- **Validation automatique** : Fonction `csrf_check()` sur toutes les requêtes POST
- **Gestion d'erreur** : Messages clairs en cas de token invalide

**Helper CSRF créé** : `csrf_helper.php`
```php
csrf_token()    // Génère un token
csrf_field()    // Crée le champ de formulaire  
csrf_check()    // Valide automatiquement
csrf_validate() // Validation manuelle
```

**Test de la remédiation** :
1. Changer `$VULNERABILITY = false` dans tous les fichiers
2. Essayer de soumettre un formulaire depuis un autre domaine
3. Doit rejeter avec "Token CSRF invalide"