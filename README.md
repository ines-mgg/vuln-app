# vuln-app

## Table des matières

1. [Tuto: Comment sécuriser son serveur local ?](#tuto--comment-sécuriser-son-serveur-local-)
2. [Démarrer le projet](#démarrer-le-projet)
3. [Les failles](#les-failles)
    3.1 [XSS](#xss-cross-site-scripting)
    3.2 [NoSQL](#injection-nosql)
    3.3 [LFI](#lfi-local-file-inclusion)
    3.4 [RCE](#rce-remote-code-execution)
    3.5 [JWT](#jwt-json-web-token)
    3.6 [CSRF](#csrf-cross-site-request-forgery)

--

## Tuto : Comment sécuriser son serveur local ?

## Démarrer le projet

Après avoir cloner le projet, mettez le en place avec les commandes suivantes :

```Bash
cd vuln-app
docker-compose down -v && docker-compose up --build -d
# attention l'installation peut être longue la première fois
docker-compose exec web php seed.php # initialiser la base de données (une fois)
```

Aller sur l'url suivante : <http://localhost:8080/>

--

## Les failles

### XSS (Cross-Site Scripting)

La cible : contact.php

L'objectif ici est de permettre à un attaquant d’exécuter du JavaScript dans la réponse.
Ceci est possible car il est renvoyé sans filtrage ni `htmlspecialchars()`.

Exemple de test possible:

```HTML
<script>alert('XSS')</script>
<script>document.body.style.background = "red";</script>
```

### Injection NoSQL

La cible : login.php

L'objectif ici est de bypass la connexion, en injectant des requêtes NoSQL

Tester la faille en ajoutant dans les chanmps username et password :

```JSON
{"$ne": null}
```

Vous êtes maintenant connecté en tant qu'admin.
Ceci est possible car la collection `users`, aucun `username` ni `password` est `null`, ce qui connectera comme le premier utilisateur de notre base de données (dans notre cas, c'est le compte admin).

### LFI (Local File Inclusion)

La cible : logviewer.php

L'objectif ici est de permettre à un attaquant de lire n’importe quel fichier local via un paramètre manipulable.
Exemple typique t'attaque:

```Bash
logviewer.php?file=../../../../etc/passwd
```

Tester la faille comme ceci :

Cas normal : <http://localhost:8080/logviewer.php?file=access.log>
Cas LFI : <http://localhost:8080/logviewer.php?file=../../../../etc/passwd>

Ceci est possible car :

- Il y a aucun restriction aux fichiers du dossier `logs`
- Il y a aucun interdiction sur `..`, `/`, `php://`, etc.
- `basename()`n'est pas utiliser

### RCE (Remote Code Execution)

La cible : upload.php

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

2. Envoyer votre fichier sur <http://localhost:8080/upload.php>
3. Aller ici <http://localhost:8080/uploads/shell.php?cmd=whoami>

### JWT (JSON Web Token)

La cible : login.php

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

La cible : admin.php

L'objectif ici est de permettre à un attaquant de forcer un utilisateur authentifié à effectuer une action à son insu (ex : changer un mot de passe, ajouter un utilisateur).

Ceci est possible car :

- Il n'y a pas de vérification de token CSRF dans les formulaires sensibles
- Les requêtes POST ne sont pas protégées contre les soumissions externes
- L'origin des requêtes (header Referer/Origin) ne sont pas vérifier

1. Connectez-vous en tant qu'admin (username: admin et password: admin123)
2. Cliquer sur "Exemple de faille CSRF" (lance csrf.html)

Comme vous êtes connecté, le formulaire sera soumis automatiquement et l'action sera réalisée.
