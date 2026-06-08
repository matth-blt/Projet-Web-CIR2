# Projet Web IRVE Bretagne — Gestion de Bornes de Recharge

Ce projet a été réalisé dans le cadre du cursus CIN2 (ISEN Ouest) par **Matthieu Ballout** et **Noah Guichard**.
Il consiste en une application web complète permettant d'explorer, de visualiser et de gérer les infrastructures de recharge pour véhicules électriques (IRVE) en Bretagne.

---

## Fonctionnalités du Projet

### Front-office (Grand Public)
1. **Accueil (Tableau de bord)** :
   * Présentation textuelle du service.
   * Statistiques dynamiques issues de la base de données (nombre total de bornes, nombre d'aménageurs, nombre de types de prise).
   * Graphique linéaire Chart.js de la progression des installations par année.
   * Tableau de statistiques croisées montrant dynamiquement le nombre de points de charge par année et par département breton.
2. **Recherche de Bornes** :
   * Formulaire multicritères (Aménageur, Type de prise, Département).
   * Limitation automatique de la liste déroulante des aménageurs à 20 items choisis au hasard (`ORDER BY RAND() LIMIT 20`).
   * Tableau de résultats asynchrone (AJAX/JSON) affiché sur la même page sous forme paginée (100 par 100).
   * Affichage adaptatif (responsive) : le tableau se transforme automatiquement en fiches/cartes individuelles sur tablettes et smartphones (`max-width: 768px`).
3. **Carte Interactive (OpenStreetMap & Leaflet.js)** :
   * Formulaire à double filtre (Année, Département).
   * Rendu dynamique et performant des marqueurs (Bounding Box et Zoom) :
     * À bas niveau de zoom (zoom < 10), les points physiques sont regroupés et comptés par station pour économiser la mémoire.
     * À haut niveau de zoom (zoom >= 10), seuls les points de charge individuels situés dans la zone visible sont chargés et affichés.
   * Au clic sur un marqueur, la carte se recentre doucement (`flyTo`) et affiche une bulle d'informations contenant un lien vers la fiche détails de la borne.
   * Désélection visuelle au clic sur la carte ou fermeture de la bulle d'information.
4. **Fiche Détails** :
   * Page affichant toutes les informations d'une borne (coordonnées, opérateur, tarification, câble attaché, modes de paiement, gratuité).

### Back-office (Espace Administration)
Toutes les pages d'administration sont situées sous le préfixe `/back/` et protégées par une connexion sécurisée par session, hachage Bcrypt et jetons anti-CSRF :
1. **Authentification** : Synchronisation continue entre la session PHP et le `localStorage` du navigateur. Mots de passe sécurisés par l'algorithme Bcrypt.
2. **Accueil d'administration** : Tableau de synthèse listant les bornes avec liens de consultation, modification et suppression rapide.
3. **Visualisation complète (Liste)** : Consultation de l'ensemble des installations paginée 100 par 100 avec un paginateur d'ellipses.
4. **Création (Formulaire d'ajout)** : Insertion complète d'un nouveau point de charge avec gestion des transactions MySQL/MariaDB pour préserver l'intégrité de la base.
5. **Modification (Formulaire d'édition)** : Mise à jour des caractéristiques modifiables (Puissance, câble attaché, coordonnées et tarification).
6. **Suppression sécurisée** : Action de suppression effectuée uniquement via la méthode `POST` avec contrôle de jetons CSRF pour écarter les failles de sécurité. Supprime en cascade et en transaction les relations dépendantes avant d'effacer la borne.

---

## Architecture 

```
projet-cir2-31/
├── api/
│   ├── models/
│   │   ├── PointDeCharge.php
│   │   ├── Referentiel.php
│   │   └── Stats.php
│   ├── constants.php
│   ├── Database.php
│   └── request.php
├── back/
│   ├── assets/
│   │   └── img.png
│   ├── css/
│   │   └── style.css
│   ├── php/
│   │   ├── create.php
│   │   ├── delete.php
│   │   ├── detail.php
│   │   ├── edit.php
│   │   ├── footer.php
│   │   ├── footer.php
│   │   ├── header.php
│   │   └── liste.php
│   ├── auth.php
│   └── index.php
├── bdd/
│   ├── bdd.php
│   ├── bdd.sql
│   ├── communes-france-2024-limite.csv
│   ├── irve_init.csv
│   ├── mcd_projet_img_v5.png
│   ├── mcd_projet.mcd
│   └── mdp_projet_img_v5.png
├── css/
│   ├── carte.css
│   ├── common.css
│   ├── detail.css
│   ├── index.css
│   └── recherche.css
├── html/
│   ├── carte.html
│   ├── detail.html
│   └── recherche.html
├── js/
│   ├── carte.js
│   ├── detail.js
│   ├── recherche.js
│   └── statistics.js
├── .gitignore
├── index.html
└── README.md
```

---

## Spécifications techniques & Architecture

* **Front-end** : HTML5, CSS3 natif (variables CSS, flexbox, CSS grid, transitions animées, scroll-behavior smooth), JavaScript ES6.
* **Back-end & API** : PHP 8.x natif, structuré en programmation orientée objet via des classes de modèles de base de données.
* **Base de données** : MySQL / MariaDB 10, modélisée sous forme relationnelle normalisée (relations dédupliquées 1-N et N-N pour les types de prise et les modes de paiement).
* **Communication** : Requêtes asynchrones en format JSON via l'API Fetch.
* **Sécurité** : Hachage Bcrypt des identifiants administratifs, validation anti-CSRF et assainissement des entrées/sorties (barrière anti-XSS).

---

## Procédure d'installation & Déploiement

### 1. Prérequis sur la machine virtuelle (VM)
* Serveur web Apache 2 avec module `rewrite` activé.
* PHP 8.x.
* Serveur MySQL ou MariaDB.

### 2. Importation et peuplement de la base de données
1. Connectez-vous à votre gestionnaire de base de données et importez le script de structure SQL :
   ```bash
   mysql -u [utilisateur] -p[mot_de_passe]
   ```
   ```bash
   CREATE DATABASE irve CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE irve
   SOURCE /path/de/votre/fichier/bdd/bdd.sql
   ```
2. Modifier et executez le script d'importation PHP pour analyser les fichiers et peupler automatiquement les tables normalisées de la base de données :
   ```php
      $DB_USER = 'nom_utilisateur';
      $DB_PASSWORD = 'mot_de_passe';
      $DB_HOST = 'localhost';
      $DB_NAME = 'irve';
   ```
   ```bash
   php bdd/bdd.php
   ```

### 3. Configuration de l'accès à la base de données
Éditez le fichier `api/constants.php` pour renseigner vos identifiants d'accès locaux :
```php
   define('DB_USER', 'nom_utilisateur');
   define('DB_PASSWORD', 'mot_de_passe');
   define('DB_NAME', 'irve');
   define('DB_SERVER', 'localhost');
   define('DB_PORT', '3306');
```

### 4. Configuration du Virtual Host Apache
Créez et configurez un Virtual Host Apache pour faire correspondre l'URL `http://projet-cir2-31` en créant le fichier `projet-cir2-31.conf` dans `/etc/apache2/sites-available/` (ou autre path si pas sur linux) :
```apache
<VirtualHost *:80>
    ServerName projet-cir2-31
    DocumentRoot /var/www/projet-cir2-31

    <Directory /var/www/projet-cir2-31>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
        DirectoryIndex index.html
    </Directory>

    Alias /back /var/www/projet-cir2-31/back

    <Directory /var/www/projet-cir2-31/back>
        Options FollowSymLinks
        AllowOverride All
        Require all granted
	DirectoryIndex index.php
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/projet-cir2-31-error.log
    CustomLog ${APACHE_LOG_DIR}/projet-cir2-31-access.log combined
</VirtualHost>
```
```bash
sudo a2ensite projet-cir2-31
```

---

## 🔑 Identifiants d'administration (Back-office)
Pour accéder à l'interface d'administration sous l'URL `http://projet-cir2-31/back/`, utilisez l'un des comptes statiques :

| Identifiant | Mot de passe |
| :--- | :--- |
| **matthieu** | `irve_matthieu` |
| **noah** | `irve_noah` |

Ou alors modifier `auth.php` :
```php
$USERS = [
    'matthieu' => '$2y$12$wshtRls.2HJZ4CeSF/qBnuvhKOoFaoHQ/3mnAxBeEa2884dXKSAEG',
    'noah' => '$2y$12$TIBgf0gDMR1zeIxkQNEAUO40JSPxvwx1YTIyWxf.kQ6e9O1fUGMES',
    'nom_utilisateur' => password_hash('mot_de_passe', PASSWORD_BCRYPT)
];
```
(recommandé) Pour eviter les problèmes de sécurité créé un fichier `hash.php` : 
```php
$password = 'mot_de_passe';
echo password_hash($password, PASSWORD_BCRYPT);
```
```bash
php hash.php
```
Récupérer le mot de passe hasher et copier le dans le tableau de `auth.php`.