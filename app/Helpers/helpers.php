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
            return $value === null ? '' : $value;
        });
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
