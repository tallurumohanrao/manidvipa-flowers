<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait GoogleDistanceTrait
{
    public function getDistance($address_id): array
    {
        $address = DB::table('addresses')->where('id', $address_id)->first();

        if (! $address) {
            return $this->distanceFailed('Address not found.', 'address_not_found');
        }

        $coordinateDistance = $this->distanceFromStoredCoordinates($address);
        if ($coordinateDistance !== null) {
            return $this->distanceSuccess($coordinateDistance, 'coordinates');
        }

        if (app()->environment(['local', 'testing'])) {
            $fallbackDistance = $this->distanceFromHyderabadFallback($address);
            if ($fallbackDistance !== null) {
                return $this->distanceSuccess($fallbackDistance, 'hyderabad_estimate');
            }
        }

        $googleDistance = $this->distanceFromGoogle($address);
        if ($googleDistance !== null) {
            return $this->distanceSuccess($googleDistance, 'google');
        }

        $fallbackDistance = $this->distanceFromHyderabadFallback($address);
        if ($fallbackDistance !== null) {
            return $this->distanceSuccess($fallbackDistance, 'hyderabad_estimate');
        }

        return $this->distanceFailed(
            'Unable to calculate delivery distance for the selected address.',
            'distance_unavailable'
        );
    }

    public function getDistanceOld($address_id): array
    {
        return $this->getDistance($address_id);
    }

    private function distanceFromStoredCoordinates($address): ?float
    {
        if (
            ! Schema::hasColumn('addresses', 'latitude') ||
            ! Schema::hasColumn('addresses', 'longitude') ||
            empty($address->latitude) ||
            empty($address->longitude)
        ) {
            return null;
        }

        [$originLatitude, $originLongitude] = $this->storeCoordinates();

        return $this->haversineDistance(
            (float) $originLatitude,
            (float) $originLongitude,
            (float) $address->latitude,
            (float) $address->longitude
        );
    }

    private function distanceFromGoogle($address): ?float
    {
        $googleMapsKey = trim((string) config('services.google_maps.key', ''));

        if ($googleMapsKey === '' || ! function_exists('curl_init')) {
            return null;
        }

        $routesApiDistance = $this->distanceFromGoogleRoutesApi($googleMapsKey, $address);
        if ($routesApiDistance !== null) {
            return $routesApiDistance;
        }

        return $this->distanceFromGoogleDistanceMatrix($googleMapsKey, $address);
    }

    private function distanceFromGoogleRoutesApi(string $googleMapsKey, $address): ?float
    {
        $headers = [
            'Content-Type: application/json',
            'X-Goog-Api-Key: '.$googleMapsKey,
            'X-Goog-FieldMask: routes.distanceMeters,routes.duration',
        ];
        $referer = trim((string) config('services.google_maps.referer', ''));
        if ($referer !== '') {
            $headers[] = 'Referer: '.$referer;
        }

        $payload = [
            'origin' => [
                'address' => $this->storeAddress(),
            ],
            'destination' => [
                'address' => $this->addressText($address),
            ],
            'travelMode' => 'DRIVE',
            'routingPreference' => 'TRAFFIC_UNAWARE',
            'languageCode' => 'en-IN',
            'regionCode' => 'in',
            'units' => 'METRIC',
        ];

        $ch = curl_init('https://routes.googleapis.com/directions/v2:computeRoutes');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! $response || $httpCode >= 400) {
            return null;
        }

        $result = json_decode($response, true);
        $distanceMeters = $result['routes'][0]['distanceMeters'] ?? null;

        return is_numeric($distanceMeters) && $distanceMeters > 0
            ? round(((float) $distanceMeters) / 1000, 2)
            : null;
    }

    private function distanceFromGoogleDistanceMatrix(string $googleMapsKey, $address): ?float
    {
        $query = http_build_query([
            'origins' => $this->storeAddress(),
            'destinations' => $this->addressText($address),
            'mode' => 'driving',
            'language' => 'en-IN',
            'units' => 'metric',
            'key' => $googleMapsKey,
        ]);

        $ch = curl_init('https://maps.googleapis.com/maps/api/distancematrix/json?'.$query);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (! $response || $httpCode >= 400) {
            return null;
        }

        $result = json_decode($response, true);

        if (($result['status'] ?? null) !== 'OK') {
            return null;
        }

        $element = $result['rows'][0]['elements'][0] ?? null;

        if (($element['status'] ?? null) !== 'OK') {
            return null;
        }

        if (isset($element['distance']['value']) && is_numeric($element['distance']['value'])) {
            return round(((float) $element['distance']['value']) / 1000, 2);
        }

        $distanceText = (string) ($element['distance']['text'] ?? '');
        if ($distanceText === '') {
            return null;
        }

        $distanceValue = (float) preg_replace('/[^\d.]/', '', $distanceText);

        if (stripos($distanceText, 'mi') !== false) {
            $distanceValue *= 1.60934;
        }

        return $distanceValue > 0 ? round($distanceValue, 2) : null;
    }

    private function distanceFromHyderabadFallback($address): ?float
    {
        $text = strtolower($this->addressText($address));
        $pincode = preg_replace('/\D+/', '', (string) ($address->pincode ?? ''));

        $pincodeDistances = [
            '500038' => 1.5,
            '500045' => 3.5,
            '500016' => 4.5,
            '500073' => 5.5,
            '500034' => 6.5,
            '500018' => 7.0,
            '500033' => 8.0,
            '500081' => 10.0,
            '500072' => 11.0,
            '500037' => 12.0,
            '500049' => 13.0,
            '500050' => 14.0,
            '500032' => 16.0,
            '500055' => 18.0,
            '500019' => 20.0,
            '500070' => 24.0,
            '500084' => 26.0,
            '500079' => 28.0,
            '500100' => 30.0,
            '501301' => 35.0,
        ];

        if (isset($pincodeDistances[$pincode])) {
            return $pincodeDistances[$pincode];
        }

        $localityDistances = [
            'vengal rao nagar' => 1.5,
            'sr nagar' => 2.0,
            'ameerpet' => 3.0,
            'jubilee hills' => 6.5,
            'banjara hills' => 7.0,
            'madhapur' => 9.5,
            'hitech city' => 11.0,
            'kukatpally' => 12.0,
            'gachibowli' => 15.0,
            'suraram' => 18.0,
            'miyapur' => 19.0,
            'lb nagar' => 23.0,
            'uppal' => 24.0,
            'kompally' => 25.0,
            'bachupally' => 26.0,
        ];

        foreach ($localityDistances as $locality => $distance) {
            if (str_contains($text, $locality)) {
                return $distance;
            }
        }

        if (
            str_contains($text, 'hyderabad') ||
            str_contains($text, 'secunderabad') ||
            str_starts_with($pincode, '500')
        ) {
            return (float) config('services.store_origin.default_hyderabad_distance_km', 12);
        }

        return null;
    }

    private function storeAddress(): string
    {
        return trim((string) config(
            'services.store_origin.address',
            '8-3-241/21, Srinivasa Colony, Vengal Rao Nagar, SR Nagar, Hyderabad, Telangana 500038, India'
        ));
    }

    private function storeCoordinates(): array
    {
        return [
            (float) config('services.store_origin.latitude', 17.4356),
            (float) config('services.store_origin.longitude', 78.4467),
        ];
    }

    private function addressText($address): string
    {
        return implode(', ', array_filter([
            $address->address_line1 ?? null,
            $address->address_line2 ?? null,
            $address->landmark ?? null,
            $address->city ?? null,
            $address->state ?? null,
            $address->pincode ?? null,
            $address->country ?? 'India',
        ]));
    }

    private function haversineDistance(float $originLatitude, float $originLongitude, float $destinationLatitude, float $destinationLongitude): float
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($destinationLatitude - $originLatitude);
        $longitudeDelta = deg2rad($destinationLongitude - $originLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($originLatitude)) * cos(deg2rad($destinationLatitude))
            * sin($longitudeDelta / 2) ** 2;

        return round($earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a)), 2);
    }

    private function distanceSuccess(float $distance, string $source): array
    {
        return [
            'status' => 'success',
            'distance' => round($distance, 2),
            'source' => $source,
            'message' => null,
        ];
    }

    private function distanceFailed(string $message, string $source): array
    {
        return [
            'status' => 'fail',
            'distance' => null,
            'source' => $source,
            'message' => $message,
        ];
    }
}
