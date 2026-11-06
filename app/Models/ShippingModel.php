<?php

namespace App\Models;

use CodeIgniter\Model;

class ShippingModel extends Model
{
    protected $DBGroup = 'default';

    /**
     * Normalize a zone name to match ajobmart_shipping_rates.
     */
    private function normalizeZone(string $zone): string
    {
        return strtoupper(trim($zone));
    }

    /**
     * Resolve destination zone.
     *
     * Rules:
     * 1. Explicit origin/destination override.
     * 2. Same configured local area.
     * 3. Same state.
     * 4. Other state.
     *
     * ZONE_METRO should be configured explicitly in overrides
     * until your courier's metro-zone rules are confirmed.
     */
    public function resolveZone(string $originPin, string $destinationPin): ?string
    {
        $db = db_connect();

        $originPin = trim($originPin);
        $destinationPin = trim($destinationPin);

        if (
            !preg_match('/^\d{6}$/', $originPin) ||
            !preg_match('/^\d{6}$/', $destinationPin)
        ) {
            return null;
        }

        // Explicit override always wins.
        $override = $db->table('ajobmart_shipping_zone_overrides')
            ->where('origin_pincode', $originPin)
            ->where('destination_pincode', $destinationPin)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        if ($override) {
            return $this->normalizeZone($override['zone_code']);
        }

        $origin = $db->table('ajobmart_pincode_directory')
            ->where('pincode', $originPin)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        $destination = $db->table('ajobmart_pincode_directory')
            ->where('pincode', $destinationPin)
            ->where('is_active', 1)
            ->get()
            ->getRowArray();

        // Do not guess if either PIN is missing from the directory.
        if (!$origin || !$destination) {
            return null;
        }

        $originLocal = trim((string) ($origin['local_area_code'] ?? ''));
        $destinationLocal = trim((string) ($destination['local_area_code'] ?? ''));

        if (
            $originLocal !== '' &&
            $destinationLocal !== '' &&
            strcasecmp($originLocal, $destinationLocal) === 0
        ) {
            return 'LOCAL';
        }

        $originState = trim((string) ($origin['state_code'] ?? ''));
        $destinationState = trim((string) ($destination['state_code'] ?? ''));

        if ($originState === '' || $destinationState === '') {
            $originState = trim((string) ($origin['state_name'] ?? ''));
            $destinationState = trim((string) ($destination['state_name'] ?? ''));
        }

        if (
            $originState !== '' &&
            $destinationState !== '' &&
            strcasecmp($originState, $destinationState) === 0
        ) {
            return 'WITHIN_STATE';
        }

        return 'OTHER_STATE';
    }

    /**
     * Get a rate for the requested shipment weight.
     *
     * We intentionally return unavailable if no exact rate slab exists.
     * This version does not guess the tariff for shipments above 5000g.
     */
    public function calculateRate(string $zoneCode, int $weightGrams): ?array
    {
        if ($weightGrams <= 0 || $weightGrams > 5000) {
            return null;
        }

        $db = db_connect();

        $rate = $db->table('ajobmart_shipping_rates')
            ->where('zone_code', $this->normalizeZone($zoneCode))
            ->where('service_type', 'parcel_retail')
            ->where('is_active', 1)
            ->where('min_weight_grams <=', $weightGrams)
            ->where('max_weight_grams >=', $weightGrams)
            ->orderBy('min_weight_grams', 'DESC')
            ->get()
            ->getRowArray();

        if (!$rate) {
            return null;
        }

        $baseCharge = (float) $rate['base_charge'];
        $gstPercent = (float) $rate['gst_percent'];

        // Additional weight is not applied in this first version.
        // Confirm the courier tariff before extending beyond 5000g.
        $gstAmount = $baseCharge * ($gstPercent / 100);
        $total = round($baseCharge + $gstAmount, 2);

        return [
            'courier_name' => $rate['courier_name'],
            'service_type' => $rate['service_type'],
            'zone_code'    => $this->normalizeZone($zoneCode),
            'weight_grams' => $weightGrams,
            'base_charge'  => round($baseCharge, 2),
            'gst_amount'   => round($gstAmount, 2),
            'shipping'     => $total,
        ];
    }
}