<?php

require_once 'config.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class ExportHandler {

    private static function sanitizeFilename($filename) {
        return preg_replace('/[^A-Za-z0-9\-_]/', '_', $filename);
    }

    public static function exportOrders($orders, $format = 'csv', $filename = null) {
        if (!$filename) {
            $filename = 'mosse_luxe_orders_' . date('Y-m-d');
        }

        if ($format === 'pdf') {
            self::exportOrdersPDF($orders, $filename);
        } else {
            self::exportOrdersCSV($orders, $filename);
        }
    }

    public static function exportCustomers($customers, $format = 'csv', $filename = null) {
        if (!$filename) {
            $filename = 'mosse_luxe_customers_' . date('Y-m-d');
        }

        if ($format === 'pdf') {
            self::exportCustomersPDF($customers, $filename);
        } else {
            self::exportCustomersCSV($customers, $filename);
        }
    }

    public static function exportSalesReport($data, $format = 'csv', $filename = null) {
        if (!$filename) {
            $filename = 'mosse_luxe_sales_report_' . date('Y-m-d');
        }

        if ($format === 'pdf') {
            self::exportSalesReportPDF($data, $filename);
        } else {
            self::exportSalesReportCSV($data, $filename);
        }
    }

    private static function exportOrdersCSV($orders, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Write header
        fputcsv($output, [
            'Order ID',
            'Customer Name',
            'Customer Email',
            'Order Date',
            'Total Price (R)',
            'Status',
            'Payment Method',
            'Discount Code',
            'Discount Amount'
        ]);

        // Write data
        foreach ($orders as $order) {
            fputcsv($output, [
                'ML-' . $order['id'],
                $order['customer_name'] ?? 'Guest',
                $order['customer_email'] ?? 'N/A',
                date('Y-m-d H:i:s', strtotime($order['created_at'] ?? $order['order_date'])),
                number_format($order['total_price'] ?? $order['total'], 2, '.', ''),
                $order['status'],
                $order['payment_method'] ?? 'PayFast',
                $order['discount_code'] ?? 'N/A',
                $order['discount_amount'] ? 'R ' . number_format($order['discount_amount'], 2) : 'R 0.00'
            ]);
        }

        fclose($output);
        exit();
    }

    private static function exportOrdersPDF($orders, $filename) {
        $html = self::generateOrdersPDFHTML($orders);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        echo $dompdf->output();
        exit();
    }

    private static function getLogoBase64() {
        $logoPath = ABSPATH . '/assets/images/logo-dark.png';
        if (file_exists($logoPath)) {
            $logoData = file_get_contents($logoPath);
            $base64 = base64_encode($logoData);
            return 'data:image/png;base64,' . $base64;
        }
        // Fallback to simple text logo
        return null;
    }

    private static function generateOrdersPDFHTML($orders) {
        $totalRevenue = array_sum(array_column($orders, 'total_price'));
        $logoSrc = self::getLogoBase64();

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Mossé Luxe Orders Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                .logo { max-width: 150px; height: auto; margin-bottom: 15px; }
                .logo-text { font-size: 24px; font-weight: bold; color: #000; margin-bottom: 15px; }
                .summary { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #000; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .total { font-weight: bold; font-size: 18px; margin-top: 20px; text-align: right; }
                .status { text-transform: capitalize; }
                .status.completed { color: green; }
                .status.pending { color: orange; }
                .status.failed { color: red; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">' .
                ($logoSrc ? '<img src="' . $logoSrc . '" alt="Mossé Luxe" class="logo" />' : '<div class="logo-text">Mossé Luxe</div>') . '
                <h1>Mossé Luxe Orders Report</h1>
                <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
            </div>

            <div class="summary">
                <h3>Report Summary</h3>
                <p><strong>Total Orders:</strong> ' . count($orders) . '</p>
                <p><strong>Total Revenue:</strong> R ' . number_format($totalRevenue, 2) . '</p>
                <p><strong>Report Period:</strong> All Time</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Email</th>
                        <th>Order Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Discount</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orders as $order) {
            $statusClass = strtolower($order['status']);
            $html .= '
                    <tr>
                        <td>ML-' . $order['id'] . '</td>
                        <td>' . htmlspecialchars($order['customer_name'] ?? 'Guest') . '</td>
                        <td>' . htmlspecialchars($order['customer_email'] ?? 'N/A') . '</td>
                        <td>' . date('Y-m-d H:i', strtotime($order['created_at'] ?? $order['order_date'])) . '</td>
                        <td>R ' . number_format($order['total_price'] ?? $order['total'], 2) . '</td>
                        <td class="status ' . $statusClass . '">' . htmlspecialchars($order['status']) . '</td>
                        <td>' . ($order['payment_method'] ?? 'PayFast') . '</td>
                        <td>' . ($order['discount_code'] ? htmlspecialchars($order['discount_code']) . ' (-R ' . number_format($order['discount_amount'], 2) . ')' : 'None') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>Mossé Luxe - Luxury Streetwear</p>
                <p>Report generated by admin panel - ' . SITE_URL . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    private static function exportCustomersCSV($customers, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        fputcsv($output, [
            'Customer ID',
            'Name',
            'Email',
            'Phone',
            'Registration Date',
            'Total Orders',
            'Total Spent',
            'Last Order Date'
        ]);

        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['name'],
                $customer['email'],
                $customer['phone'] ?? 'N/A',
                date('Y-m-d', strtotime($customer['created_at'])),
                $customer['total_orders'] ?? 0,
                'R ' . number_format($customer['total_spent'] ?? 0, 2),
                $customer['last_order'] ? date('Y-m-d', strtotime($customer['last_order'])) : 'Never'
            ]);
        }

        fclose($output);
        exit();
    }

    private static function exportCustomersPDF($customers, $filename) {
        $html = self::generateCustomersPDFHTML($customers);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        echo $dompdf->output();
        exit();
    }

    private static function generateCustomersPDFHTML($customers) {
        $totalCustomers = count($customers);
        $activeCustomers = count(array_filter($customers, fn($c) => ($c['total_orders'] ?? 0) > 0));
        $logoSrc = self::getLogoBase64();

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Mossé Luxe Customers Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                .logo { max-width: 150px; height: auto; margin-bottom: 15px; }
                .logo-text { font-size: 24px; font-weight: bold; color: #000; margin-bottom: 15px; }
                .summary { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #000; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">' .
                ($logoSrc ? '<img src="' . $logoSrc . '" alt="Mossé Luxe" class="logo" />' : '<div class="logo-text">Mossé Luxe</div>') . '
                <h1>Mossé Luxe Customers Report</h1>
                <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
            </div>

            <div class="summary">
                <h3>Report Summary</h3>
                <p><strong>Total Customers:</strong> ' . $totalCustomers . '</p>
                <p><strong>Active Customers:</strong> ' . $activeCustomers . '</p>
                <p><strong>Report Period:</strong> All Time</p>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Joined</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Last Order</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($customers as $customer) {
            $html .= '
                    <tr>
                        <td>' . $customer['id'] . '</td>
                        <td>' . htmlspecialchars($customer['name']) . '</td>
                        <td>' . htmlspecialchars($customer['email']) . '</td>
                        <td>' . htmlspecialchars($customer['phone'] ?? 'N/A') . '</td>
                        <td>' . date('Y-m-d', strtotime($customer['created_at'])) . '</td>
                        <td>' . ($customer['total_orders'] ?? 0) . '</td>
                        <td>R ' . number_format($customer['total_spent'] ?? 0, 2) . '</td>
                        <td>' . ($customer['last_order'] ? date('Y-m-d', strtotime($customer['last_order'])) : 'Never') . '</td>
                    </tr>';
        }

        $html .= '
                </tbody>
            </table>

            <div class="footer">
                <p>Mossé Luxe - Luxury Streetwear</p>
                <p>Report generated by admin panel - ' . SITE_URL . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    private static function exportSalesReportCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');

        $output = fopen('php://output', 'w');

        // Summary section
        fputcsv($output, ['Export Type', 'Sales Report']);
        fputcsv($output, ['Generated', date('Y-m-d H:i:s')]);
        fputcsv($output, ['Period', $data['period']]);
        fputcsv($output, ['Total Sales', 'R ' . number_format($data['total_sales'], 2)]);
        fputcsv($output, ['Total Orders', $data['total_orders']]);
        fputcsv($output, ['Average Order Value', 'R ' . number_format($data['avg_order_value'], 2)]);
        fputcsv($output, []);

        // Orders by status
        fputcsv($output, ['Orders by Status']);
        fputcsv($output, ['Status', 'Count', 'Percentage']);
        foreach ($data['orders_by_status'] as $status => $count) {
            fputcsv($output, [
                ucfirst($status),
                $count,
                number_format(($count / $data['total_orders']) * 100, 1) . '%'
            ]);
        }
        fputcsv($output, []);

        // Daily sales
        fputcsv($output, ['Daily Sales']);
        fputcsv($output, ['Date', 'Orders', 'Revenue']);
        foreach ($data['daily_sales'] as $day) {
            fputcsv($output, [
                $day['date'],
                $day['orders'],
                'R ' . number_format($day['revenue'], 2)
            ]);
        }
        fputcsv($output, []);

        // Top products
        fputcsv($output, ['Top Products']);
        fputcsv($output, ['Product', 'Units Sold', 'Revenue']);
        foreach ($data['top_products'] as $product) {
            fputcsv($output, [
                $product['name'],
                $product['total_sold'],
                'R ' . number_format($product['total_revenue'], 2)
            ]);
        }

        fclose($output);
        exit();
    }

    private static function exportSalesReportPDF($data, $filename) {
        $html = self::generateSalesReportPDFHTML($data);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '.pdf"');
        echo $dompdf->output();
        exit();
    }

    private static function generateSalesReportPDFHTML($data) {
        $logoSrc = self::getLogoBase64();

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Mossé Luxe Sales Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 20px; }
                .logo { max-width: 150px; height: auto; margin-bottom: 15px; }
                .logo-text { font-size: 24px; font-weight: bold; color: #000; margin-bottom: 15px; }
                .summary { background: #f5f5f5; padding: 15px; margin: 20px 0; border-radius: 5px; }
                .metrics { display: flex; justify-content: space-around; flex-wrap: wrap; margin: 20px 0; }
                .metric { text-align: center; padding: 15px; background: white; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); min-width: 150px; margin: 10px; }
                .metric h3 { margin: 0; color: #000; font-size: 24px; }
                .metric p { margin: 5px 0 0 0; color: #666; font-size: 14px; }
                .table-container { margin: 30px 0; }
                h2 { color: #000; border-bottom: 1px solid #ddd; padding-bottom: 10px; }
                table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                th { background-color: #000; color: white; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 40px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">' .
                ($logoSrc ? '<img src="' . $logoSrc . '" alt="Mossé Luxe" class="logo" />' : '<div class="logo-text">Mossé Luxe</div>') . '
                <h1>Mossé Luxe Sales Report</h1>
                <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
                <p><strong>Period:</strong> ' . $data['period'] . '</p>
            </div>

            <div class="metrics">
                <div class="metric">
                    <h3>R ' . number_format($data['total_sales'], 2) . '</h3>
                    <p>Total Sales</p>
                </div>
                <div class="metric">
                    <h3>' . $data['total_orders'] . '</h3>
                    <p>Total Orders</p>
                </div>
                <div class="metric">
                    <h3>R ' . number_format($data['avg_order_value'], 2) . '</h3>
                    <p>Avg Order Value</p>
                </div>
            </div>

            <div class="table-container">
                <h2>Orders by Status</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Count</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($data['orders_by_status'] as $status => $count) {
            $html .= '
                        <tr>
                            <td>' . ucfirst($status) . '</td>
                            <td>' . $count . '</td>
                            <td>' . number_format(($count / $data['total_orders']) * 100, 1) . '%</td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <h2>Daily Sales</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($data['daily_sales'] as $day) {
            $html .= '
                        <tr>
                            <td>' . $day['date'] . '</td>
                            <td>' . $day['orders'] . '</td>
                            <td>R ' . number_format($day['revenue'], 2) . '</td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>

            <div class="table-container">
                <h2>Top Products</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Units Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($data['top_products'] as $product) {
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars($product['name']) . '</td>
                            <td>' . $product['total_sold'] . '</td>
                            <td>R ' . number_format($product['total_revenue'], 2) . '</td>
                        </tr>';
        }

        $html .= '
                    </tbody>
                </table>
            </div>

            <div class="footer">
                <p>Mossé Luxe - Luxury Streetwear</p>
                <p>Report generated by admin panel - ' . SITE_URL . '</p>
            </div>
        </body>
        </html>';

        return $html;
    }
}

// No closing PHP tag - prevents accidental whitespace output