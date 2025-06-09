<?php
session_start();
require_once 'db-connect.php';

// Check authentication with impersonation support
$userId = null;
$isImpersonated = false;

if (isset($_SESSION['impersonated_user_id'])) {
    // Admin is impersonating a user
    $userId = $_SESSION['impersonated_user_id'];
    $isImpersonated = true;
    $_SESSION['user_id'] = $userId; // Set user_id for full access
} elseif (isset($_SESSION['user_id'])) {
    // Normal user session
    $userId = $_SESSION['user_id'];
} else {
    // No session - redirect to user page without login if that's your requirement
    // Or you can remove this redirect completely if you want direct access
    header('Location: user-page.php'); // Change to your desired public page
    exit;
}

// Get user data using prepared statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("<h2>User not found</h2>");
}

// Get user assets from database (example implementation)
$assets = [
    'USDT' => ['available' => $user['usdt_balance'] ?? 0, 'price' => 1],
    'BTC' => ['available' => $user['btc_balance'] ?? 0, 'price' => 0],
    'ETH' => ['available' => $user['eth_balance'] ?? 0, 'price' => 0],
    'TRX' => ['available' => $user['trx_balance'] ?? 0, 'price' => 0]
];

// Try to get current prices (fallback to last known prices)
try {
    $priceStmt = $pdo->prepare("SELECT symbol, price FROM crypto_prices WHERE symbol IN ('BTC', 'ETH', 'TRX')");
    $priceStmt->execute();
    $prices = $priceStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    if (isset($prices['BTC'])) $assets['BTC']['price'] = $prices['BTC'];
    if (isset($prices['ETH'])) $assets['ETH']['price'] = $prices['ETH'];
    if (isset($prices['TRX'])) $assets['TRX']['price'] = $prices['TRX'];
} catch (PDOException $e) {
    // Use default prices if price lookup fails
    error_log("Price lookup failed: " . $e->getMessage());
}

// Calculate total balance
$totalBalance = 0;
foreach ($assets as $asset) {
    $totalBalance += $asset['available'] * $asset['price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <!-- [Keep all the same head content from previous version] -->
    <style>
        /* [Keep all the same styles from previous version] */
        
        /* Enhanced impersonation warning */
        .impersonation-warning {
            position: fixed;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(255, 77, 77, 0.3);
            border: 1px solid var(--danger);
            padding: 12px 24px;
            border-radius: 10px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            backdrop-filter: blur(5px);
            box-shadow: 0 4px 20px rgba(255, 77, 77, 0.2);
            animation: pulseWarning 1.5s infinite;
        }
        @keyframes pulseWarning {
            0% { box-shadow: 0 0 0 0 rgba(255, 77, 77, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255, 77, 77, 0); }
            100% { box-shadow: 0 0 0 0 rgba(255, 77, 77, 0); }
        }
        .impersonation-warning a {
            color: white;
            text-decoration: underline;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body onclick="closeMenuOutside(event)">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <!-- [Keep same loading overlay content] -->
    </div>

    <!-- Content Wrapper -->
    <div class="content-wrapper" id="contentWrapper">
        <div id="particles-js"></div>

        <!-- Enhanced Impersonation Warning -->
        <?php if ($isImpersonated): ?>
        <div class="impersonation-warning">
            <i class="bi bi-exclamation-triangle"></i>
            <span>ADMIN MODE: Viewing as <?php echo htmlspecialchars($user['username']); ?></span>
            <a href="end-impersonation.php">Return to Admin</a>
        </div>
        <?php endif; ?>

        <!-- [Keep all other HTML elements from previous version] -->
        
        <!-- Dashboard Content -->
        <div class="dashboard">
            <div class="profile-section">
                <h3>Dashboard</h3>
                <p><h1>Welcome, <span id="username" style="..."><?php echo htmlspecialchars($user['username']); ?></span></h1></p>
                <?php if ($isImpersonated): ?>
                <p style="color: var(--danger); font-size: 14px; margin-top: 5px;">
                    <i class="bi bi-shield-lock"></i> Admin Impersonation Active
                </p>
                <?php endif; ?>
            </div>

            <!-- [Keep all other dashboard content from previous version] -->
        </div>
    </div>

    <script>
    // [Keep all JavaScript from previous version but add impersonation checks]
    
    // Enhanced logout function
    function logout() {
        <?php if ($isImpersonated): ?>
            if (confirm('End impersonation and return to admin panel?')) {
                window.location.href = 'end-impersonation.php';
            }
        <?php else: ?>
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        <?php endif; ?>
    }

    // Disable certain actions during impersonation
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($isImpersonated): ?>
            // Disable financial buttons during impersonation
            document.querySelectorAll('.buttons button, .convert-button').forEach(btn => {
                btn.disabled = true;
                btn.style.opacity = '0.6';
                btn.style.cursor = 'not-allowed';
                btn.title = 'Disabled during impersonation';
            });
            
            // Add visual indicator to all action buttons
            const buttons = document.querySelectorAll('button, a[href^="javascript"]');
            buttons.forEach(btn => {
                if (!btn.disabled) {
                    btn.style.position = 'relative';
                    btn.style.overflow = 'hidden';
                    
                    const indicator = document.createElement('div');
                    indicator.style.position = 'absolute';
                    indicator.style.top = '0';
                    indicator.style.right = '0';
                    indicator.style.width = '8px';
                    indicator.style.height = '8px';
                    indicator.style.backgroundColor = 'var(--danger)';
                    indicator.style.borderRadius = '50%';
                    indicator.style.boxShadow = '0 0 5px var(--danger)';
                    btn.appendChild(indicator);
                }
            });
        <?php endif; ?>
    });
    </script>
</body>
</html>