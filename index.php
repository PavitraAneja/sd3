<?php

 session_start();
include('api/db.php'); 
include('includes/functions.php');
include('includes/pagination.php');

// include ('includes/db_local.php');  // Use local database settings
// include ('includes/functions.php');
// include ('includes/pagination.php');


ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$filter_sql = build_filter_query($_GET);

$count_sql = "SELECT COUNT(*) as total FROM rets_property_yu $filter_sql";
$count_result = $conn->query($count_sql);
$total = $count_result ? $count_result->fetch_assoc()['total'] : 0;


$sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude, 
        LA1_UserFirstName, LA1_UserLastName, LO1_OrganizationName, L_Status, created_at
        FROM rets_property_yu $filter_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

// Get the latest created_at from the entire table
// $latest_created_at = null;
// $latest_result = $conn->query('SELECT MAX(created_at) as latest_created_at FROM rets_property');
// if ($latest_result && ($row = $latest_result->fetch_assoc())) {
//     $latest_created_at = $row['latest_created_at'];
// }

// $sql = "SELECT L_ListingID, L_Address, L_City, L_State, L_Zip, L_SystemPrice, L_Keyword2, LM_Dec_3, LM_Int2_3, L_Photos, L_Remarks, LMD_MP_Latitude as Latitude, LMD_MP_Longitude as Longitude, 
//         LA1_UserFirstName, LA1_UserLastName, LO1_OrganizationName, L_Status, created_at
//         FROM rets_property $filter_sql ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

$result = $conn->query($sql);

$listings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $photos = [];
        if (!empty($row['L_Photos'])) {
            $photos = json_decode($row['L_Photos'], true);

            if (!is_array($photos)) $photos = [];
        }
        $firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';
        

//             if (!is_array($photos))
//                 $photos = [];
//         }
//         $firstPhoto = $photos[0] ?? 'https://via.placeholder.com/300x200?text=No+Photo';


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

// Collect unique cities from the entire property table
$cities = [];
$city_result = $conn->query("SELECT DISTINCT L_City FROM rets_property WHERE L_City IS NOT NULL AND L_City != '' ORDER BY L_City ASC");
if ($city_result) {
    while ($row = $city_result->fetch_assoc()) {
        $cities[] = $row['L_City'];
    }
}
sort($cities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>California Homes</title>

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

            grid-template-columns: repeat(6, 1fr); /* 5 filters + 1 button group */
            gap: 1rem;
            align-items: end;
        }
        .search-form .form-group {
            margin-bottom: 0;
        }
        .search-form .form-group.buttons {
            display: flex;
            flex-direction:row;
            gap: 1rem;
            align-items: end;
        }
        @media (max-width: 900px) {
            .search-form {
                grid-template-columns: 1fr 1fr 1fr;
            }
        }
        @media (max-width: 600px) {
            .search-form {
                grid-template-columns: 1fr;
            }
            .search-form .form-group.buttons {
                flex-direction: row;
                justify-content: flex-start;
            }
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
        <?php if (isset($_SESSION['user_id'])): ?>
            <li style="color: white; margin-right: 10px;">
                Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>
            </li>
            <li>
                <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
            </li>
        <?php else: ?>
            <li>
                <a href="login.php" class="btn btn-primary btn-sm">Login</a>
            </li>
        <?php endif; ?>

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

                    <select id="city" name="city">
                        <option value="">All Cities</option>
                        <?php foreach ($cities as $city): ?>
                            <option value="<?php echo htmlspecialchars(trim($city)); ?>" <?php if (isset($_GET['city']) && trim($_GET['city']) === trim($city)) echo 'selected'; ?>><?php echo htmlspecialchars(trim($city)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="min">Min Price</label>
                    <select id="min" name="min">
                        <?php
                        $min_price_options = [200000, 300000, 400000, 500000, 600000, 700000, 800000, 900000, 1000000];
                        foreach ($min_price_options as $price) {
                            $label = $price == 0 ? '$0' : ('$' . number_format($price / 1000, 0) . 'k');
                            echo '<option value="' . $price . '"' . (isset($_GET['min']) && $_GET['min'] == $price ? ' selected' : '') . '>' . $label . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="max">Max Price</label>
                    <select id="max" name="max">
                        <option value="" <?php echo (!isset($_GET['max']) || $_GET['max'] === '') ? 'selected' : ''; ?>>Not Limited</option>
                        <?php
                        $max_price_options = [300000, 400000, 500000, 600000, 700000, 800000, 900000, 1000000];
                        foreach ($max_price_options as $price) {
                            $label = '$' . number_format($price / 1000, 0) . 'k';
                            $selected = (isset($_GET['max']) && $_GET['max'] == $price) ? ' selected' : '';
                            echo "<option value=\"$price\"$selected>$label</option>";
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="beds">Min Beds</label>
                    <select id="beds" name="beds">
                        <option value="">Any</option>
                        <?php
                        $bed_options = [1, 2, 3, 4, 5];
                        foreach ($bed_options as $bed) {
                            echo '<option value="' . $bed . '"' . (isset($_GET['beds']) && $_GET['beds'] == $bed ? ' selected' : '') . '>' . $bed . '</option>';
                        }
                        echo '<option value="6+"' . (isset($_GET['beds']) && $_GET['beds'] == '6+' ? ' selected' : '') . '>6+</option>';
                        ?>
                    </select>                </div>
                <div class="form-group">
                    <label for="baths">Min Baths</label>
                    <select id="baths" name="baths">
                        <option value="">Any</option>
                        <?php
                        $bath_options = [1, 2, 3, 4, 5];
                        foreach ($bath_options as $bath) {
                            echo '<option value="' . $bath . '"' . (isset($_GET['baths']) && $_GET['baths'] == $bath ? ' selected' : '') . '>' . $bath . '</option>';
                        }
                        echo '<option value="6+"' . (isset($_GET['baths']) && $_GET['baths'] == '6+' ? ' selected' : '') . '>6+</option>';
                        ?>
                    </select>                </div>
                <div class="form-group buttons">
                    <button type="submit" class="search-btn">Search</button>
                    <button type="button" class="search-btn" style="background: #e2e8f0; color: #333;" id="clear-filters-btn">Clear</button>
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
                    <div class="stat-number">
                        <?php echo $latest_created_at ? date('M j, Y g:i A', strtotime($latest_created_at)) : 'N/A'; ?>
                    </div>
                    <div class="stat-label">Latest Listing Update</div>
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

                <?php foreach ($listings as $home): ?>

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
                <?php echo paginate($total, $limit, $page, '?' . http_build_query(array_merge($_GET, ['page' => null])) . '&'); ?>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php if (!empty($listings)): ?>
    <div id="map"></div>
    <?php endif; ?>

    <script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB0szWlIt9Vj26cM300wTcWxwL0ABHZ9HE
&loading=async&callback=initMap">
</script>
</body>
</html>


<script>
const listings = <?php echo json_encode($listings); ?>;

function initMap() {
    if (!listings.length) return;

    // Center map on the first property, or a default location
    const center = { 
        lat: parseFloat(listings[0].lat) || 36.7783, 
        lng: parseFloat(listings[0].lng) || -119.4179 
    };

    const map = new google.maps.Map(document.getElementById('map'), {
        zoom: 8,
        center: center
    });

    listings.forEach(home => {
        if (home.lat && home.lng) {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(home.lat), lng: parseFloat(home.lng) },
                map: map,
                title: home.address
            });

            const infoWindow = new google.maps.InfoWindow({
                content: `
                    <div style="max-width:200px;">
                        <strong>${home.address}</strong><br>
                        Price: $${home.price}<br>
                        Beds: ${home.beds}, Baths: ${home.baths}<br>
                        <img src="${home.photo}" alt="Photo" style="width:100%;margin-top:5px;">
                    </div>
                `
            });

            marker.addListener('click', function() {
                infoWindow.open(map, marker);
            });
        }
    });
}

// Initialize map after page load
window.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('map')) {
        initMap();
    }
});

document.getElementById('clear-filters-btn')?.addEventListener('click', function() {
    window.location.href = 'index.php';
});
</script>