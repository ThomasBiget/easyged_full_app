<?php

require_once __DIR__ . '/../vendor/autoload.php';

// ✅ Charge les variables d'environnement depuis .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && $line[0] !== '#') {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

use App\Core\Router;
use App\Database\Database;
use App\Repository\InvoiceRepository;
use App\Repository\LineItemRepository;
use App\Repository\UserRepository;
use App\Services\JwtService;
use App\Services\InvoiceService;
use App\Services\AuthService;
use App\Services\SolrService;
use App\Services\OcrService;
use App\Services\DocumentService;
use App\Middleware\JwtMiddleware;
use App\Controllers\InvoiceController;
use App\Controllers\AuthController;
use App\Controllers\SearchController;
use App\Controllers\UploadController;

// ✅ HEADERS API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

/*
|--------------------------------------------------------------------------
| ✅ BOOTSTRAP APPLICATION
|--------------------------------------------------------------------------
*/

// ✅ 0. Configuration
$jwtSecret = $_ENV['JWT_SECRET'] ?? 'dev_secret_key_change_in_production';
$claudeApiKey = $_ENV['CLAUDE_API_KEY'] ?? '';

// ✅ 1. Une seule connexion PDO pour toute l'app
$db = Database::getInstance()->getConnection();

// ✅ 2. Repositories
$invoiceRepository = new InvoiceRepository($db);
$lineItemRepository = new LineItemRepository($db);
$userRepository = new UserRepository($db);

// ✅ 3. Services
$jwtService = new JwtService($jwtSecret);
$solrService = new SolrService();
$ocrService = new OcrService($claudeApiKey);
$documentService = new DocumentService();
$invoiceService = new InvoiceService(
    $db,
    $invoiceRepository,
    $lineItemRepository,
    $solrService
);
$authService = new AuthService($userRepository, $jwtService);

// ✅ 4. Middleware
$jwtMiddleware = new JwtMiddleware($jwtService);

// ✅ 5. Controllers
$invoiceController = new InvoiceController($invoiceService);
$authController = new AuthController($authService);
$searchController = new SearchController($solrService);
$uploadController = new UploadController($documentService, $ocrService, $invoiceService);

/*
|--------------------------------------------------------------------------
| ✅ ROUTER
|--------------------------------------------------------------------------
*/

$router = new Router($jwtMiddleware);

// Routes publiques (pas de JWT requis)
$router->post('/login', [$authController, 'login'], false);
$router->post('/register', [$authController, 'register'], false);

// Routes protégées (JWT requis)
$router->get('/invoices', [$invoiceController, 'index'], true);
$router->get('/invoices/show', [$invoiceController, 'show'], true);
$router->post('/invoices', [$invoiceController, 'store'], true);
$router->put('/invoices', [$invoiceController, 'update'], true);
$router->delete('/invoices', [$invoiceController, 'delete'], true);

$router->get('/search', [$searchController, 'search'], true);

// Routes Upload & OCR (protégées)
$router->post('/upload', [$uploadController, 'upload'], true);
$router->post('/upload/analyze', [$uploadController, 'analyze'], true);

$router->dispatch(
    $_SERVER['REQUEST_METHOD'],
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)
);
