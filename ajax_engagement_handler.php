<?php
require_once __DIR__ . '/includes/bootstrap.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid request.'];

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    $response['message'] = 'You must be logged in to use engagement features.';
    echo json_encode($response);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = filter_var($_POST['product_id'] ?? 0, FILTER_VALIDATE_INT);

    if (!validate_csrf_token()) {
        $response = ['success' => false, 'message' => 'Invalid security token.'];
        echo json_encode($response);
        exit;
    }

    switch ($action) {
        case 'set_price_alert':
            $alert_price = filter_var($_POST['alert_price'] ?? 0, FILTER_VALIDATE_FLOAT);
            if ($alert_price <= 0) {
                $response['message'] = 'Invalid price alert value.';
                echo json_encode($response);
                exit;
            }

            if (set_price_alert($user_id, $product_id, $alert_price)) {
                $response = [
                    'success' => true,
                    'message' => 'Price alert set! We\'ll notify you when the price drops below R' . number_format($alert_price, 2) . '.'
                ];
            } else {
                $response['message'] = 'Failed to set price alert.';
            }
            break;

        case 'remove_price_alert':
            if (remove_price_alert($user_id, $product_id)) {
                $response = ['success' => true, 'message' => 'Price alert removed.'];
            } else {
                $response['message'] = 'Failed to remove price alert.';
            }
            break;

        case 'get_price_alert':
            $alert_price = get_price_alert($user_id, $product_id);
            $response = ['success' => true, 'alert_price' => $alert_price];
            break;

        case 'set_back_in_stock_alert':
            $size_variant = $_POST['size_variant'] ?? null;
            $color_variant = $_POST['color_variant'] ?? null;

            $variant_text = [];
            if ($size_variant) $variant_text[] = "Size: $size_variant";
            if ($color_variant) $variant_text[] = "Color: $color_variant";
            $variant_display = !empty($variant_text) ? ' (' . implode(', ', $variant_text) . ')' : '';

            if (set_back_in_stock_alert($user_id, $product_id, $size_variant, $color_variant)) {
                $response = [
                    'success' => true,
                    'message' => 'Back-in-stock alert set!' . (empty($variant_display) ? '' : " For variant$variant_display.")
                ];
            } else {
                $response['message'] = 'Failed to set back-in-stock alert.';
            }
            break;

        case 'claim_loyalty_reward':
            $reward_id = (int) ($_POST['reward_id'] ?? 0);
            $points_required = (int) ($_POST['points_required'] ?? 0);

            if ($reward_id <= 0 || $points_required <= 0) {
                $response['message'] = 'Invalid reward information.';
                echo json_encode($response);
                exit;
            }

            if (spend_loyalty_points($user_id, $points_required, "Redeemed reward #$reward_id")) {
                $response = [
                    'success' => true,
                    'message' => 'Reward claimed successfully! ' . $points_required . ' points spent.'
                ];
            } else {
                $response['message'] = 'Insufficient points or reward unavailable.';
            }
            break;

        case 'get_loyalty_info':
            $loyalty_info = get_user_loyalty_info($user_id);
            if ($loyalty_info) {
                $response = ['success' => true, 'loyalty' => $loyalty_info];
            } else {
                $response['message'] = 'Failed to load loyalty information.';
            }
            break;

        case 'share_product':
            $platform = $_POST['platform'] ?? '';
            $content_id = (int) ($_POST['content_id'] ?? 0);

            $valid_platforms = ['facebook', 'twitter', 'instagram', 'whatsapp', 'linkedin'];
            if (!in_array($platform, $valid_platforms) || $content_id <= 0) {
                $response['message'] = 'Invalid sharing information.';
                echo json_encode($response);
                exit;
            }

            process_social_share_points($user_id, ucfirst($platform), $content_id);

            $share_points = (int) get_loyalty_setting('points_social_share');
            $response = [
                'success' => true,
                'message' => "Product shared on $platform!" .
                           ($share_points > 0 ? " Earned $share_points loyalty points!" : '')
            ];
            break;

        default:
            $response['message'] = 'Unknown action.';
            break;
    }
}

echo json_encode($response);
?>
