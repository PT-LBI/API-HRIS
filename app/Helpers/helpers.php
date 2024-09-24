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

if (!function_exists('convertResponseJson')) {
    function convertResponseJson($data_json)
    {
        return collect($data_json)->map(function ($value) {
            if ($value === null) {
                return '';
            } elseif (is_string($value) && isJson($value)) {
                return json_decode($value, true);
            } else {
                return $value;
            }

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

if (!function_exists('sendNotification')) {
    function sendNotification()
    {
        // Firebase API URL
        $url = 'https://fcm.googleapis.com/fcm/send';

        // Firebase server key (you can store it in your .env file for security)
        $serverKey = env('FCM_SERVER_KEY');

        // The notification payload
        $notification = [
            'title' => 'Test Notification',
            'body'  => 'This is a test message',
            'sound' => 'default',
        ];

        // Target device token or topic
        // $token = 'YOUR_DEVICE_TOKEN';
        $token = 'e6bf975f-16a5-40e9-8de7-731a0f4f';

        // Data payload (optional)
        $data = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        // Fields required by FCM
        $fields = [
            'to' => $token,  // You can also use 'topic' => 'your-topic'
            'notification' => $notification,
            'data' => $data,
        ];

        // Set headers for the request
        $headers = [
            'Authorization: key=' . $serverKey,
            'Content-Type: application/json',
        ];

        // Initialize cURL session
        $ch = curl_init();

        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

        // Execute the cURL request
        $response = curl_exec($ch);

        // Close the cURL session
        curl_close($ch);

        // Handle the response
        if ($response === false) {
            return response()->json(['message' => 'Notification failed'], 500);
        }

        return response()->json(['message' => 'Notification sent successfully', 'response' => json_decode($response)], 200);
    }
}

