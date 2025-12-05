# EasyGED - Gestion Électronique de Documents

Application de gestion de factures avec extraction automatique des données via OCR (Claude AI).

## 🎯 Objectif du projet

J'ai développé cette application pour démontrer mes compétences en développement backend PHP. L'idée est de proposer une solution complète permettant de :

- Uploader des factures (PDF/images)
- Extraire automatiquement les informations via l'API Claude (OCR)
- Stocker et gérer les factures
- Rechercher dans les factures via un moteur de recherche full-text (Apache Solr)

## 🏗️ Architecture

```
┌─────────────────┐     ┌─────────────────┐     ┌─────────────────┐
│   Frontend      │────▶│   API PHP       │────▶│   PostgreSQL    │
│   React.js      │     │   (REST)        │     │                 │
└─────────────────┘     └────────┬────────┘     └─────────────────┘
                                 │
                    ┌────────────┼────────────┐
                    ▼                         ▼
           ┌─────────────────┐     ┌─────────────────┐
           │   Claude AI     │     │   Apache Solr   │
           │   (OCR)         │     │   (Recherche)   │
           └─────────────────┘     └─────────────────┘
```

### Stack technique

| Composant | Technologie |
|-----------|-------------|
| Frontend | React.js |
| Backend | PHP 8.3 (vanilla, sans framework) |
| Base de données | PostgreSQL |
| Recherche full-text | Apache Solr 9 |
| OCR | API Claude (Anthropic) |
| Déploiement | Railway |

## 📁 Structure du projet

```
easyged/
├── api/                          # Backend PHP
│   ├── public/
│   │   └── index.php             # Point d'entrée (routeur)
│   ├── src/
│   │   ├── Controllers/          # Contrôleurs REST
│   │   ├── Services/             # Logique métier
│   │   ├── Repository/           # Accès aux données
│   │   ├── Models/               # Entités
│   │   ├── Middleware/           # JWT Authentication
│   │   ├── Core/                 # Router
│   │   └── Database/             # Connexion BDD (Singleton)
│   ├── database/
│   │   └── migrations/           # Scripts SQL
│   └── uploads/                  # Fichiers uploadés
│
└── front/                        # Frontend React
    └── src/
        ├── pages/                # Pages (Login, Dashboard)
        ├── components/           # Composants réutilisables
        └── api.js                # Client API
```

## 🔐 Authentification

J'ai implémenté une authentification JWT (JSON Web Token) :

1. **Login** : L'utilisateur envoie email/password → reçoit un token JWT
2. **Requêtes protégées** : Le token est envoyé dans le header `Authorization: Bearer <token>`
3. **Middleware** : Vérifie et décode le token avant chaque route protégée

```php
// Exemple de route protégée
$router->get('/invoices', [$invoiceController, 'index'], true); // true = protégée
```

## 🧠 Design Patterns utilisés

### Singleton (Database)
Une seule instance de connexion à la base de données pour toute l'application.

```php
class Database {
    private static ?Database $instance = null;
    
    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
}
```

### Repository Pattern
Séparation de la logique d'accès aux données.

### Service Layer
La logique métier est isolée dans des services (`InvoiceService`, `AuthService`, etc.).

### Dependency Injection
Les dépendances sont injectées via les constructeurs.

## 🔍 Recherche Full-Text (Solr)

Apache Solr indexe les factures pour permettre une recherche rapide :

- Recherche par fournisseur
- Recherche par numéro de facture
- Recherche dans le contenu des lignes de facture

```php
// Exemple de recherche
$results = $solrService->search("ACME");
```

## 🤖 OCR avec Claude AI

Quand un document est uploadé :

1. Le fichier est envoyé à l'API Claude
2. Claude analyse l'image/PDF et extrait les informations
3. Les données sont structurées et sauvegardées en BDD
4. La facture est indexée dans Solr

## 🚀 Déploiement

L'application est déployée sur Railway :

- **Frontend** : https://easyged.up.railway.app
- **API** : https://easygedfullapp-production.up.railway.app

### Variables d'environnement requises

```
DB_HOST=
DB_NAME=
DB_USER=
DB_PASSWORD=
JWT_SECRET=
CLAUDE_API_KEY=
SOLR_URL=
```

## 🛠️ Installation locale

### Prérequis
- PHP 8.3+
- Composer
- Node.js 18+
- PostgreSQL
- Apache Solr (optionnel)

### Backend

```bash
cd api
composer install
php -S localhost:8000 -t public
```

### Frontend

```bash
cd front
npm install
npm start
```

## 📝 API Endpoints

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/register` | Inscription | ❌ |
| POST | `/login` | Connexion | ❌ |
| GET | `/invoices` | Liste des factures | ✅ |
| GET | `/invoices/show?id=X` | Détail facture | ✅ |
| POST | `/invoices` | Créer facture | ✅ |
| DELETE | `/invoices?id=X` | Supprimer facture | ✅ |
| POST | `/upload/analyze` | Upload + OCR | ✅ |
| GET | `/search?q=X` | Recherche Solr | ✅ |

## 🎓 Ce que j'ai appris

- Architecture d'une API REST en PHP sans framework
- Implémentation de JWT from scratch
- Intégration d'un moteur de recherche (Solr)
- Utilisation d'une API d'IA pour l'OCR
- Déploiement containerisé sur le cloud


