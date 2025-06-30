<?php
include('includes/db_local.php');
include('includes/functions.php');
include('includes/pagination.php');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Build filter query
$where_conditions = [];
$params = [];
$param_types = "";

if (!empty($_GET['date_from'])) {
    $where_conditions[] = "OpenHouseDate >= ?";
    $params[] = $_GET['date_from'];
    $param_types .= "s";
}

if (!empty($_GET['date_to'])) {
    $where_conditions[] = "OpenHouseDate <= ?";
    $params[] = $_GET['date_to'];
    $param_types .= "s";
}

if (!empty($_GET['listing_id'])) {
    $where_conditions[] = "L_ListingID LIKE ?";
    $params[] = '%' . $_GET['listing_id'] . '%';
    $param_types .= "s";
}

$filter_sql = "";
if (!empty($where_conditions)) {
    $filter_sql = "WHERE " . implode(" AND ", $where_conditions);
}

// Get total count
$count_sql = "SELECT COUNT(*) as total FROM rets_openhouse $filter_sql";
if (!empty($params)) {
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->bind_param($param_types, ...$params);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
} else {
    $count_result = $conn->query($count_sql);
}

$total = $count_result ? $count_result->fetch_assoc()['total'] : 0;

// Get open house data
$sql = "SELECT L_ListingID, L_DisplayId, OpenHouseDate, OH_StartTime, OH_EndTime, 
        OH_StartDate, OH_EndDate, updated_date, created_at, all_data
        FROM rets_openhouse $filter_sql 
        ORDER BY OpenHouseDate ASC, OH_StartTime ASC 
        LIMIT $limit OFFSET $offset";

if (!empty($params)) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($param_types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $result = $conn->query($sql);
}

$openhouses = [];
if ($result) {
    while($row = $result->fetch_assoc()) {
        $all_data = json_decode($row['all_data'], true);
        
        $openhouses[] = [
            'listing_id' => $row['L_ListingID'],
            'display_id' => $row['L_DisplayId'],
            'date' => $row['OpenHouseDate'],
            'start_time' => $row['OH_StartTime'],
            'end_time' => $row['OH_EndTime'],
            'start_date' => $row['OH_StartDate'],
            'end_date' => $row['OH_EndDate'],
            'updated_date' => $row['updated_date'],
            'created_at' => $row['created_at'],
            'all_data' => $all_data,
            'remarks' => $all_data['OpenHouseRemarks'] ?? '',
            'status' => $all_data['OpenHouseStatus'] ?? '',
            'attended_by' => $all_data['OpenHouseAttendedBy'] ?? '',
            'refreshments' => $all_data['Refreshments'] ?? '',
            'showing_agent_first' => $all_data['ShowingAgentFirstName'] ?? '',
            'showing_agent_last' => $all_data['ShowingAgentLastName'] ?? '',
            'livestream_url' => $all_data['LivestreamOpenHouseURL'] ?? ''
        ];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Open Houses - SD3</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .header-actions {
            margin: 20px 0;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .header-actions a {
            margin-right: 15px;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .header-actions a:hover {
            background: #0056b3;
        }
        .stats {
            background: #e9ecef;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .openhouse-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        .openhouse-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .openhouse-date {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        .openhouse-time {
            font-size: 14px;
            color: #666;
        }
        .openhouse-details {
            margin-top: 10px;
        }
        .detail-row {
            display: flex;
            margin-bottom: 5px;
        }
        .detail-label {
            font-weight: bold;
            width: 120px;
            color: #555;
        }
        .detail-value {
            flex: 1;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .filters input, .filters button {
            margin-right: 10px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .filters button {
            background: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }
        .filters button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Open Houses</h1>
    
    <div class="header-actions">
        <a href="index.php">🔄 View Properties</a>
        <a href="openhouse.php">🏠 View Open Houses</a>
    </div>

    <div class="stats">
        <strong>Total Open Houses:</strong> <?php echo number_format($total); ?> | 
        <strong>Showing:</strong> <?php echo count($openhouses); ?> open houses
        <?php if (!empty($openhouses)): ?>
        | <strong>Date Range:</strong> <?php echo date('M j, Y', strtotime($openhouses[0]['date'])); ?> to <?php echo date('M j, Y', strtotime($openhouses[count($openhouses)-1]['date'])); ?>
        <?php endif; ?>
    </div>

    <form method="GET" class="filters">
        <input type="date" name="date_from" placeholder="From Date" value="<?php echo $_GET['date_from'] ?? ''; ?>">
        <input type="date" name="date_to" placeholder="To Date" value="<?php echo $_GET['date_to'] ?? ''; ?>">
        <input type="text" name="listing_id" placeholder="Listing ID" value="<?php echo $_GET['listing_id'] ?? ''; ?>">
        <button type="submit">Filter</button>
        <a href="openhouse.php" style="margin-left: 10px; color: #666;">Clear Filters</a>
    </form>

    <?php if (empty($openhouses)): ?>
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>No open houses found</h3>
            <p>Try running the sync script to import open houses from the Trestle API.</p>
            <a href="sync_openhouse.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;">Sync Open Houses</a>
        </div>
    <?php else: ?>
        <?php foreach($openhouses as $oh): ?>
        <div class="openhouse-card">
            <div class="openhouse-header">
                <div>
                    <div class="openhouse-date">
                        <?php echo date('l, F j, Y', strtotime($oh['date'])); ?>
                    </div>
                    <div class="openhouse-time">
                        <?php 
                        if ($oh['start_time'] && $oh['end_time']) {
                            echo date('g:i A', strtotime($oh['start_time'])) . ' - ' . date('g:i A', strtotime($oh['end_time']));
                        }
                        ?>
                    </div>
                </div>
                <div>
                    <?php if (!empty($oh['status'])): ?>
                    <span class="status-badge status-<?php echo strtolower($oh['status']); ?>">
                        <?php echo htmlspecialchars($oh['status']); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="openhouse-details">
                <div class="detail-row">
                    <span class="detail-label">Listing ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($oh['listing_id']); ?></span>
                </div>
                
                <?php if (!empty($oh['showing_agent_first']) || !empty($oh['showing_agent_last'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Showing Agent:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($oh['showing_agent_first'] . ' ' . $oh['showing_agent_last']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($oh['attended_by'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Attended By:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($oh['attended_by']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($oh['refreshments'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Refreshments:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($oh['refreshments']); ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($oh['remarks'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Remarks:</span>
                    <span class="detail-value"><?php echo htmlspecialchars(substr($oh['remarks'], 0, 200)); ?><?php echo strlen($oh['remarks']) > 200 ? '...' : ''; ?></span>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($oh['livestream_url'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Livestream:</span>
                    <span class="detail-value">
                        <a href="<?php echo htmlspecialchars($oh['livestream_url']); ?>" target="_blank">Watch Live</a>
                    </span>
                </div>
                <?php endif; ?>
                
                <div class="detail-row">
                    <span class="detail-label">Added:</span>
                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($oh['created_at'])); ?></span>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($total > $limit): ?>
        <?php echo paginate($total, $limit, $page, '?'.http_build_query(array_merge($_GET, ['page' => null])) . '&'); ?>
    <?php endif; ?>

</body>
</html> 