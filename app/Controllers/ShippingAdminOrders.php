<?php

namespace App\Controllers;

class ShippingAdminOrders extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Admin order listing.
     */
    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));
        $orderStatus = trim((string) $this->request->getGet('order_status'));
        $paymentStatus = trim((string) $this->request->getGet('payment_status'));

        // Aggregate order items separately to avoid duplicate totals
        // when an order contains products from multiple sellers.
        $itemSummary = '(
            SELECT
                oi.order_id,
                COUNT(oi.id) AS item_lines,
                SUM(oi.quantity) AS total_quantity,
                COUNT(DISTINCT p.seller_id) AS seller_count
            FROM ajobmart_order_items oi
            LEFT JOIN ajobmart_products p ON p.id = oi.product_id
            GROUP BY oi.order_id
        ) item_summary';

        $fulfillmentSummary = '(
            SELECT
                order_id,
                GROUP_CONCAT(DISTINCT seller_status ORDER BY seller_status SEPARATOR ", ") AS seller_statuses
            FROM ajobmart_seller_order_fulfillments
            GROUP BY order_id
        ) fulfillment_summary';

        $bookingSummary = '(
            SELECT
                order_id,
                GROUP_CONCAT(DISTINCT admin_status ORDER BY admin_status SEPARATOR ", ") AS booking_statuses
            FROM ajobmart_shipping_label_bookings
            GROUP BY order_id
        ) booking_summary';

        $builder = $this->db->table('ajobmart_orders o');

        $builder->select([
            'o.*',
            'u.name AS customer_name',
            'u.mobile AS customer_mobile',
            'u.email AS customer_email',
            'COALESCE(item_summary.item_lines, 0) AS item_lines',
            'COALESCE(item_summary.total_quantity, 0) AS total_quantity',
            'COALESCE(item_summary.seller_count, 0) AS seller_count',
            'fulfillment_summary.seller_statuses',
            'booking_summary.booking_statuses',
        ]);

        $builder->join('ajobmart_users u', 'u.id = o.user_id', 'left');
        $builder->join($itemSummary, 'item_summary.order_id = o.id', 'left', false);
        $builder->join($fulfillmentSummary, 'fulfillment_summary.order_id = o.id', 'left', false);
        $builder->join($bookingSummary, 'booking_summary.order_id = o.id', 'left', false);

        if ($search !== '') {
            $builder->groupStart()
                ->like('o.order_number', $search)
                ->orLike('u.name', $search)
                ->orLike('u.mobile', $search)
                ->orLike('u.email', $search)
                ->groupEnd();
        }

        if ($orderStatus !== '') {
            $builder->where('o.order_status', $orderStatus);
        }

        if ($paymentStatus !== '') {
            $builder->where('o.payment_status', $paymentStatus);
        }

        $builder->orderBy('o.id', 'DESC');

        $perPage = 20;
$page = max(1, (int) $this->request->getGet('page_orders'));

// Count total matching orders without modifying the original builder.
$countBuilder = clone $builder;
$total = $countBuilder->countAllResults();

// Fetch current page.
$orders = $builder
    ->get($perPage, ($page - 1) * $perPage)
    ->getResultArray();

// Generate pagination links using the "orders" group.
$pager = service('pager');

$pagerLinks = $pager->makeLinks(
    $page,
    $perPage,
    $total,
    'default_full',
    0,
    'orders'
);

        return view('shipping_admin/orders/index', [
            'pageTitle' => 'All Orders',
            'orders' => $orders,
            'pagerLinks' => $pagerLinks,
            'search' => $search,
            'orderStatus' => $orderStatus,
            'paymentStatus' => $paymentStatus,
        ]);
    }

    /**
     * Admin order details.
     */
    public function details($orderId)
    {
        $orderId = (int) $orderId;

        if ($orderId < 1) {
            return redirect()->to(site_url('shipping-admin/orders'))
                ->with('error', 'Invalid order ID.');
        }

        // Main order and customer information.
        $order = $this->db->table('ajobmart_orders o')
            ->select([
                'o.*',
                'u.name AS customer_name',
                'u.user_id AS customer_user_id',
                'u.mobile AS customer_mobile',
                'u.email AS customer_email',
            ])
            ->join('ajobmart_users u', 'u.id = o.user_id', 'left')
            ->where('o.id', $orderId)
            ->get()
            ->getRowArray();

        if (!$order) {
            return redirect()->to(site_url('shipping-admin/orders'))
                ->with('error', 'Order not found.');
        }

        // Products belonging to this order, with seller IDs.
        $items = $this->db->table('ajobmart_order_items oi')
            ->select([
                'oi.*',
                'p.seller_id',
                'p.sku',
                'p.brand',
                'p.product_name AS current_product_name',
            ])
            ->join('ajobmart_products p', 'p.id = oi.product_id', 'left')
            ->where('oi.order_id', $orderId)
            ->orderBy('oi.id', 'ASC')
            ->get()
            ->getResultArray();

        // Seller-level fulfillment records.
        $fulfillments = $this->db->table('ajobmart_seller_order_fulfillments f')
            ->select('f.*')
            ->where('f.order_id', $orderId)
            ->orderBy('f.id', 'ASC')
            ->get()
            ->getResultArray();

        // All labels and admin booking records for this order.
        $labels = $this->db->table('ajobmart_seller_order_labels l')
            ->select([
                'l.*',
                'b.id AS booking_id',
                'b.admin_status',
                'b.admin_note',
                'b.rejection_reason',
                'b.booking_service',
                'b.consignment_number',
                'b.booking_date',
                'b.booking_post_office',
                'b.parcel_weight_grams',
                'b.postage_amount',
                'b.cod_amount',
                'b.postal_receipt_path',
                'b.booked_at',
                'b.shipped_at AS booking_shipped_at',
                'b.delivered_at AS booking_delivered_at',
            ])
            ->join('ajobmart_shipping_label_bookings b', 'b.label_id = l.id', 'left')
            ->where('l.order_id', $orderId)
            ->orderBy('l.id', 'ASC')
            ->get()
            ->getResultArray();

        return view('shipping_admin/orders/details', [
            'pageTitle' => 'Order Details',
            'order' => $order,
            'items' => $items,
            'fulfillments' => $fulfillments,
            'labels' => $labels,
        ]);
    }

    
/**
 * AJAX order data for the Admin Orders dashboard.
 * One record per order + seller.
 */
public function listData()
{
    $search = trim((string) $this->request->getGet('search'));
    $section = strtolower(trim((string) $this->request->getGet('section')));

    $allowedSections = [
        'pending',
        'rd',
        'shipped',
        'delivered',
    ];

    if (!in_array($section, $allowedSections, true)) {
        $section = 'pending';
    }

    $fromDate = trim((string) $this->request->getGet('from_date'));
    $toDate = trim((string) $this->request->getGet('to_date'));

    $validDate = static function ($date) {
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);

        return $parsed && $parsed->format('Y-m-d') === $date;
    };

    if ($fromDate !== '' && !$validDate($fromDate)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => false,
            'message' => 'Invalid From Date.',
        ]);
    }

    if ($toDate !== '' && !$validDate($toDate)) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => false,
            'message' => 'Invalid To Date.',
        ]);
    }

    if ($fromDate !== '' && $toDate !== '' && $fromDate > $toDate) {
        return $this->response->setStatusCode(422)->setJSON([
            'status' => false,
            'message' => 'From Date cannot be after To Date.',
        ]);
    }

    /*
     * Aggregate products by order and seller first.
     * This prevents duplicate order rows when an order has
     * multiple products belonging to the same seller.
     */
    $sql = "
        SELECT
            o.id AS order_id,
            o.order_number,
            o.user_id,
            o.address_id,
            o.payment_method,
            o.payment_status,
            o.order_status AS main_order_status,
            o.created_at,
            o.total_amount,

            u.name AS customer_name,
            u.mobile AS customer_mobile,
            u.email AS customer_email,

            si.seller_id,
            si.seller_item_count,
            si.seller_total_quantity,
            si.seller_subtotal,

            COALESCE(f.seller_status, 'new') AS seller_status,
            f.courier_name,
            f.tracking_number,
            f.tracking_url,
            f.seller_note,
            f.shipped_at AS seller_shipped_at,
            f.delivered_at AS seller_delivered_at,

            l.id AS label_id,
            l.label_number,
            l.pdf_path,
            l.label_status,
            l.generated_at AS label_generated_at,

            b.id AS booking_id,
            b.admin_status,
            b.admin_note,
            b.rejection_reason,
            b.booking_service,
            b.consignment_number,
            b.booking_date,
            b.booking_post_office,
            b.postage_amount,
            b.cod_amount,
            b.postal_receipt_path,
            b.booked_at,
            b.shipped_at AS booking_shipped_at,
            b.delivered_at AS booking_delivered_at

        FROM (
            SELECT
                oi.order_id,
                p.seller_id,
                COUNT(DISTINCT oi.id) AS seller_item_count,
                SUM(oi.quantity) AS seller_total_quantity,
                SUM(oi.line_total) AS seller_subtotal

            FROM ajobmart_order_items oi

            INNER JOIN ajobmart_products p
                ON p.id = oi.product_id

            GROUP BY
                oi.order_id,
                p.seller_id
        ) si

        INNER JOIN ajobmart_orders o
            ON o.id = si.order_id

        LEFT JOIN ajobmart_users u
            ON u.id = o.user_id

        LEFT JOIN ajobmart_seller_order_fulfillments f
            ON f.order_id = o.id
            AND f.seller_id = si.seller_id

        LEFT JOIN ajobmart_seller_order_labels l
            ON l.order_id = o.id
            AND l.seller_id = si.seller_id

        LEFT JOIN ajobmart_shipping_label_bookings b
            ON b.label_id = l.id

        WHERE
            o.payment_status IN ('paid', 'pending')
            AND LOWER(COALESCE(o.order_status, '')) != 'cancelled'
            AND LOWER(COALESCE(f.seller_status, 'new')) != 'cancelled'
    ";

    $params = [];

    if ($fromDate !== '') {
        $sql .= " AND DATE(o.created_at) >= ?";
        $params[] = $fromDate;
    }

    if ($toDate !== '') {
        $sql .= " AND DATE(o.created_at) <= ?";
        $params[] = $toDate;
    }

    if ($search !== '') {
        $sql .= "
            AND (
                o.order_number LIKE ?
                OR u.name LIKE ?
                OR u.mobile LIKE ?
                OR u.email LIKE ?
            )
        ";

        $keyword = '%' . $search . '%';

        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
        $params[] = $keyword;
    }

    $sql .= "
        ORDER BY
            o.id DESC,
            si.seller_id ASC
    ";

    $rows = $this->db->query($sql, $params)->getResultArray();

    $counts = [
        'pending' => 0,
        'rd' => 0,
        'shipped' => 0,
        'delivered' => 0,
    ];

    $classified = [];

    foreach ($rows as $row) {
        $labelId = !empty($row['label_id'])
            ? (int) $row['label_id']
            : null;

        $bookingStatus = strtolower(
            trim((string) ($row['admin_status'] ?? ''))
        );

        /*
         * Admin tab classification uses the admin booking status.
         * Seller fulfillment status is still returned separately.
         */
        if ($bookingStatus === 'delivered') {
            $dashboardSection = 'delivered';
        } elseif ($bookingStatus === 'shipped') {
            $dashboardSection = 'shipped';
        } elseif ($labelId !== null) {
            $dashboardSection = 'rd';
        } else {
            $dashboardSection = 'pending';
        }

        $row['dashboard_section'] = $dashboardSection;

        $row['order_id'] = (int) $row['order_id'];
        $row['seller_id'] = (int) $row['seller_id'];

        $row['seller_item_count'] =
            (int) $row['seller_item_count'];

        $row['seller_total_quantity'] =
            (int) $row['seller_total_quantity'];

        $row['seller_subtotal'] =
            (float) $row['seller_subtotal'];

        $row['label_id'] = $labelId;

        $row['booking_id'] = !empty($row['booking_id'])
            ? (int) $row['booking_id']
            : null;

        $row['seller_status'] =
            $row['seller_status'] ?: 'new';

        $row['label_eligible'] = (
            strtolower((string) $row['payment_status']) === 'paid'
            ||
            (
                in_array(
                    strtolower((string) $row['payment_method']),
                    ['cod', 'cash_on_delivery', 'cash on delivery'],
                    true
                )
                && strtolower((string) $row['payment_status']) === 'pending'
            )
        );

        $row['label_eligible'] = (bool) $row['label_eligible'];

        $counts[$dashboardSection]++;

        if ($dashboardSection === $section) {
            $classified[] = $row;
        }
    }

    return $this->response->setJSON([
        'status' => true,
        'selected_section' => $section,
        'counts' => $counts,
        'orders' => $classified,
        'count' => count($classified),
    ]);
}

}
