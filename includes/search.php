<?php
session_start();
require_once('../config/database.php');
include('../includes/header.php');

// Handle direct GET requests from the search form
$initialSearch = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
?>

<div class="search-container">
    <form id="searchForm" class="search-form">
        <input type="text" 
               id="searchInput" 
               value="<?php echo $initialSearch; ?>"
               placeholder="Search by title, author, or ISBN...">
        <select id="categoryFilter">
            <option value="">All Categories</option>
            <?php
            $sql = "SELECT name FROM categories";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()) {
                echo "<option value='".$row['name']."'>".$row['name']."</option>";
            }
            ?>
        </select>
        <select id="genreFilter">
            <option value="">All Genres</option>
            <?php
            $sql = "SELECT name FROM genres";
            $result = $conn->query($sql);
            while($row = $result->fetch_assoc()) {
                echo "<option value='".$row['name']."'>".$row['name']."</option>";
            }
            ?>
        </select>
        <button type="submit">Search</button>
    </form>
    <div id="searchResults" class="search-results"></div>
</div>

<script>
// Trigger initial search if there's a search term
document.addEventListener('DOMContentLoaded', function() {
    const initialSearch = '<?php echo $initialSearch; ?>';
    if (initialSearch) {
        document.getElementById('searchInput').value = initialSearch;
        performSearch();
    }
});
</script>

<?php include('../includes/footer.php'); ?>