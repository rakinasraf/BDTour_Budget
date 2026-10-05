<?php
// ---- Editable assumptions (all prices in BDT, estimates) ----
// Destination cost tier: hotel = per ROOM per night, food = per PERSON per day,
// local = local transport (CNG / boat / leguna / chander gari) per day per 4 people
const BD_TIERS = [
    1 => ['hotel' => 1000, 'food' => 500,  'local' => 250],
    2 => ['hotel' => 1500, 'food' => 650,  'local' => 350],
    3 => ['hotel' => 2500, 'food' => 850,  'local' => 500],
    4 => ['hotel' => 3800, 'food' => 1100, 'local' => 700],
];

// kind: person = fare per person per km, vehicle = per vehicle (4 seats) per km, air = base + rate*km
const BD_MODES = [
    'bus'    => ['label' => 'Non-AC Bus',            'icon' => '🚌', 'kind' => 'person',  'rate' => 2.6, 'factor' => 1.3,  'speed' => 45],
    'ac_bus' => ['label' => 'AC Bus / Coach',        'icon' => '🚍', 'kind' => 'person',  'rate' => 4.6, 'factor' => 1.3,  'speed' => 50],
    'train'  => ['label' => 'Train',                 'icon' => '🚆', 'kind' => 'person',  'rate' => 3.0, 'factor' => 1.25, 'speed' => 50],
    'car'    => ['label' => 'Private Car / Microbus', 'icon' => '🚗', 'kind' => 'vehicle', 'rate' => 24,  'factor' => 1.3,  'speed' => 55],
    'launch' => ['label' => 'Launch / Steamer',      'icon' => '🚢', 'kind' => 'person',  'rate' => 4.0, 'factor' => 1.2,  'speed' => 22],
    'air'    => ['label' => 'Domestic Flight',       'icon' => '✈️', 'kind' => 'air',     'base' => 3000, 'rate' => 10, 'factor' => 1.0, 'speed' => 600],
];

// comfort level => [label, hotel multiplier, food multiplier]
const BD_COMFORT = [
    'budget'   => ['Budget',   0.6, 0.8],
    'standard' => ['Standard', 1.0, 1.0],
    'premium'  => ['Premium',  1.8, 1.5],
];

const BD_MISC_RATE = 0.05; // tips, tolls, small emergencies

// ---- Route availability (edit if services change) ----
const BD_NO_ROAD = ['Bhola']; // island district: no bus / car
const BD_TRAIN = ['Dhaka','Gazipur','Narayanganj','Narsingdi','Kishoreganj','Tangail','Munshiganj','Shariatpur','Faridpur','Narail','Magura','Jashore','Jhenaidah','Brahmanbaria','Cumilla','Feni','Chattogram',"Cox's Bazar",'Noakhali','Chandpur','Mymensingh','Netrokona','Jamalpur','Sherpur','Sirajganj','Bogura','Joypurhat','Naogaon','Rajshahi','Natore','Pabna','Chapai Nawabganj','Rangpur','Dinajpur','Nilphamari','Thakurgaon','Lalmonirhat','Kurigram','Gaibandha','Sylhet','Moulvibazar','Habiganj','Khulna','Satkhira','Kushtia','Chuadanga'];
const BD_LAUNCH = ['Dhaka','Narayanganj','Munshiganj','Chandpur','Madaripur','Shariatpur','Barishal','Bhola','Patuakhali','Barguna','Pirojpur','Jhalokathi','Bagerhat','Khulna','Gopalganj','Lakshmipur','Noakhali','Rajbari','Faridpur'];
const BD_AIRPORTS = ['Dhaka','Chattogram',"Cox's Bazar",'Sylhet','Jashore','Rajshahi','Barishal','Rangpur','Nilphamari'];
