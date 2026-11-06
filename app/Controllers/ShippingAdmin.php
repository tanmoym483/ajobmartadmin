<?php

namespace App\Controllers;

use App\Models\ShippingAdminModel;
use App\Models\ShippingAdminDataModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use App\Models\ShippingLabelSettingsModel;
use App\Models\ShippingLabelBookingModel;

class ShippingAdmin extends BaseController
{
    protected ShippingAdminModel $admins;
    protected ShippingAdminDataModel $shippingData;
    protected ShippingLabelSettingsModel $labelSettings;
    protected ShippingLabelBookingModel $labelBookings;
    protected $layout = 'shipping_admin/layouts/main';


    public function __construct()
    {
        $this->admins = new ShippingAdminModel();
        $this->shippingData = new ShippingAdminDataModel();
        $this->labelSettings = new ShippingLabelSettingsModel();
        $this->labelBookings = new ShippingLabelBookingModel();
    }

    public function index()
    {
        return $this->view('shipping_admin/dashboard', [
            'admin_username' => session()->get('shipping_admin_username'),
            'admin_role'     => session()->get('shipping_admin_role') ?? 'ADMIN',
        ]);
    }

    public function setup()
    {
        if ($this->admins->adminCount() > 0) {
            return redirect()->to(site_url('shipping-admin/login'))
                ->with('error', 'Initial administrator setup is already complete.');
        }

        return $this->view('shipping_admin/setup');
    }

    public function createFirstAdmin()
    {
        if ($this->admins->adminCount() > 0) {
            return redirect()->to(site_url('shipping-admin/login'))
                ->with('error', 'An administrator already exists.');
        }

        $rules = [
            'setup_key' => 'required|max_length[200]',
            'username' => 'required|alpha_numeric_punct|min_length[4]|max_length[100]',
            'password' => 'required|min_length[12]|max_length[200]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $configuredKey = (string) env('SHIPPING_ADMIN_SETUP_KEY');
        $submittedKey = (string) $this->request->getPost('setup_key');

        if ($configuredKey === '' || !hash_equals($configuredKey, $submittedKey)) {
            return redirect()->back()
                ->with('error', 'Invalid setup key.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        // Recheck immediately before insertion to reduce setup race conditions.
        if ($this->admins->adminCount() > 0) {
            return redirect()->to(site_url('shipping-admin/login'))
                ->with('error', 'An administrator already exists.');
        }

        try {
            if (!$this->admins->createAdmin($username, $password)) {
                return redirect()->back()
                    ->with('error', 'Could not create the administrator.');
            }
        } catch (\Throwable $e) {
            log_message('error', 'Shipping admin setup failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Could not create the administrator. Check the application logs.');
        }

        return redirect()->to(site_url('shipping-admin/login'))
            ->with('success', 'Administrator created. Please log in.');
    }

    public function login()
    {
        // if ((int) session()->get('shipping_admin_id') > 0) {
        //     return redirect()->to(site_url('shipping-admin/shipping'))
        // }

        return view('shipping_admin/login');
    }

    public function authenticate()
    {
        $attempts = (int) session()->get('shipping_admin_login_attempts');
        $lockedUntil = (int) session()->get('shipping_admin_locked_until');

        if ($lockedUntil > time()) {
            return redirect()->back()->with(
                'error',
                'Too many failed attempts. Try again in ' .
                (int) ceil(($lockedUntil - time()) / 60) . ' minute(s).'
            );
        }

        if ($lockedUntil > 0 && $lockedUntil <= time()) {
            $attempts = 0;
            session()->remove([
                'shipping_admin_login_attempts',
                'shipping_admin_locked_until',
            ]);
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        if ($username === '' || $password === '') {
            return redirect()->back()->with('error', 'Enter your username and password.');
        }

        $admin = $this->admins->findByUsername($username);

        if (
            !$admin ||
            (int) $admin['is_active'] !== 1 ||
            !password_verify($password, $admin['password_hash'])
        ) {
            $attempts++;

            session()->set('shipping_admin_login_attempts', $attempts);

            if ($attempts >= 5) {
                session()->set('shipping_admin_locked_until', time() + 300);
                return redirect()->back()->with(
                    'error',
                    'Too many failed attempts. Try again in 5 minutes.'
                );
            }

            return redirect()->back()->with('error', 'Invalid username or password.');
        }

        if (password_needs_rehash($admin['password_hash'], PASSWORD_DEFAULT)) {
            $this->admins->update($admin['id'], [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        session()->regenerate(true);

        session()->remove([
            'shipping_admin_login_attempts',
            'shipping_admin_locked_until',
        ]);

        // Only set shipping-admin keys; do not overwrite customer session keys.
        session()->set([
            'shipping_admin_id'       => (int) $admin['id'],
            'shipping_admin_username' => $admin['username'],
            'shipping_admin_role'     => $admin['role'] ?? 'ADMIN',
        ]);

        $this->admins->recordLogin((int) $admin['id']);

        return redirect()->to(site_url('shipping-admin'))
            ->with('success', 'Welcome back.');
    }

    public function logout()
    {
        // Do not destroy the shared CI4 session; it may contain customer data.
        session()->remove([
            'shipping_admin_id',
            'shipping_admin_username',
            'shipping_admin_role',
            'shipping_admin_login_attempts',
            'shipping_admin_locked_until',
        ]);

        session()->regenerate(true);

        return redirect()->to(site_url('shipping-admin/login'))
            ->with('success', 'Shipping admin logged out.');
    }

    public function shipping()
    {
        $search = trim((string) $this->request->getGet('search'));

        // Search PIN directory.
        $pins = $this->shippingData->searchPins($search);

        // Search shipping mappings using the same search term.
        $mappings = $this->shippingData->searchMappings($search);

        return view('shipping_admin/shipping', [
            'pageTitle' => 'Shipping Management',
            'search'    => $search,
            'pins'      => $pins,
            'mappings'  => $mappings,
        ]);
    }

    public function importCsv()
    {
        $file = $this->request->getFile('pin_csv');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()->with('error', 'Select a valid CSV file.');
        }

        if (strtolower($file->getExtension()) !== 'csv') {
            return redirect()->back()->with('error', 'Only CSV files are accepted.');
        }

        if ($file->getSize() > 30 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Maximum CSV size is 20 MB.');
        }

        $handle = fopen($file->getTempName(), 'r');

        if (!$handle) {
            return redirect()->back()->with('error', 'Unable to read the CSV file.');
        }

        $firstLine = fgets($handle);
        rewind($handle);

        $counts = [
            ',' => substr_count((string) $firstLine, ','),
            ';' => substr_count((string) $firstLine, ';'),
            "\t" => substr_count((string) $firstLine, "\t"),
        ];

        arsort($counts);
        $delimiter = array_key_first($counts) ?: ',';

        $headers = fgetcsv($handle, 0, $delimiter);

        if (!$headers) {
            fclose($handle);
            return redirect()->back()->with('error', 'CSV header is missing.');
        }

        $headerMap = [];

        foreach ($headers as $index => $header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
            $normalized = strtolower(
                preg_replace('/[^a-z0-9]/i', '', trim($header))
            );
            $headerMap[$normalized] = $index;
        }

        $find = static function (array $names) use ($headerMap): ?int {
            foreach ($names as $name) {
                if (array_key_exists($name, $headerMap)) {
                    return $headerMap[$name];
                }
            }
            return null;
        };

        $pinIndex = $find(['pincode', 'pin', 'postalcode', 'pincodeoffice']);
        $stateIndex = $find(['statename', 'state']);
        $districtIndex = $find(['districtname', 'district']);
        $cityIndex = $find(['taluk', 'city', 'cityname', 'subdistrict']);
        $officeIndex = $find(['officename', 'postoffice']);

        if ($pinIndex === null || $stateIndex === null) {
            fclose($handle);
            return redirect()->back()->with(
                'error',
                'Could not find PIN and state columns. Share the CSV header row for adjustment.'
            );
        }

        $db = db_connect();
        $db->transBegin();

        $seen = [];
        $processed = 0;
        $skipped = 0;
        $line = 1;

        try {
            while (($record = fgetcsv($handle, 0, $delimiter)) !== false) {
                $line++;

                $pin = preg_replace('/\s+/', '', trim((string) ($record[$pinIndex] ?? '')));
                $state = trim((string) ($record[$stateIndex] ?? ''));

                $district = $districtIndex !== null
                    ? trim((string) ($record[$districtIndex] ?? ''))
                    : '';

                $city = $cityIndex !== null
                    ? trim((string) ($record[$cityIndex] ?? ''))
                    : '';

                if ($city === '' && $officeIndex !== null) {
                    $city = trim((string) ($record[$officeIndex] ?? ''));
                }

                if ($city === '') {
                    $city = $district;
                }

                if (!preg_match('/^[0-9]{6}$/', $pin) || $state === '') {
                    $skipped++;
                    continue;
                }

                // A PIN may have multiple post offices. Store one directory row per PIN.
                if (isset($seen[$pin])) {
                    continue;
                }

                $seen[$pin] = true;

                if (!$this->shippingData->importPin([
                    'pincode' => $pin,
                    'state_name' => $state,
                    'district_name' => $district,
                    'city_name' => $city,
                ])) {
                    throw new \RuntimeException('Database error at CSV line ' . $line);
                }

                $processed++;
            }

            fclose($handle);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('PIN import transaction failed.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            $db->transRollback();
            log_message('error', 'Shipping PIN import failed: ' . $e->getMessage());

            return redirect()->back()->with(
                'error',
                'Import failed; no partial import was committed. Check application logs.'
            );
        }

        return redirect()->to(site_url('shipping-admin/shipping'))
            ->with(
                'success',
                "PIN import completed. Inserted/updated: {$processed}. Skipped invalid rows: {$skipped}."
            );
    }

    public function saveMapping()
    {
        $rules = [
            'origin_pincode' => 'required|regex_match[/^[0-9]{6}$/]',
            'destination_pincode' => 'required|regex_match[/^[0-9]{6}$/]',
            'zone_code' => 'required|in_list[local,within_state,zone_metro,other_state]',
            'notes' => 'permit_empty|max_length[500]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $origin = trim((string) $this->request->getPost('origin_pincode'));
        $destination = trim((string) $this->request->getPost('destination_pincode'));

        if ($origin === $destination) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Origin and destination PINs must be different.');
        }

        $db = db_connect();

        $count = $db->table('ajobmart_pincode_directory')
            ->whereIn('pincode', [$origin, $destination])
            ->where('is_active', 1)
            ->countAllResults();

        if ($count !== 2) {
            return redirect()->back()->withInput()->with(
                'error',
                'Both PINs must exist and be active in the directory.'
            );
        }

        $saved = $this->shippingData->saveMapping([
            'origin_pincode' => $origin,
            'destination_pincode' => $destination,
            'zone_code' => $this->request->getPost('zone_code'),
            'notes' => trim((string) $this->request->getPost('notes')),
            'is_active' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$saved) {
            return redirect()->back()->withInput()->with(
                'error',
                'This origin/destination pair already exists or could not be saved.'
            );
        }

        return redirect()->to(site_url('shipping-admin/shipping'))
            ->with('success', 'Manual shipping zone saved.');
    }

    public function toggleMapping(int $id)
    {
        if ($id < 1 || !$this->shippingData->toggleMapping($id)) {
            return redirect()->back()->with(
                'error',
                'Mapping not found or could not be updated.'
            );
        }

        return redirect()->back()->with('success', 'Mapping status updated.');
    }

    
/*
|--------------------------------------------------------------------------
| SHIPPING LABEL SETTINGS
|--------------------------------------------------------------------------
*/

public function labelSettings()
{
    return view('shipping_admin/label_settings', [
        'pageTitle' => 'Shipping Label Settings',
        'settings' => $this->labelSettings->getSettings(),
    ]);
}

public function saveLabelSettings()
{
    $companyName = trim((string) $this->request->getPost('company_name'));
    $labelSize = $this->request->getPost('label_size');

    if ($companyName === '') {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Company name is required.');
    }

    if (!in_array($labelSize, ['A5', 'A6'], true)) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid label size.');
    }

    $data = [
        'company_name' => $companyName,
        'label_size' => $labelSize,

        'show_order_number' => $this->request->getPost('show_order_number') ? 1 : 0,
        'show_label_number' => $this->request->getPost('show_label_number') ? 1 : 0,
        'show_seller_details' => $this->request->getPost('show_seller_details') ? 1 : 0,
        'show_product_details' => $this->request->getPost('show_product_details') ? 1 : 0,
        'show_payment_details' => $this->request->getPost('show_payment_details') ? 1 : 0,
        'show_shipment_details' => $this->request->getPost('show_shipment_details') ? 1 : 0,

        'footer_text' => trim((string) $this->request->getPost('footer_text')),
        'updated_by' => (int) session()->get('shipping_admin_id'),
    ];

    // Optional logo upload.
    $logo = $this->request->getFile('logo');

    if ($logo && $logo->isValid() && !$logo->hasMoved()) {
        if (!in_array($logo->getExtension(), ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Logo must be JPG, PNG or WebP.');
        }

        if ($logo->getSizeByUnit('mb') > 2) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Logo must not exceed 2 MB.');
        }

        $uploadPath = FCPATH . 'uploads/shipping-labels/logo/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        $newName = $logo->getRandomName();

        if (!$logo->move($uploadPath, $newName)) {
            return redirect()->back()
                ->with('error', 'Unable to upload logo.');
        }

        $data['logo_path'] = 'uploads/shipping-labels/logo/' . $newName;
    }

    if (!$this->labelSettings->saveSettings($data)) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Unable to save label settings.');
    }

    return redirect()->to(site_url('shipping-admin/label-settings'))
        ->with('success', 'Label settings saved successfully.');
}


/*
|--------------------------------------------------------------------------
| SELLER LABEL REVIEW
|--------------------------------------------------------------------------
*/

public function labelBookings()
{
    $status = trim((string) $this->request->getGet('status'));

    $allowedStatuses = [
        'pending_review',
        'approved',
        'rejected',
        'booked',
        'shipped',
        'delivered',
        'cancelled',
    ];

    if (!in_array($status, $allowedStatuses, true)) {
        $status = '';
    }

    return view('shipping_admin/label_bookings', [
        'pageTitle' => 'Seller Shipping Labels',
        'status' => $status,
        'bookings' => $this->labelBookings->getAdminBookings($status),
    ]);
}

public function labelBookingDetails(int $id)
{
    $booking = $this->labelBookings->getAdminBooking($id);

    if (!$booking) {
        return redirect()->to(site_url('shipping-admin/label-bookings'))
            ->with('error', 'Shipping label record not found.');
    }

    return view('shipping_admin/label_booking_details', [
        'pageTitle' => 'Review Shipping Label',
        'booking' => $booking,
    ]);
}


/*
|--------------------------------------------------------------------------
| APPROVE / REJECT LABEL
|--------------------------------------------------------------------------
*/

public function approveLabel(int $id)
{
    $booking = $this->labelBookings->find($id);

    if (!$booking || $booking['admin_status'] !== 'pending_review') {
        return redirect()->back()
            ->with('error', 'This label cannot be approved.');
    }

    $this->labelBookings->update($id, [
        'admin_status' => 'approved',
        'approved_by' => (int) session()->get('shipping_admin_id'),
        'approved_at' => date('Y-m-d H:i:s'),
        'admin_note' => trim((string) $this->request->getPost('admin_note')),
    ]);

    return redirect()->to(site_url('shipping-admin/label-bookings/' . $id))
        ->with('success', 'Label approved. You can now record India Post booking.');
}

public function rejectLabel(int $id)
{
    $booking = $this->labelBookings->find($id);

    if (!$booking || $booking['admin_status'] !== 'pending_review') {
        return redirect()->back()
            ->with('error', 'This label cannot be rejected.');
    }

    $reason = trim((string) $this->request->getPost('rejection_reason'));

    if ($reason === '') {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Please provide a rejection reason.');
    }

    $this->labelBookings->update($id, [
        'admin_status' => 'rejected',
        'rejection_reason' => $reason,
        'admin_note' => trim((string) $this->request->getPost('admin_note')),
    ]);

    return redirect()->to(site_url('shipping-admin/label-bookings/' . $id))
        ->with('success', 'Label rejected.');
}


/*
|--------------------------------------------------------------------------
| MANUAL INDIA POST BOOKING
|--------------------------------------------------------------------------
*/

public function savePostalBooking(int $id)
{
    $booking = $this->labelBookings->find($id);

    if (!$booking || $booking['admin_status'] !== 'approved') {
        return redirect()->back()
            ->with('error', 'Only approved labels can be booked.');
    }

    $service = $this->request->getPost('booking_service');

    if (!in_array($service, ['india_post_parcel', 'india_post_speed_post'], true)) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Select a valid India Post service.');
    }

    $consignment = trim((string) $this->request->getPost('consignment_number'));
    $bookingDate = $this->request->getPost('booking_date');
    $postOffice = trim((string) $this->request->getPost('booking_post_office'));

    $weight = (int) $this->request->getPost('parcel_weight_grams');
    $length = (float) $this->request->getPost('parcel_length_cm');
    $width = (float) $this->request->getPost('parcel_width_cm');
    $height = (float) $this->request->getPost('parcel_height_cm');
    $postage = (float) $this->request->getPost('postage_amount');
    $codAmount = (float) $this->request->getPost('cod_amount');

    if (
        $consignment === '' ||
        !$bookingDate ||
        $postOffice === '' ||
        $weight <= 0 ||
        $postage < 0 ||
        $codAmount < 0
    ) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Please enter all required booking details correctly.');
    }

    $date = \DateTime::createFromFormat('Y-m-d', $bookingDate);

    if (!$date || $date->format('Y-m-d') !== $bookingDate) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Invalid booking date.');
    }

    if ($length < 0 || $width < 0 || $height < 0) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Parcel dimensions cannot be negative.');
    }

    // Receipt is optional, but if provided it must be a PDF or image.
    $receipt = $this->request->getFile('postal_receipt');
    $receiptPath = null;

    if ($receipt && $receipt->isValid() && !$receipt->hasMoved()) {
        $allowedMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
        ];

        if (!in_array($receipt->getMimeType(), $allowedMimeTypes, true)) {
            return redirect()->back()
                ->with('error', 'Receipt must be PDF, JPG or PNG.');
        }

        if ($receipt->getSizeByUnit('mb') > 5) {
            return redirect()->back()
                ->with('error', 'Receipt must not exceed 5 MB.');
        }

        $uploadPath = WRITEPATH . 'uploads/postal-receipts/';

        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0750, true);
        }

        $newName = $receipt->getRandomName();

        if (!$receipt->move($uploadPath, $newName)) {
            return redirect()->back()
                ->with('error', 'Unable to upload postal receipt.');
        }

        $receiptPath = 'uploads/postal-receipts/' . $newName;
    }

    $data = [
        'admin_status' => 'booked',
        'booking_service' => $service,
        'consignment_number' => $consignment,
        'booking_date' => $bookingDate,
        'booking_post_office' => $postOffice,
        'parcel_weight_grams' => $weight,
        'parcel_length_cm' => $length ?: null,
        'parcel_width_cm' => $width ?: null,
        'parcel_height_cm' => $height ?: null,
        'postage_amount' => $postage,
        'cod_amount' => $codAmount,
        'booked_by' => (int) session()->get('shipping_admin_id'),
        'booked_at' => date('Y-m-d H:i:s'),
    ];

    if ($receiptPath !== null) {
        $data['postal_receipt_path'] = $receiptPath;
    }

    if (!$this->labelBookings->update($id, $data)) {
        return redirect()->back()
            ->with('error', 'Unable to save India Post booking.');
    }

    return redirect()->to(site_url('shipping-admin/label-bookings/' . $id))
        ->with('success', 'India Post booking details saved successfully.');
}


/*
|--------------------------------------------------------------------------
| POSTAL DISPATCH / DELIVERY STATUS
|--------------------------------------------------------------------------
*/

public function updatePostalStatus(int $id)
{
    $booking = $this->labelBookings->find($id);

    if (!$booking || !in_array($booking['admin_status'], ['booked', 'shipped'], true)) {
        return redirect()->back()
            ->with('error', 'This shipment cannot be updated.');
    }

    $status = $this->request->getPost('admin_status');

    if (!in_array($status, ['shipped', 'delivered'], true)) {
        return redirect()->back()
            ->with('error', 'Invalid shipment status.');
    }

    if ($status === 'delivered' && $booking['admin_status'] !== 'shipped') {
        return redirect()->back()
            ->with('error', 'Mark the parcel as shipped before marking it delivered.');
    }

    $data = ['admin_status' => $status];

    if ($status === 'shipped') {
        $data['shipped_at'] = date('Y-m-d H:i:s');
    } else {
        $data['delivered_at'] = date('Y-m-d H:i:s');
    }

    $this->labelBookings->update($id, $data);

    // Synchronize the seller's existing fulfillment record.
    $fulfillment = $this->db->table('ajobmart_seller_order_fulfillments')
        ->where('seller_id', $booking['seller_id'])
        ->where('order_id', $booking['order_id'])
        ->get()
        ->getRowArray();

    $fulfillmentData = [
        'seller_status' => $status,
        'courier_name' => 'India Post',
        'tracking_number' => $booking['consignment_number'],
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    if ($status === 'shipped') {
        $fulfillmentData['shipped_at'] = date('Y-m-d H:i:s');
    } else {
        $fulfillmentData['delivered_at'] = date('Y-m-d H:i:s');
    }

    if ($fulfillment) {
        $this->db->table('ajobmart_seller_order_fulfillments')
            ->where('id', $fulfillment['id'])
            ->update($fulfillmentData);
    } else {
        $fulfillmentData['seller_id'] = $booking['seller_id'];
        $fulfillmentData['order_id'] = $booking['order_id'];
        $fulfillmentData['created_at'] = date('Y-m-d H:i:s');

        $this->db->table('ajobmart_seller_order_fulfillments')
            ->insert($fulfillmentData);
    }

    return redirect()->to(site_url('shipping-admin/label-bookings/' . $id))
        ->with('success', 'Shipment status updated successfully.');
}



/*
|--------------------------------------------------------------------------
| SECURE SELLER LABEL PDF VIEW
|--------------------------------------------------------------------------
*/


public function viewSellerLabel(int $id)
{
    $booking = $this->labelBookings->getAdminBooking($id);

    if (!$booking || empty($booking['pdf_path'])) {
        return redirect()->back()
            ->with('error', 'Seller shipping label not found.');
    }

    $relativePath = str_replace('\\', '/', trim($booking['pdf_path']));

    // Accept both old and new database paths.
    if (str_starts_with($relativePath, 'uploads/order-labels/')) {
        $relativePath = substr(
            $relativePath,
            strlen('uploads/order-labels/')
        );
    }

    // Validate path.
    if (
        !preg_match(
            '#^seller_[0-9]+/[A-Za-z0-9_-]+\.pdf$#i',
            $relativePath
        )
    ) {
        log_message('error', 'Invalid seller label path: ' . $relativePath);

        return redirect()->back()
            ->with('error', 'Invalid label file path.');
    }

    $storageRoot = trim((string) env('AJOBMART_LABEL_STORAGE_PATH'));

    if ($storageRoot === '') {
        return redirect()->back()
            ->with('error', 'Label storage path is not configured.');
    }

    $storageRoot = rtrim($storageRoot, '/\\') . DIRECTORY_SEPARATOR;

    $basePath = realpath($storageRoot);
    $filePath = realpath($storageRoot . $relativePath);

    if (
        !$basePath ||
        !$filePath ||
        !str_starts_with(
            $filePath,
            $basePath . DIRECTORY_SEPARATOR
        ) ||
        !is_file($filePath) ||
        strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) !== 'pdf'
    ) {
        log_message('error', 'Seller label PDF not found: ' . $storageRoot . $relativePath);

        return redirect()->back()
            ->with('error', 'Label PDF file does not exist.');
    }

    return $this->response
        ->setHeader('Content-Type', 'application/pdf')
        ->setHeader(
            'Content-Disposition',
            'inline; filename="' . basename($filePath) . '"'
        )
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setBody(file_get_contents($filePath));
}



/*
|--------------------------------------------------------------------------
| SECURE INDIA POST RECEIPT VIEW
|--------------------------------------------------------------------------
*/

public function viewPostalReceipt(int $id)
{
    $booking = $this->labelBookings->find($id);

    if (!$booking || empty($booking['postal_receipt_path'])) {
        return redirect()->back()
            ->with('error', 'Postal receipt not found.');
    }

    $relativePath = str_replace('\\', '/', $booking['postal_receipt_path']);

    if (
        !str_starts_with($relativePath, 'uploads/postal-receipts/') ||
        str_contains($relativePath, '..')
    ) {
        return redirect()->back()
            ->with('error', 'Invalid receipt file path.');
    }

    $basePath = realpath(WRITEPATH . 'uploads/postal-receipts/');
    $filePath = realpath(WRITEPATH . $relativePath);

    if (
        !$basePath ||
        !$filePath ||
        !str_starts_with($filePath, $basePath . DIRECTORY_SEPARATOR) ||
        !is_file($filePath)
    ) {
        return redirect()->back()
            ->with('error', 'Receipt file does not exist.');
    }

    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    $allowedMimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
    ];

    if (!isset($allowedMimeTypes[$extension])) {
        return redirect()->back()
            ->with('error', 'Unsupported receipt file.');
    }

    return $this->response
        ->setHeader('Content-Type', $allowedMimeTypes[$extension])
        ->setHeader(
            'Content-Disposition',
            'inline; filename="' . basename($filePath) . '"'
        )
        ->setHeader('X-Content-Type-Options', 'nosniff')
        ->setBody(file_get_contents($filePath));
}

}
