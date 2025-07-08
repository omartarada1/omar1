<?php
session_start();
require_once '../config/database.php';

// Check authentication
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: index.php');
    exit();
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die('Database connection failed');
}

// Handle form submissions
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_pricing':
            try {
                $iphone_price = (float) $_POST['iphone_price'];
                $ipad_price = (float) $_POST['ipad_price'];
                $mac_price = (float) $_POST['mac_price'];
                
                $stmt = $db->prepare("UPDATE pricing SET price = ? WHERE device_type = ?");
                $stmt->execute([$iphone_price, 'iphone']);
                $stmt->execute([$ipad_price, 'ipad']);
                $stmt->execute([$mac_price, 'mac']);
                
                $message = 'Pricing updated successfully!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error updating pricing: ' . $e->getMessage();
                $message_type = 'error';
            }
            break;
            
        case 'update_wallets':
            try {
                $trc20_address = trim($_POST['trc20_address']);
                $erc20_address = trim($_POST['erc20_address']);
                
                $stmt = $db->prepare("UPDATE settings SET setting_value = ? WHERE setting_key = ?");
                $stmt->execute([$trc20_address, 'usdt_trc20_address']);
                $stmt->execute([$erc20_address, 'usdt_erc20_address']);
                
                $message = 'Wallet addresses updated successfully!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error updating wallet addresses: ' . $e->getMessage();
                $message_type = 'error';
            }
            break;
            
        case 'update_request_status':
            try {
                $request_id = (int) $_POST['request_id'];
                $new_status = $_POST['new_status'];
                $payment_status = $_POST['payment_status'];
                
                $stmt = $db->prepare("UPDATE unlock_requests SET status = ?, payment_status = ? WHERE id = ?");
                $stmt->execute([$new_status, $payment_status, $request_id]);
                
                $message = 'Request status updated successfully!';
                $message_type = 'success';
            } catch (Exception $e) {
                $message = 'Error updating request status: ' . $e->getMessage();
                $message_type = 'error';
            }
            break;
    }
}

// Get dashboard statistics
$stats = [];

// Total requests
$stmt = $db->query("SELECT COUNT(*) as total FROM unlock_requests");
$stats['total_requests'] = $stmt->fetch()['total'];

// Pending requests
$stmt = $db->query("SELECT COUNT(*) as pending FROM unlock_requests WHERE status = 'pending'");
$stats['pending_requests'] = $stmt->fetch()['pending'];

// Paid requests
$stmt = $db->query("SELECT COUNT(*) as paid FROM unlock_requests WHERE payment_status = 'paid'");
$stats['paid_requests'] = $stmt->fetch()['paid'];

// Total revenue
$stmt = $db->query("SELECT SUM(amount) as revenue FROM unlock_requests WHERE payment_status = 'paid'");
$stats['total_revenue'] = (float) $stmt->fetch()['revenue'];

// Get recent requests
$stmt = $db->query("
    SELECT id, device_type, imei_serial, email, amount, payment_method, 
           payment_status, status, created_at 
    FROM unlock_requests 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_requests = $stmt->fetchAll();

// Get current pricing
$stmt = $db->query("SELECT device_type, price FROM pricing");
$pricing = [];
while ($row = $stmt->fetch()) {
    $pricing[$row['device_type']] = $row['price'];
}

// Get current wallet addresses
$stmt = $db->query("
    SELECT setting_key, setting_value 
    FROM settings 
    WHERE setting_key IN ('usdt_trc20_address', 'usdt_erc20_address')
");
$wallets = [];
while ($row = $stmt->fetch()) {
    if ($row['setting_key'] === 'usdt_trc20_address') {
        $wallets['trc20'] = $row['setting_value'];
    } elseif ($row['setting_key'] === 'usdt_erc20_address') {
        $wallets['erc20'] = $row['setting_value'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - iCloud Unlock Pro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #333;
        }

        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            height: 100vh;
            background: #2c3e50;
            color: white;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid #34495e;
        }

        .sidebar-header h2 {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .sidebar-nav {
            padding: 1rem 0;
        }

        .nav-item {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            transition: background 0.3s ease;
            border-left: 3px solid transparent;
        }

        .nav-item:hover,
        .nav-item.active {
            background: #34495e;
            border-left-color: #3498db;
        }

        .nav-item i {
            margin-right: 0.75rem;
            width: 20px;
        }

        .main-content {
            margin-left: 250px;
            padding: 2rem;
            min-height: 100vh;
        }

        .header {
            background: white;
            padding: 1.5rem 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 1.8rem;
            color: #2c3e50;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logout-btn {
            background: #e74c3c;
            color: white;
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background 0.3s ease;
        }

        .logout-btn:hover {
            background: #c0392b;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card.total i { color: #3498db; }
        .stat-card.pending i { color: #f39c12; }
        .stat-card.paid i { color: #27ae60; }
        .stat-card.revenue i { color: #9b59b6; }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }

        .content-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .section-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #eee;
        }

        .section-header h2 {
            font-size: 1.3rem;
            color: #2c3e50;
        }

        .section-content {
            padding: 2rem;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-paid { background: #d4edda; color: #155724; }
        .status-failed { background: #f8d7da; color: #721c24; }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #229954;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .actions {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .main-content {
                margin-left: 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-shield-alt"></i> Admin Panel</h2>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-item active" data-section="dashboard">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </div>
            <div class="nav-item" data-section="requests">
                <i class="fas fa-list"></i> Unlock Requests
            </div>
            <div class="nav-item" data-section="pricing">
                <i class="fas fa-dollar-sign"></i> Pricing Management
            </div>
            <div class="nav-item" data-section="wallets">
                <i class="fab fa-bitcoin"></i> Wallet Addresses
            </div>
            <div class="nav-item" data-section="settings">
                <i class="fas fa-cog"></i> Settings
            </div>
        </nav>
    </div>

    <div class="main-content">
        <div class="header">
            <h1>Dashboard</h1>
            <div class="user-info">
                <span>Welcome, <?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?php echo $message_type; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
        <?php endif; ?>

        <!-- Dashboard Section -->
        <div class="content-section active" id="dashboard">
            <div class="stats-grid">
                <div class="stat-card total">
                    <i class="fas fa-clipboard-list"></i>
                    <div class="stat-value"><?php echo number_format($stats['total_requests']); ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card pending">
                    <i class="fas fa-clock"></i>
                    <div class="stat-value"><?php echo number_format($stats['pending_requests']); ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-card paid">
                    <i class="fas fa-check-circle"></i>
                    <div class="stat-value"><?php echo number_format($stats['paid_requests']); ?></div>
                    <div class="stat-label">Paid Requests</div>
                </div>
                <div class="stat-card revenue">
                    <i class="fas fa-dollar-sign"></i>
                    <div class="stat-value">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="content-section active">
                <div class="section-header">
                    <h2>Recent Requests</h2>
                </div>
                <div class="section-content">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Device</th>
                                <th>IMEI/Serial</th>
                                <th>Email</th>
                                <th>Amount</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_requests as $request): ?>
                            <tr>
                                <td>#<?php echo $request['id']; ?></td>
                                <td><?php echo ucfirst($request['device_type']); ?></td>
                                <td><?php echo htmlspecialchars($request['imei_serial']); ?></td>
                                <td><?php echo htmlspecialchars($request['email']); ?></td>
                                <td>$<?php echo number_format($request['amount'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $request['payment_status']; ?>"><?php echo ucfirst($request['payment_status']); ?></span></td>
                                <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                                <td><?php echo date('M j, Y', strtotime($request['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Requests Section -->
        <div class="content-section" id="requests">
            <div class="section-header">
                <h2>Unlock Requests Management</h2>
            </div>
            <div class="section-content">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Device</th>
                            <th>IMEI/Serial</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment Status</th>
                            <th>Request Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Get all requests for this section
                        $stmt = $db->query("
                            SELECT id, device_type, imei_serial, email, amount, 
                                   payment_method, payment_status, status, created_at 
                            FROM unlock_requests 
                            ORDER BY created_at DESC
                        ");
                        $all_requests = $stmt->fetchAll();
                        
                        foreach ($all_requests as $request):
                        ?>
                        <tr>
                            <td>#<?php echo $request['id']; ?></td>
                            <td><?php echo ucfirst($request['device_type']); ?></td>
                            <td><?php echo htmlspecialchars($request['imei_serial']); ?></td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td>$<?php echo number_format($request['amount'], 2); ?></td>
                            <td><span class="status-badge status-<?php echo $request['payment_status']; ?>"><?php echo ucfirst($request['payment_status']); ?></span></td>
                            <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                            <td><?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></td>
                            <td>
                                <div class="actions">
                                    <button class="btn btn-primary btn-sm" onclick="editRequest(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>', '<?php echo $request['payment_status']; ?>')">
                                        <i class="fas fa-edit"></i> Edit
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pricing Section -->
        <div class="content-section" id="pricing">
            <div class="section-header">
                <h2>Pricing Management</h2>
            </div>
            <div class="section-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_pricing">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="iphone_price">iPhone Unlock Price ($)</label>
                            <input type="number" step="0.01" id="iphone_price" name="iphone_price" value="<?php echo $pricing['iphone'] ?? 89; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ipad_price">iPad Unlock Price ($)</label>
                            <input type="number" step="0.01" id="ipad_price" name="ipad_price" value="<?php echo $pricing['ipad'] ?? 79; ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="mac_price">Mac Unlock Price ($)</label>
                            <input type="number" step="0.01" id="mac_price" name="mac_price" value="<?php echo $pricing['mac'] ?? 149; ?>" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Pricing
                    </button>
                </form>
            </div>
        </div>

        <!-- Wallets Section -->
        <div class="content-section" id="wallets">
            <div class="section-header">
                <h2>USDT Wallet Addresses</h2>
            </div>
            <div class="section-content">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_wallets">
                    <div class="form-group">
                        <label for="trc20_address">TRC20 (Tron) Wallet Address</label>
                        <input type="text" id="trc20_address" name="trc20_address" value="<?php echo htmlspecialchars($wallets['trc20'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="erc20_address">ERC20 (Ethereum) Wallet Address</label>
                        <input type="text" id="erc20_address" name="erc20_address" value="<?php echo htmlspecialchars($wallets['erc20'] ?? ''); ?>" required>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Update Wallet Addresses
                    </button>
                </form>
            </div>
        </div>

        <!-- Settings Section -->
        <div class="content-section" id="settings">
            <div class="section-header">
                <h2>System Settings</h2>
            </div>
            <div class="section-content">
                <p>Additional settings and configurations can be added here.</p>
                <div class="form-grid">
                    <div>
                        <h3>Payment Gateway Settings</h3>
                        <p>Configure Stripe and PayPal credentials in the database settings table.</p>
                    </div>
                    <div>
                        <h3>Email Settings</h3>
                        <p>Configure SMTP settings for email notifications in the database settings table.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Request Modal -->
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
        <div style="background: white; padding: 2rem; border-radius: 8px; width: 90%; max-width: 500px;">
            <h3>Edit Request Status</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_request_status">
                <input type="hidden" id="edit_request_id" name="request_id">
                <div class="form-group">
                    <label for="edit_status">Request Status</label>
                    <select id="edit_status" name="new_status" required>
                        <option value="pending">Pending</option>
                        <option value="processing">Processing</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_payment_status">Payment Status</label>
                    <select id="edit_payment_status" name="payment_status" required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div style="display: flex; gap: 1rem; margin-top: 2rem;">
                    <button type="submit" class="btn btn-success">Update</button>
                    <button type="button" class="btn btn-primary" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Navigation functionality
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', function() {
                // Remove active class from all nav items
                document.querySelectorAll('.nav-item').forEach(nav => nav.classList.remove('active'));
                
                // Add active class to clicked item
                this.classList.add('active');
                
                // Hide all content sections
                document.querySelectorAll('.content-section').forEach(section => {
                    section.classList.remove('active');
                });
                
                // Show selected section
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
                
                // Update header title
                const sectionName = this.textContent.trim();
                document.querySelector('.header h1').textContent = sectionName;
            });
        });

        // Edit request functionality
        function editRequest(id, status, paymentStatus) {
            document.getElementById('edit_request_id').value = id;
            document.getElementById('edit_status').value = status;
            document.getElementById('edit_payment_status').value = paymentStatus;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeEditModal();
            }
        });
    </script>
</body>
</html>