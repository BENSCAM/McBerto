# Déploiement de McBerto sur CloudPanel

Ce guide suppose : un VPS avec CloudPanel déjà installé, un accès SSH (root ou utilisateur sudo), et un nom de domaine pointant vers l'IP du VPS.

Remplace `mcberto.exemple.com` par ton vrai domaine partout ci-dessous.

## 1. Créer le site dans CloudPanel

1. CloudPanel → **Sites** → **Add Site** → **Create a PHP Site**
2. Domain Name : `mcberto.exemple.com`
3. Application : PHP
4. PHP Version : **8.2** ou supérieure (le projet requiert `^8.2`)
5. Valide la création — CloudPanel crée un utilisateur système dédié au site et un dossier `htdocs/mcberto.exemple.com/`

## 2. Créer la base de données

CloudPanel → **Databases** → **Add Database** :
- Note le nom de la base, l'utilisateur et le mot de passe générés — ils serviront dans le `.env` à l'étape 5.

## 3. Récupérer les accès SSH

CloudPanel → **Sites** → ton site → onglet **SSH/SFTP** (ou utilise l'utilisateur système créé à l'étape 1). Connecte-toi :

```bash
ssh <site-user>@<ip-du-vps>
```

## 4. Cloner le projet

```bash
cd ~/htdocs/mcberto.exemple.com
rm -rf * .[!.]*        # vide le dossier (CloudPanel y met un index.html par défaut)
git clone https://github.com/BENSCAM/McBerto.git .
```

## 5. Installer les dépendances PHP

```bash
composer install --no-dev --optimize-autoloader
```

(Si `composer` n'existe pas : `curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer`)

## 6. Installer Node.js et compiler les assets

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install 20
npm ci
npm run build
```

## 7. Configurer l'environnement

```bash
cp .env.example .env
nano .env
```

Renseigne au minimum :

```
APP_NAME=McBerto
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mcberto.exemple.com

# Le vrai compte Propriétaire — mets un mot de passe fort, pas celui de démo
ADMIN_NAME="Bertony Effa"
ADMIN_EMAIL=ton-vrai-email@exemple.com
ADMIN_PASSWORD=un-mot-de-passe-fort

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=<nom_base_étape_2>
DB_USERNAME=<utilisateur_étape_2>
DB_PASSWORD=<mot_de_passe_étape_2>
```

> **Important** — sans ces variables `ADMIN_*`, le seeder créera un compte de démo (`owner@mcberto.test` / `password`) qu'il ne faut jamais utiliser en production.

Pour que la réinitialisation de mot de passe fonctionne réellement (actuellement `MAIL_MAILER=log` n'envoie aucun email), configure aussi un vrai `MAIL_MAILER` (SMTP) — sinon un compte oublié ne pourra être réinitialisé que par le Propriétaire depuis la Gestion des utilisateurs.

## 8. Générer la clé, migrer, peupler

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

`--force` est nécessaire car `APP_ENV=production` bloque les commandes destructives par sécurité. Le seeder ne crée en production que ton compte Propriétaire réel et le catalogue de produits — **aucune fausse vente de démo**.

## 9. Optimiser pour la production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 10. Permissions

```bash
chmod -R 775 storage bootstrap/cache
```

## 11. Pointer le document root vers `public/`

Laravel sert son application depuis le sous-dossier `public/`, pas la racine du projet. Dans CloudPanel :

CloudPanel → **Sites** → ton site → onglet **Vhost** → modifie la ligne `root` :

```
root /home/<site-user>/htdocs/mcberto.exemple.com/public;
```

Sauvegarde — CloudPanel recharge Nginx automatiquement.

## 12. Activer le SSL

CloudPanel → **Sites** → ton site → **SSL/TLS** → **New Let's Encrypt Certificate** → Actionner. Gratuit et automatique, renouvellement géré par CloudPanel.

## 13. Vérifier

Visite `https://mcberto.exemple.com` — tu dois voir la page de connexion McBerto (pas la page Laravel par défaut). Connecte-toi avec le compte `ADMIN_EMAIL` / `ADMIN_PASSWORD` défini à l'étape 7.

## Mises à jour futures

À chaque nouvelle version à déployer :

```bash
cd ~/htdocs/mcberto.exemple.com
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

Le script `deploy.sh` à la racine du projet automatise ces étapes.
