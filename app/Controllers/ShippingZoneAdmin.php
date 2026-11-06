<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ShippingAdminModel;
use CodeIgniter\Exceptions\PageNotFoundException;

class ShippingZoneAdmin extends BaseController
{
    protected ShippingAdminModel $shippingAdminModel;

    public function __construct()
    {
        $this->shippingAdminModel = new ShippingAdminModel();
    }

    public function index()
    {
        $search = trim((string) $this->request->getGet('search'));

        return view('admin/shipping-zones/index', [
            'title' => 'India Post Shipping Zones',
            'search' => $search,
            'pins' => $this->shippingAdminModel->searchPins($search),
            'overrides' => $this->shippingAdminModel->searchOverrides($search),
        ]);
    }

    public function importCsv()
    {
        $file = $this->request->getFile('pin_csv');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return redirect()->back()
                ->with('error', 'Please select a valid CSV file.');
        }

        if (strtolower($file->getExtension()) !== 'csv') {
            return redirect()->back()
                ->with('error', 'Please upload a .csv file.');
        }

        // Limit upload size to 20 MB.
        if ($file->getSizeByUnit('mb') > 20) {
            return redirect()->back()
                ->with('error', 'CSV file must be 20 MB or smaller.');
        }

        $handle = fopen($file->getTempName(), 'r');

        if (!$handle) {
            return redirect()->back()
                ->with('error', 'Unable to read the uploaded CSV.');
        }

        // Detect common delimiters from the first line.
        $firstLine = fgets($handle);
        rewind($handle);

        $commaCount = substr_count((string) $firstLine, ',');
        $semicolonCount = substr_count((string) $firstLine, ';');
        $tabCount = substr_count((string) $firstLine, "\t");

        $delimiter = ',';

        if ($semicolonCount > $commaCount && $semicolonCount > $tabCount) {
            $delimiter = ';';
        } elseif ($tabCount > $commaCount && $tabCount > $semicolonCount) {
            $delimiter = "\t";
        }

        $headers = fgetcsv($handle, 0, $delimiter);

        if (!$headers || count($headers) < 2) {
            fclose($handle);

            return redirect()->back()
                ->with('error', 'CSV header row is missing or invalid.');
        }

        $headerMap = [];

        foreach ($headers as $index => $header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header);
            $normalized = strtolower(
                preg_replace('/[^a-z0-9]/i', '', trim($header))
            );

            $headerMap[$normalized] = $index;
        }

        $pinIndex = $this->findHeader($headerMap, [
            'pincode', 'pin', 'pincodeoffice', 'postalcode'
        ]);

        $stateIndex = $this->findHeader($headerMap, [
            'statename', 'state', 'statename'
        ]);

        $districtIndex = $this->findHeader($headerMap, [
            'districtname', 'district', 'districtname'
        ]);

        $cityIndex = $this->findHeader($headerMap, [
            'taluk', 'city', 'cityname', 'subdistrict'
        ]);

        $officeIndex = $this->findHeader($headerMap, [
            'officename', 'postoffice', 'officename'
        ]);

        if ($pinIndex === null || $stateIndex === null) {
            fclose($handle);

            return redirect()->back()->with(
                'error',
                'Could not identify the PIN-code and state columns. Please check the CSV headers.'
            );
        }

        $insertedOrUpdated = 0;
        $skipped = 0;
        $lineNumber = 1;
        $seenPins = [];
        $db = db_connect();

        $db->transBegin();

        try {
            while (($record = fgetcsv($handle, 0, $delimiter)) !== false) {
                $lineNumber++;

                if (count($record) <= $pinIndex) {
                    $skipped++;
                    continue;
                }

                $pin = trim((string) ($record[$pinIndex] ?? ''));
                $pin = preg_replace('/\s+/', '', $pin);

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

                // Multiple post offices may share one PIN.
                // Import only one directory record per unique PIN.
                if (isset($seenPins[$pin])) {
                    continue;
                }

                $seenPins[$pin] = true;

                $ok = $this->shippingAdminModel->importPinRow([
                    'pincode' => $pin,
                    'state_name' => $state,
                    'district_name' => $district,
                    'city_name' => $city,
                ]);

                if (!$ok) {
                    throw new \RuntimeException(
                        'Database error while importing CSV line ' . $lineNumber
                    );
                }

                $insertedOrUpdated++;
            }

            fclose($handle);

            if ($db->transStatus() === false) {
                throw new \RuntimeException('Database transaction failed.');
            }

            $db->transCommit();
        } catch (\Throwable $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            $db->transRollback();

            log_message('error', 'PIN CSV import failed: ' . $e->getMessage());

            return redirect()->back()
                ->with('error', 'Import failed. Check the CSV format and database logs.');
        }

        return redirect()->to(site_url('admin/shipping-zones'))
            ->with(
                'success',
                "Import completed. Inserted/updated: {$insertedOrUpdated}; skipped invalid rows: {$skipped}."
            );
    }

    private function findHeader(array $headerMap, array $possibleNames): ?int
    {
        foreach ($possibleNames as $name) {
            if (isset($headerMap[$name])) {
                return $headerMap[$name];
            }
        }

        return null;
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
                ->with('error', 'Origin and destination PIN codes must be different.');
        }

        // Both PINs must exist in the imported directory.
        $db = db_connect();

        $count = $db->table('ajobmart_pincode_directory')
            ->whereIn('pincode', [$origin, $destination])
            ->where('is_active', 1)
            ->countAllResults();

        if ($count !== 2) {
            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'Both PIN codes must exist and be active in the PIN directory.'
                );
        }

        $data = [
            'origin_pincode' => $origin,
            'destination_pincode' => $destination,
            'zone_code' => $this->request->getPost('zone_code'),
            'notes' => trim((string) $this->request->getPost('notes')),
            'is_active' => 1,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $saved = $this->shippingAdminModel->saveOverride($data);

        if (!$saved) {
            return redirect()->back()
                ->withInput()
                ->with(
                    'error',
                    'This origin and destination PIN pair already exists, or the mapping could not be saved.'
                );
        }

        return redirect()->to(site_url('admin/shipping-zones'))
            ->with('success', 'Shipping zone mapping saved.');
    }

    public function toggleMapping(int $id)
    {
        if ($id < 1 || !$this->shippingAdminModel->toggleOverride($id)) {
            return redirect()->back()
                ->with('error', 'Mapping not found or could not be updated.');
        }

        return redirect()->back()
            ->with('success', 'Mapping status updated.');
    }
}
