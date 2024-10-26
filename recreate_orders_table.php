<?php
require_once '../includes/db_connect.php';

try {
    // Find foreign key constraints
    $stmt = $pdo->query("SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                         FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                         WHERE REFERENCED_TABLE_SCHEMA = 'online_bookstore'
                           AND REFERENCED_TABLE_NAME = 'orders'");
    $constraints = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Drop foreign key constraints
    foreach ($constraints as $constraint) {
        $pdo->exec("ALTER TABLE {$constraint['TABLE_NAME']} DROP FOREIGN KEY {$constraint['CONSTRAINT_NAME']}");
        echo "Dropped foreign key {$constraint['CONSTRAINT_NAME']} from table {$constraint['TABLE_NAME']}<br>";
    }

    // Drop the orders table
    $pdo->exec("DROP TABLE IF EXISTS orders");
    echo "Dropped orders table<br>";

    // Recreate the orders table
    $pdo->exec("CREATE TABLE orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        total_amount DECIMAL(10, 2) NOT NULL,
        discount_amount DECIMAL(10, 2) DEFAULT 0,
        status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )");
    echo "Recreated orders table<br>";

    // Recreate foreign key constraints
    foreach ($constraints as $constraint) {
        $pdo->exec("ALTER TABLE {$constraint['TABLE_NAME']}
                    ADD CONSTRAINT {$constraint['CONSTRAINT_NAME']}
                    FOREIGN KEY ({$constraint['COLUMN_NAME']}) 
                    REFERENCES orders({$constraint['REFERENCED_COLUMN_NAME']})");
        echo "Recreated foreign key {$constraint['CONSTRAINT_NAME']} on table {$constraint['TABLE_NAME']}<br>";
    }

    echo "Table 'orders' has been successfully recreated with all foreign key constraints.";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>