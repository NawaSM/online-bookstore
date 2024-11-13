<?php
session_start();
require_once('../../config/database.php');

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

error_reporting(E_ALL);
ini_set('display_errors', 0);

$searchTerm = $_GET['q'] ?? '';
$category = $_GET['category'] ?? '';
$genre = $_GET['genre'] ?? '';
$minPrice = $_GET['min_price'] ?? '';
$maxPrice = $_GET['max_price'] ?? '';
$inStock = $_GET['in_stock'] ?? '';
$isbn = $_GET['isbn'] ?? '';

try {
    $query = "SELECT * FROM inventory WHERE 1=1";
    $params = [];

    // Build query conditions
    if (!empty($searchTerm)) {
        $query .= " AND (book_name LIKE ? OR author LIKE ? OR isbn LIKE ?)";
        $searchTerm = "%$searchTerm%";
        array_push($params, $searchTerm, $searchTerm, $searchTerm);
    }

    if (!empty($category)) {
        $query .= " AND status = ?";
        $params[] = $category;
    }

    if (!empty($genre)) {
        $query .= " AND FIND_IN_SET(?, genres)";
        $params[] = $genre;
    }

    if (!empty($minPrice)) {
        $query .= " AND price >= ?";
        $params[] = $minPrice;
    }

    if (!empty($maxPrice)) {
        $query .= " AND price <= ?";
        $params[] = $maxPrice;
    }

    if ($inStock == '1') {
        $query .= " AND quantity > 0";
    }

    if (!empty($isbn)) {
        $query .= " AND isbn = ?";
        $params[] = $isbn;
    }

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $books = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $books[] = [
            'id' => $row['id'],
            'book_name' => $row['book_name'],
            'author' => $row['author'],
            'price' => number_format((float)$row['price'], 2, '.', ''),
            'special_price' => $row['special_price'] ? number_format((float)$row['special_price'], 2, '.', '') : null,
            'is_special' => $row['is_special'],
            'status' => $row['status'],
            'quantity' => $row['quantity'],
            'isbn' => $row['isbn'],
            'release_year' => $row['release_year'],
            'genres' => $row['genres'] ? explode(',', $row['genres']) : [],
            'category' => $row['category'],
            'image_data' => base64_encode($row['image_data']),
            'image_type' => $row['image_type']
        ];
    }

    echo json_encode(['books' => $books]);

} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['error' => 'Database error occurred']);
}
?>