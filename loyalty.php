<?php
/**
 * Customer Loyalty Dashboard
 * View points, rewards, and transaction history
 */

$pageTitle = "Loyalty Rewards - Mossé Luxe";
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/loyalty_functions.php';

// Require login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=loyalty.php');
    exit;
}

$conn = get_db_connection();
$user_id = $_SESSION['user_id'];

// Get loyalty stats
$stats = getLoyaltyStats($conn, $user_id);
if (!$stats) {
    // Create loyalty account
    awardSignupBonus($conn, $user_id);
    $stats = getLoyaltyStats($conn, $user_id);
}

// Get transaction history
$transactions = getLoyaltyTransactions($conn, $user_id, 20);

// Get tier benefits
$tier_benefits = getTierBenefits($stats['tier']);

require_once 'includes/header.php';
?>

<main class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">🎁 Loyalty Rewards</h1>
            <p class="text-gray-600">Earn points with every purchase and unlock exclusive rewards</p>
        </div>

        <!-- Points Balance Card -->
        <div class="bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-lg shadow-lg p-8 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-purple-200 text-sm mb-2">Your Points Balance</p>
                    <p class="text-5xl font-bold mb-2"><?php echo number_format($stats['points_balance']); ?></p>
                    <p class="text-purple-200">Worth R <?php echo number_format($stats['discount_value'], 2); ?> in rewards</p>
                </div>
                <div>
                    <p class="text-purple-200 text-sm mb-2">Member Tier</p>
                    <div class="flex items-center gap-2 mb-2">
                        <span class="text-3xl font-bold"><?php echo $stats['tier']; ?></span>
                        <?php if ($stats['tier'] === 'Platinum'): ?>
                            <span class="text-2xl">💎</span>
                        <?php elseif ($stats['tier'] === 'Gold'): ?>
                            <span class="text-2xl">🥇</span>
                        <?php elseif ($stats['tier'] === 'Silver'): ?>
                            <span class="text-2xl">🥈</span>
                        <?php else: ?>
                            <span class="text-2xl">🥉</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-purple-200"><?php echo $tier_benefits['multiplier']; ?>x points multiplier</p>
                </div>
                <div>
                    <p class="text-purple-200 text-sm mb-2">Lifetime Earned</p>
                    <p class="text-3xl font-bold mb-2"><?php echo number_format($stats['total_earned']); ?></p>
                    <p class="text-purple-200"><?php echo number_format($stats['total_redeemed']); ?> redeemed</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- How to Earn Points -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold mb-6">💰 How to Earn Points</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="bg-green-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Make a Purchase</h3>
                                <p class="text-sm text-gray-600">Earn <?php echo POINTS_PER_RAND; ?> point per R1 spent</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="bg-blue-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Write a Review</h3>
                                <p class="text-sm text-gray-600">Earn <?php echo REVIEW_BONUS; ?> points per review</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="bg-purple-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Refer a Friend</h3>
                                <p class="text-sm text-gray-600">Earn <?php echo REFERRAL_BONUS; ?> points per referral</p>
                            </div>
                        </div>

                        <div class="flex items-start gap-4 p-4 bg-gray-50 rounded-lg">
                            <div class="bg-yellow-100 p-3 rounded-full">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"></path>
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold mb-1">Birthday Bonus</h3>
                                <p class="text-sm text-gray-600">Special points on your birthday</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-2xl font-bold">📜 Transaction History</h2>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if (empty($transactions)): ?>
                            <div class="px-6 py-8 text-center text-gray-500">
                                <p>No transactions yet</p>
                                <p class="text-sm mt-2">Start shopping to earn points!</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($transactions as $transaction): ?>
                                <div class="px-6 py-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <p class="font-semibold"><?php echo htmlspecialchars($transaction['description']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo date('M d, Y H:i', strtotime($transaction['created_at'])); ?></p>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-lg font-bold <?php echo $transaction['transaction_type'] === 'earned' ? 'text-green-600' : 'text-red-600'; ?>">
                                                <?php echo $transaction['transaction_type'] === 'earned' ? '+' : ''; ?><?php echo number_format($transaction['points']); ?>
                                            </p>
                                            <span class="text-xs px-2 py-1 rounded-full <?php echo $transaction['transaction_type'] === 'earned' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                                <?php echo ucfirst($transaction['transaction_type']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Tier Benefits -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-xl font-bold mb-4">🌟 Your Benefits</h3>
                    <ul class="space-y-3">
                        <?php foreach ($tier_benefits['perks'] as $perk): ?>
                            <li class="flex items-start gap-2">
                                <svg class="w-5 h-5 text-green-500 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-sm"><?php echo htmlspecialchars($perk); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Redeem Points -->
                <div class="bg-gradient-to-br from-green-50 to-blue-50 rounded-lg shadow p-6 border-2 border-green-200">
                    <h3 class="text-xl font-bold mb-4">💳 Redeem Points</h3>
                    <p class="text-sm text-gray-600 mb-4">Use your points at checkout for instant discounts!</p>
                    <div class="bg-white rounded-lg p-4 mb-4">
                        <p class="text-sm text-gray-600 mb-1">Your points are worth:</p>
                        <p class="text-3xl font-bold text-green-600">R <?php echo number_format($stats['discount_value'], 2); ?></p>
                    </div>
                    <a href="shop.php" class="block w-full bg-black text-white text-center py-3 rounded-md font-semibold hover:bg-black/80 transition-colors">
                        Start Shopping
                    </a>
                </div>

                <!-- Next Tier Progress -->
                <?php
                $next_tier_points = 0;
                $next_tier_name = '';
                if ($stats['tier'] === 'Bronze') {
                    $next_tier_points = 500;
                    $next_tier_name = 'Silver';
                } elseif ($stats['tier'] === 'Silver') {
                    $next_tier_points = 2000;
                    $next_tier_name = 'Gold';
                } elseif ($stats['tier'] === 'Gold') {
                    $next_tier_points = 5000;
                    $next_tier_name = 'Platinum';
                }
                
                if ($next_tier_name):
                    $progress = ($stats['total_earned'] / $next_tier_points) * 100;
                    $remaining = $next_tier_points - $stats['total_earned'];
                ?>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-xl font-bold mb-4">🎯 Next Tier</h3>
                        <p class="text-sm text-gray-600 mb-2">Progress to <?php echo $next_tier_name; ?></p>
                        <div class="w-full bg-gray-200 rounded-full h-3 mb-2">
                            <div class="bg-gradient-to-r from-purple-600 to-blue-600 h-3 rounded-full transition-all" style="width: <?php echo min($progress, 100); ?>%"></div>
                        </div>
                        <p class="text-sm text-gray-600">
                            <?php echo number_format($remaining); ?> more points to unlock <?php echo $next_tier_name; ?>!
                        </p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<?php
require_once 'includes/footer.php';
?>
