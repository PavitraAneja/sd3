<?php
include('includes/db_local.php'); // Use local database settings
include('includes/functions.php');
include('includes/pagination.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$filter_sql = build_filter_query($_GET);

$count_sql = "SELECT COUNT(*) as total FROM rets_property $filter_sql";
$count_result = $conn->query($count_sql);
$total = $count_result ? $count_result->fetch_assoc()['total'] : 0;

$sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude, 
        LA1_UserFirstName, LA1_UserLastName, LO1_OrganizationName, L_Status, created_at
        FROM rets_property $filter_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$result = $conn->query($sql);

$listings = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $photos = [];
        if (!empty($row['L_Photos'])) {
            $photos = json_decode($row['L_Photos'], true);
            if (!is_array($photos)) $photos = [];
        }
        $firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';
        
        $listings[] = [
            'id' => $row['L_ListingID'] ?? '',
            'address' => ($row['L_Address'] ?? '') . ', ' . ($row['L_City'] ?? '') . ', ' . ($row['L_State'] ?? '') . ' ' . ($row['L_Zip'] ?? ''),
            'price' => isset($row['L_SystemPrice']) ? number_format($row['L_SystemPrice']) : 'N/A',
            'beds' => $row['L_Keyword2'] ?? '-',
            'baths' => $row['LM_Dec_3'] ?? '-',
            'sqft' => $row['LM_Int2_3'] ?? '-',
            'photo' => $firstPhoto,
            'lat' => $row['Latitude'] ?? null,
            'lng' => $row['Longitude'] ?? null,
            'remarks' => $row['L_Remarks'] ?? '',
            'agent_first' => $row['LA1_UserFirstName'] ?? '',
            'agent_last' => $row['LA1_UserLastName'] ?? '',
            'office' => $row['LO1_OrganizationName'] ?? '',
            'status' => $row['L_Status'] ?? '',
            'created_at' => $row['created_at'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>California Homes</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8fafc;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: bold;
        }

        .logo svg {
            height: 48px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }

        .logo svg * {
            fill: white !important;
        }

        .logo-img {
            height: 48px;
            width: auto;
            display: inline-block;
            vertical-align: middle;
        }

        nav ul {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        nav a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s;
        }

        nav a:hover {
            opacity: 0.8;
        }

        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 4rem 0;
            text-align: center;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            font-weight: 300;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        /* Search Form */
        .search-section {
            background: white;
            padding: 2rem 0;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            margin-top: -2rem;
            position: relative;
            z-index: 10;
        }

        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #555;
        }

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .search-btn:hover {
            transform: translateY(-2px);
        }

        /* Stats Section */
        .stats {
            background: white;
            padding: 1.5rem 0;
            border-bottom: 1px solid #e2e8f0;
            margin-top: 2rem;
        }

        .stats-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #666;
        }

        /* Results Section */
        .results-section {
            padding: 2rem 0;
        }

        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .results-count {
            font-size: 1.1rem;
            color: #666;
        }

        /* Property Grid */
        .property-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }

        .property-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
            text-decoration: none;
            color: inherit;
        }

        .property-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .property-image {
            width: 100%;
            height: 250px;
            position: relative;
            overflow: hidden;
        }

        .property-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .property-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #10b981;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .property-content {
            padding: 1.5rem;
        }

        .property-price {
            font-size: 1.5rem;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 0.5rem;
        }

        .property-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .property-location {
            color: #666;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .property-features {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #666;
        }

        .feature {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .agent-info {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .page-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            background: white;
            color: #666;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }

        .page-btn:hover,
        .page-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        /* Map */
        #map {
            height: 400px;
            margin-top: 2rem;
            border-radius: 12px;
            overflow: hidden;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 0;
            color: #666;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            margin-bottom: 2rem;
        }

        .sync-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }

        .sync-btn:hover {
            transform: translateY(-2px);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero h1 {
                font-size: 2rem;
            }

            .search-form {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 1rem;
            }

            nav ul {
                gap: 1rem;
            }

            .results-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }

            .property-grid {
                grid-template-columns: 1fr;
            }

            .stats-content {
                flex-direction: column;
                text-align: center;
            }
        }

        @media (max-width: 480px) {
            .container {
                padding: 0 15px;
            }

            .hero {
                padding: 2rem 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
            <div class="logo" style="display: flex; align-items: center; gap: 2px;">
                <img src="assets/white-logo.png" alt="California Homes Logo" style="height: 48px; width: auto; display: inline-block; vertical-align: middle;" />
                <div style="width:2px; height:32px; background:white; margin:0 10px; border-radius:2px;"></div>
                <span style="font-size: 1.5rem; font-weight: bold; color: white; margin-left: 0;">California Homes</span>
            </div>
                <nav>
                    <ul>
                        <li><a href="index.php">Homes for Sale</a></li>
                        <li><a href="openhouse.php">Open Houses</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <h1>Find Your Dream Home in California</h1>
            <p>Discover beautiful homes for sale across California's most desirable neighborhoods and communities.</p>
        </div>
    </section>

    <section class="search-section">
        <div class="container">
            <form method="GET" class="search-form">
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" placeholder="Enter city" value="<?php echo $_GET['city'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="min">Min Price</label>
                    <input type="number" id="min" name="min" placeholder="$0" value="<?php echo $_GET['min'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="max">Max Price</label>
                    <input type="number" id="max" name="max" placeholder="No limit" value="<?php echo $_GET['max'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="beds">Min Beds</label>
                    <input type="number" id="beds" name="beds" placeholder="Any" value="<?php echo $_GET['beds'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <label for="baths">Min Baths</label>
                    <input type="number" id="baths" name="baths" placeholder="Any" value="<?php echo $_GET['baths'] ?? ''; ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="search-btn">Search Properties</button>
                </div>
            </form>
        </div>
    </section>

    <section class="stats">
        <div class="container">
            <div class="stats-content">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($total); ?></div>
                    <div class="stat-label">Total Properties</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo count($listings); ?></div>
                    <div class="stat-label">Showing</div>
                </div>
                <?php if (!empty($listings)): ?>
                <div class="stat-item">
                    <div class="stat-number"><?php echo date('M j', strtotime($listings[0]['created_at'])); ?></div>
                    <div class="stat-label">Latest Update</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <section class="results-section">
        <div class="container">
            <div class="results-header">
                <div class="results-count">Showing <?php echo count($listings); ?> of <?php echo number_format($total); ?> properties</div>
            </div>

            <?php if (empty($listings)): ?>
            <div class="empty-state">
                <h3>No properties found</h3>
                <p>Try running the sync script to import properties from the Trestle API.</p>
                <a href="sync_properties.php" class="sync-btn">Sync Properties</a>
            </div>
            <?php else: ?>
            <div class="property-grid">
                <?php foreach($listings as $home): ?>
                <a href="property.php?id=<?php echo htmlspecialchars($home['id']); ?>" class="property-card">
                    <div class="property-image">
                        <img src="<?php echo htmlspecialchars($home['photo']); ?>" alt="California Home Photo">
                        <?php if (!empty($home['status'])): ?>
                        <div class="property-badge" style="background: <?php echo strtolower($home['status']) === 'active' ? '#10b981' : (strtolower($home['status']) === 'pending' ? '#f59e0b' : '#ef4444'); ?>">
                            <?php echo htmlspecialchars($home['status']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="property-content">
                        <div class="property-price">$<?php echo $home['price']; ?></div>
                        <div class="property-title"><?php echo htmlspecialchars($home['address']); ?></div>
                        <div class="property-location">
                            📍 <?php echo htmlspecialchars($home['address']); ?>
                        </div>
                        <div class="property-features">
                            <div class="feature">🛏️ <?php echo $home['beds']; ?> beds</div>
                            <div class="feature">🚿 <?php echo $home['baths']; ?> baths</div>
                            <div class="feature">📐 <?php echo $home['sqft']; ?> sqft</div>
                        </div>
                        
                        <?php if (!empty($home['agent_first']) || !empty($home['agent_last'])): ?>
                        <div class="agent-info">
                            <strong>Agent:</strong> <?php echo htmlspecialchars($home['agent_first'] . ' ' . $home['agent_last']); ?>
                            <?php if (!empty($home['office'])): ?>
                            <br><strong>Office:</strong> <?php echo htmlspecialchars($home['office']); ?>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>

            <?php if ($total > $limit): ?>
            <div class="pagination">
                <?php echo paginate($total, $limit, $page, '?'.http_build_query(array_merge($_GET, ['page' => null])) . '&'); ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($listings)): ?>
    <div id="map"></div>
    <?php endif; ?>

    <script>
    const listings = <?php echo json_encode($listings); ?>;
    </script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="js/scripts.js"></script>
</body>
</html>
