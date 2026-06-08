<?php

function getActivityTemplate(string $crop): array
{
    $templates = [

        'Rice' => [
            ['day' => 5,  'activity' => 'Soil leveling & water management check'],
            ['day' => 7,  'activity' => 'Apply basal fertilizer (Urea + TSP)'],
            ['day' => 14, 'activity' => 'First weeding'],
            ['day' => 21, 'activity' => 'Pest monitoring (Brown Plant Hopper risk)'],
            ['day' => 28, 'activity' => 'Apply top-dress fertilizer'],
            ['day' => 35, 'activity' => 'Irrigation check'],
            ['day' => 45, 'activity' => 'Disease inspection (Blast / Sheath Blight)'],
            ['day' => 60, 'activity' => 'Second pest monitoring'],
            ['day' => 75, 'activity' => 'Irrigation reduction (heading stage)'],
            ['day' => 90, 'activity' => 'Pre-harvest inspection'],
        ],

        'Wheat' => [
            ['day' => 7,  'activity' => 'Irrigation check (crown root stage)'],
            ['day' => 21, 'activity' => 'Apply nitrogen fertilizer'],
            ['day' => 30, 'activity' => 'Rust disease monitoring'],
            ['day' => 45, 'activity' => 'Second irrigation'],
            ['day' => 60, 'activity' => 'Aphid pest monitoring'],
            ['day' => 75, 'activity' => 'Stop irrigation (dough stage)'],
            ['day' => 90, 'activity' => 'Harvest readiness check'],
        ],

        'Potato' => [
            ['day' => 7,  'activity' => 'Irrigation after planting'],
            ['day' => 10, 'activity' => 'Blight monitoring (Late Blight risk)'],
            ['day' => 15, 'activity' => 'Earthing up (mound soil around plants)'],
            ['day' => 20, 'activity' => 'Fungicide spray (if humidity > 80%)'],
            ['day' => 30, 'activity' => 'Apply fertilizer (potassium)'],
            ['day' => 45, 'activity' => 'Second blight monitoring'],
            ['day' => 60, 'activity' => 'Irrigation stop (vine drying)'],
            ['day' => 75, 'activity' => 'Harvest readiness check'],
        ],

        'Jute' => [
            ['day' => 10, 'activity' => 'Thinning (remove weak plants)'],
            ['day' => 20, 'activity' => 'Apply nitrogen fertilizer'],
            ['day' => 30, 'activity' => 'Weeding'],
            ['day' => 45, 'activity' => 'Pest monitoring (Hairy caterpillar)'],
            ['day' => 60, 'activity' => 'Apply top-dress fertilizer'],
            ['day' => 90, 'activity' => 'Retting water management'],
            ['day' => 110,'activity' => 'Harvest readiness check'],
        ],

        'Mustard' => [
            ['day' => 7,  'activity' => 'Thinning & weeding'],
            ['day' => 15, 'activity' => 'Apply fertilizer (boron + nitrogen)'],
            ['day' => 25, 'activity' => 'Aphid pest monitoring'],
            ['day' => 35, 'activity' => 'Irrigation check (flowering stage)'],
            ['day' => 50, 'activity' => 'Disease monitoring (Alternaria blight)'],
            ['day' => 65, 'activity' => 'Harvest readiness check'],
        ],

        'Maize' => [
            ['day' => 7,  'activity' => 'Irrigation check (germination stage)'],
            ['day' => 14, 'activity' => 'Thinning & gap filling'],
            ['day' => 21, 'activity' => 'Apply nitrogen fertilizer'],
            ['day' => 35, 'activity' => 'Pest monitoring (Fall Armyworm)'],
            ['day' => 50, 'activity' => 'Apply top-dress fertilizer'],
            ['day' => 65, 'activity' => 'Irrigation (tasseling stage)'],
            ['day' => 85, 'activity' => 'Harvest readiness check'],
        ],

        'Onion' => [
            ['day' => 7,  'activity' => 'Irrigation check'],
            ['day' => 15, 'activity' => 'Apply fertilizer (NPK)'],
            ['day' => 25, 'activity' => 'Thrips pest monitoring'],
            ['day' => 35, 'activity' => 'Weeding'],
            ['day' => 50, 'activity' => 'Purple blotch disease monitoring'],
            ['day' => 70, 'activity' => 'Reduce irrigation (bulb maturity)'],
            ['day' => 85, 'activity' => 'Harvest readiness check'],
        ],

        'Tomato' => [
            ['day' => 5,  'activity' => 'Staking / support installation'],
            ['day' => 10, 'activity' => 'Irrigation check'],
            ['day' => 20, 'activity' => 'Apply fertilizer (calcium + nitrogen)'],
            ['day' => 30, 'activity' => 'Whitefly & leaf curl virus monitoring'],
            ['day' => 40, 'activity' => 'Pruning & side shoot removal'],
            ['day' => 50, 'activity' => 'Disease monitoring (Early Blight)'],
            ['day' => 65, 'activity' => 'Harvest readiness check'],
        ],

        'Brinjal' => [
            ['day' => 7,  'activity' => 'Irrigation check'],
            ['day' => 15, 'activity' => 'Apply fertilizer'],
            ['day' => 25, 'activity' => 'Shoot & fruit borer monitoring'],
            ['day' => 40, 'activity' => 'Weeding & earthing up'],
            ['day' => 55, 'activity' => 'Second pest monitoring'],
            ['day' => 70, 'activity' => 'Harvest readiness check'],
        ],

        'Chili' => [
            ['day' => 7,  'activity' => 'Irrigation check'],
            ['day' => 15, 'activity' => 'Apply fertilizer (NPK)'],
            ['day' => 25, 'activity' => 'Thrips & mite monitoring'],
            ['day' => 40, 'activity' => 'Anthracnose disease monitoring'],
            ['day' => 55, 'activity' => 'Apply top-dress fertilizer'],
            ['day' => 70, 'activity' => 'Harvest readiness check'],
        ],

    ];

    return $templates[$crop] ?? [];
}