<?php
session_start();
include '../includes/config.php';

// Get search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Build the base query
$whereClause = '';
$params = [];
$types = '';

if (!empty($search)) {
    // Corrected WHERE clause to search across multiple columns
    $whereClause = "WHERE drugName LIKE ? OR drugCategory LIKE ? OR drugID LIKE ?";
    $searchTerm = "%{$search}%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $types = 'sss';
}

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM drug " . $whereClause;
$countStmt = $conn->prepare($countSql);
if (!empty($search)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalRows = $countStmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalRows / $limit);
$countStmt->close();

// Fetch drug with pagination
$sql = "SELECT * FROM drug " . $whereClause . " ORDER BY date_created DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

if (!empty($search)) {
    // Correctly bind all parameters: search terms first, then limit and offset
    $paramsForFetch = array_merge($params, [$limit, $offset]);
    $typesForFetch = $types . 'ii';
    $stmt->bind_param($typesForFetch, ...$paramsForFetch);
} else {
    // Standard binding without a search term
    $stmt->bind_param('ii', $limit, $offset);
}

$stmt->execute();
$result = $stmt->get_result();
$drugs = $result->fetch_all(MYSQLI_ASSOC); // Renamed variable to avoid conflict
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Drugs List</title>
    <link rel="stylesheet" href="../assets/css/bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/drugslist.css" type="text/css">
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Add basic styles for the spinner */
        .loading-spinner {
            display: none;
        }
    </style>
</head>

<body>
    <div class="main-content">
        <div class="page-header">
            <h1>All Health Products and Technologies</h1>
        </div>

        <div class="controls-section">
            <div class="search-container">
                <form action="view_other_drugs.php" method="GET" id="searchForm">
                    <input
                        type="text"
                        name="search"
                        id="searchInput"
                        class="search-input"
                        placeholder="Search drug by name, category, or ID..."
                        value="<?php echo htmlspecialchars($search); ?>"
                        autocomplete="off"
                    >
                    <!--<div>
                        <button type="submit" class="custom-search-btn"><i class="fas fa-search"></i></button>
                    </div>-->
                </form>
            </div>

            <a href="../pharmacy/add_other_drugs.php" class="add-product-btn">
                <i class="fas fa-plus"></i> Add New Product
            </a>
        </div>

        <div class="drug-container">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Drug ID</th>
                            <th>Category</th>
                            <th>Drug Name</th>
                            <th>Unit Price</th>
                            <th>Date Added</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="drugTableBody">
                        <?php if (empty($drugs)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="no-results">
                                        <i class="fas fa-search"></i>
                                        <h3>No drug Found</h3>
                                        <p>
                                            <?php echo !empty($search) ? "No drugs match your search criteria." : "No drugs available. Add your first product to get started."; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($drugs as $index => $drug): ?>
                                <tr class="table-row">
                                    <td><strong>#<?php echo htmlspecialchars($drug['drugID']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($drug['drugCategory'] ?? 'N/A'); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($drug['drugName']); ?></strong></td>
                                    <td><strong><?php echo htmlspecialchars($drug['price']); ?></strong></td>
                                    <td><?php echo date('M d, Y', strtotime($drug['date_created'])); ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-update" onclick="location.href='../pharmacy/update_drug.php?drugID=<?php echo $drug['drugID']; ?>'" title="Edit drug">
                                                <i class="fas fa-edit"></i>&nbsp;&nbsp; Edit
                                            </button>
                                            <button class="btn btn-delete" onclick="confirmDelete(<?php echo htmlspecialchars(json_encode($drug['drugID'])); ?>, '<?php echo htmlspecialchars(addslashes($drug['drugName'])); ?>')" title="Delete drug">
                                                Delete&nbsp;&nbsp;<i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if (!empty($drugs)): ?>
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $limit, $totalRows); ?> of <?php echo $totalRows; ?> drugs
                        <?php if (!empty($search)): ?>
                            for "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </div>

                    <div class="pagination-controls">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                <i class="fas fa-chevron-left"></i> Previous
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                <i class="fas fa-chevron-left"></i> Previous
                            </span>
                        <?php endif; ?>

                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);

                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>"
                               class="pagination-btn <?php echo ($i == $page) ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="pagination-btn">
                                Next <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-btn disabled">
                                Next <i class="fas fa-chevron-right"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="../assets/js/jquery-3.7.1.min.js"></script>
    <script>
        $(document).ready(function() {
            let searchTimeout;

            // Use a form submission to handle the search
            $('#searchForm').on('submit', function(e) {
                e.preventDefault();
                const searchTerm = $('#searchInput').val().trim();
                const url = new URL(window.location.href);
                url.searchParams.set('search', searchTerm);
                url.searchParams.set('page', 1);

                // Show spinner and navigate
                $('.search-icon').hide();
                $('.loading-spinner').show();
                window.location.href = url.toString();
            });

            // Handle the real-time search input
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().trim();
                clearTimeout(searchTimeout);

                if (searchTerm.length > 0) {
                    $('.search-icon').hide();
                    $('.loading-spinner').show();
                } else {
                    $('.search-icon').show();
                    $('.loading-spinner').hide();
                }

                searchTimeout = setTimeout(function() {
                    $('#searchForm').submit();
                }, 3000); // 500ms delay for search
            });

            // Hide spinner on page load
            $(window).on('load', function() {
                $('.loading-spinner').hide();
            });
        });

        function confirmDelete(drugID, drugName) {
            if (confirm(`Are you sure you want to delete "${drugName}"?\n\nThis action cannot be undone.`)) {
                window.location.href = `../pharmacy/delete_dda_drug.php?drugID=${drugID}`;
            }
        }
    </script>
</body>
</html>