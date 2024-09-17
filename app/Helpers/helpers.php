<?php

if (!function_exists('convertResponseArray')) {
    function convertResponseArray($data_arr)
    {
        return collect($data_arr)->map(function ($item) {
            return collect($item)->map(function ($value) {
                return $value === null ? '' : $value;
            });
        });
    }
}

if (!function_exists('convertResponseSingle')) {
    function convertResponseSingle($data)
    {
        return collect($data)->map(function ($value) {
            if ($value === null) {
                return '';
            // } elseif (is_string($value) && isJson($value)) {
            //     return json_decode($value, true);
            } else {
                return $value;
            }

            // Check if the value is a valid JSON string
            // if (is_string($value) && isJson($value)) {
            //     return json_decode($value, true); // Decode JSON to array
            // }

            // return $value;
        });
    }
}

if (!function_exists('isJson')) {
    function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

if (!function_exists('generateCode')) {
    function generateCode()
    {
        $sequence = DB::table('users')->count();

        $sequence = $sequence + 1;

        $number = "LBI" . str_pad($sequence, 4, '0', STR_PAD_LEFT);
        return $number;
    }
}

// Add this function to your MyPresenceController or a helper file
if (!function_exists('getDistanceBetweenPoints')) {
    function getDistanceBetweenPoints($latitude1, $longitude1, $latitude2, $longitude2, $earthRadius = 6371000) {
        // Convert degrees to radians
        $lat1 = deg2rad($latitude1);
        $lon1 = deg2rad($longitude1);
        $lat2 = deg2rad($latitude2);
        $lon2 = deg2rad($longitude2);

        // Haversine formula to calculate the distance
        $latDifference = $lat2 - $lat1;
        $lonDifference = $lon2 - $lon1;

        $a = sin($latDifference / 2) * sin($latDifference / 2) +
            cos($lat1) * cos($lat2) *
            sin($lonDifference / 2) * sin($lonDifference / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = $earthRadius * $c; // Distance in meters

        return $distance;
    }
}

