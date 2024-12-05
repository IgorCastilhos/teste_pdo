<?php
declare(strict_types=1);

require_once 'Database.php';

try {
    $db = new Database(
        hostname: '127.0.0.1',
        dbname: 'pdo_practice',
        username: 'root',
        password: 'secret'
    );

    // 1. Insert a new user
    $userData = [
        'name' => 'Alice Johnson 2',
        'email' => 'alice2@example.com',
        'password_hash' => password_hash('secure123', PASSWORD_DEFAULT)
    ];
    $userId = $db->insert('users', $userData);
    echo "Inserted user with ID: $userId\n<br>";

    // 2. Insert a new product
    $productData = [
        'name' => 'Tablet',
        'description' => 'Tablet with 10-inch display',
        'price' => 1200.00,
        'stock_quantity' => 5
    ];
    $productId = $db->insert('products', $productData);
    echo "Inserted product with ID: $productId\n<br>";

    // 3. Create a new order
    $orderData = [
        'user_id' => $userId,
        'total_amount' => 1200.00,
        'status' => 'Pending'
    ];
    $orderId = $db->insert('orders', $orderData);

    // 4. Add order item
    $orderItemData = [
        'order_id' => $orderId,
        'product_id' => $productId,
        'quantity' => 1,
        'price_per_unit' => 1200.00
    ];
    $orderItemId = $db->insert('order_items', $orderItemData);

    // 5. Select user with condition
    $users = $db->select('users', 'email = ?', ['alice@example.com']);
    foreach ($users as $user) {
        echo "Found user: {$user['name']} ({$user['email']})\n<br>";
    }

    // 6. Custom select for order details
    $customQuery = "
        SELECT o.id, u.name as user_name, o.total_amount, o.status,
               p.name as product_name, oi.quantity, oi.price_per_unit
        FROM orders o
        JOIN users u ON o.user_id = u.id
        JOIN order_items oi ON o.id = oi.order_id
        JOIN products p ON oi.product_id = p.id
        WHERE o.id = ?
    ";
    $orderDetails = $db->customSelect($customQuery, [$orderId]);
    foreach ($orderDetails as $detail) {
        echo "Order ID: {$detail['id']}\n<br>";
        echo "Customer: {$detail['user_name']}\n<br>";
        echo "Product: {$detail['product_name']}\n<br>";
        echo "Quantity: {$detail['quantity']}\n<br>";
        echo "Total: {$detail['total_amount']}\n<br>";
    }

    // 7. Update product stock
    $updateData = [
        'stock_quantity' => 4  // Decrease by 1
    ];
    $updateCondition = "id = ?";
    $db->update('products', $updateData, $updateCondition, [$productId]);

    // 8. Update order status
    $updateOrderData = [
        'status' => 'Completed'
    ];
    $db->update('orders', $updateOrderData, 'id = ?', [$orderId]);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n<br>";
    exit(1);
}