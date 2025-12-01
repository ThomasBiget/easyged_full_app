<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Database\Database;

$db = Database::getInstance()->getConnection();

echo "🚀 Création des tables...\n\n";

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            name VARCHAR(255),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✅ Table 'users' créée\n";
} catch (PDOException $e) {
    echo "❌ Erreur users : " . $e->getMessage() . "\n";
}

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS invoices (
            id SERIAL PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            supplier_name VARCHAR(255),
            invoice_number VARCHAR(100),
            invoice_date DATE,
            total_amount DECIMAL(10, 2),
            tva_amount DECIMAL(10, 2),
            tva_percentage DECIMAL(5, 2),
            status VARCHAR(50) DEFAULT 'pending',
            image_path VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✅ Table 'invoices' créée\n";
} catch (PDOException $e) {
    echo "❌ Erreur invoices : " . $e->getMessage() . "\n";
}

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS line_items (
            id SERIAL PRIMARY KEY,
            invoice_id INTEGER REFERENCES invoices(id) ON DELETE CASCADE,
            description TEXT,
            quantity DECIMAL(10, 2),
            unit_price DECIMAL(10, 2),
            total_price DECIMAL(10, 2),
            verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "✅ Table 'line_items' créée\n";
} catch (PDOException $e) {
    echo "❌ Erreur line_items : " . $e->getMessage() . "\n";
}

echo "\n🎉 Migrations terminées !\n";