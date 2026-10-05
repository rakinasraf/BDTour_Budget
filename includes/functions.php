<?php
declare(strict_types=1);
require_once __DIR__ . '/config.php';

function h($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function taka($n): string { return '৳' . number_format((float)$n, 0); }

function districts(): array {
    static $d = null;
    if ($d !== null) return $d;
    $d = [];
    foreach (require __DIR__ . '/data.php' as $name => $r) {
        $spots = [];
        foreach (explode(';', $r[4]) as $s) {
            if (trim($s) === '') continue;
            $p = explode('|', $s);
            $spots[] = ['name' => trim($p[0]), 'fee' => (int)($p[1] ?? 0)];
        }
        $d[$name] = ['division' => $r[0], 'lat' => $r[1], 'lng' => $r[2], 'tier' => $r[3], 'spots' => $spots];
    }
    ksort($d);
    return $d;
}

function districtsByDivision(): array {
    $g = [];
    foreach (districts() as $name => $r) $g[$r['division']][] = $name;
    ksort($g);
    return $g;
}

function straightKm(string $a, string $b): float {
    $d = districts();
    if ($a === $b) return 8.0;
    $p1 = deg2rad($d[$a]['lat']); $p2 = deg2rad($d[$b]['lat']);
    $dl = deg2rad($d[$b]['lng'] - $d[$a]['lng']);
    $x = sin(($p2 - $p1) / 2) ** 2 + cos($p1) * cos($p2) * sin($dl / 2) ** 2;
    return 2 * 6371 * asin(sqrt($x));
}

function availableModes(string $from, string $to): array {
    $m = [];
    if (!in_array($from, BD_NO_ROAD, true) && !in_array($to, BD_NO_ROAD, true)) array_push($m, 'bus', 'ac_bus', 'car');
    if ($from !== $to) {
        if (in_array($from, BD_TRAIN, true) && in_array($to, BD_TRAIN, true)) $m[] = 'train';
        if (in_array($from, BD_LAUNCH, true) && in_array($to, BD_LAUNCH, true)) $m[] = 'launch';
        if (in_array($from, BD_AIRPORTS, true) && in_array($to, BD_AIRPORTS, true)) $m[] = 'air';
    }
    return $m;
}

function travelCost(string $mode, float $km, int $people): array {
    $M = BD_MODES[$mode];
    $road = $km * $M['factor'];
    if ($M['kind'] === 'vehicle')      $oneway = $road * $M['rate'] * (int)ceil($people / 4);
    elseif ($M['kind'] === 'air')      $oneway = ($M['base'] + $M['rate'] * $road) * $people;
    else                               $oneway = $road * $M['rate'] * $people;
    $oneway = max($oneway, 100 * $people);
    $hours = $road / $M['speed'] + ($M['kind'] === 'air' ? 3.0 : 0.0);
    return ['oneway' => (int)round($oneway), 'total' => (int)round($oneway * 2), 'hours' => $hours, 'km' => (int)round($road)];
}

function fmtHours(float $h): string {
    $m = (int)round($h * 60);
    return intdiv($m, 60) . 'h ' . str_pad((string)($m % 60), 2, '0', STR_PAD_LEFT) . 'm';
}

// Everything the UI needs after origin + destination are chosen
function routeOptions(string $from, string $to): ?array {
    $D = districts();
    if (!isset($D[$from], $D[$to])) return null;
    $km = straightKm($from, $to);
    $modes = [];
    foreach (availableModes($from, $to) as $id) {
        $c = travelCost($id, $km, 1);
        $modes[] = ['id' => $id, 'label' => BD_MODES[$id]['icon'] . ' ' . BD_MODES[$id]['label'],
                    'hint' => '~' . taka($c['total']) . ' round trip/person, ~' . fmtHours($c['hours']) . ' one way'];
    }
    $t = BD_TIERS[$D[$to]['tier']];
    return ['modes' => $modes, 'spots' => $D[$to]['spots'], 'division' => $D[$to]['division'],
            'km' => (int)round($km * 1.3), 'hotel' => $t['hotel'], 'food' => $t['food'], 'local' => $t['local']];
}

function calculate(array $in): array {
    $D = districts();
    $err = [];
    $from = (string)($in['from'] ?? ''); $to = (string)($in['to'] ?? '');
    $mode = (string)($in['transport'] ?? '');
    $days = (int)($in['days'] ?? 0); $people = (int)($in['people'] ?? 0);
    $comfort = (string)($in['comfort'] ?? 'standard');
    $picked = array_values(array_unique(array_map('strval', (array)($in['spots'] ?? []))));

    if (!isset($D[$from])) $err[] = 'Please choose a valid starting district.';
    if (!isset($D[$to]))   $err[] = 'Please choose a valid destination.';
    if ($days < 1 || $days > 30)    $err[] = 'Days must be between 1 and 30.';
    if ($people < 1 || $people > 30) $err[] = 'Travellers must be between 1 and 30.';
    if (!isset(BD_COMFORT[$comfort])) $err[] = 'Invalid comfort level.';
    if ($err) return ['ok' => false, 'errors' => $err];
    if (!in_array($mode, availableModes($from, $to), true)) $err[] = 'That transport is not available for this route.';

    $spotMap = [];
    foreach ($D[$to]['spots'] as $s) $spotMap[$s['name']] = $s['fee'];
    $entry = 0; $chosen = [];
    foreach ($picked as $name) {
        if (!isset($spotMap[$name])) { $err[] = 'Invalid tourist spot selected.'; break; }
        $entry += $spotMap[$name]; $chosen[] = $name;
    }
    if (!$chosen && !$err) $err[] = 'Please select at least one tourist spot.';
    if ($err) return ['ok' => false, 'errors' => $err];

    [$cl, $hm, $fm] = BD_COMFORT[$comfort];
    $tier = BD_TIERS[$D[$to]['tier']];
    $km = straightKm($from, $to);
    $tr = travelCost($mode, $km, $people);
    $nights = $days - 1;
    $rooms = (int)ceil($people / 2);
    $vehicles = (int)ceil($people / 4);
    $hotelRoom = (int)round($tier['hotel'] * $hm);
    $foodPP = (int)round($tier['food'] * $fm);

    $rows = [
        ['icon' => BD_MODES[$mode]['icon'], 'label' => 'Travel (round trip)', 'detail' => BD_MODES[$mode]['label'] . ", ~{$tr['km']} km each way, {$people} traveller(s)", 'amount' => $tr['total']],
        ['icon' => '🏨', 'label' => 'Hotel', 'detail' => "{$rooms} room(s) x {$nights} night(s) x " . taka($hotelRoom), 'amount' => $hotelRoom * $rooms * $nights],
        ['icon' => '🍽️', 'label' => 'Food', 'detail' => "{$people} person(s) x {$days} day(s) x " . taka($foodPP), 'amount' => $foodPP * $people * $days],
        ['icon' => '🛺', 'label' => 'Local transport', 'detail' => "CNG / boat / leguna / chander gari: {$vehicles} group(s) x {$days} day(s) x " . taka($tier['local']), 'amount' => $tier['local'] * $vehicles * $days],
        ['icon' => '🎟️', 'label' => 'Entry & activity fees', 'detail' => count($chosen) . ' spot(s) x ' . $people . ' person(s)', 'amount' => $entry * $people],
    ];
    $sub = array_sum(array_column($rows, 'amount'));
    $misc = (int)round($sub * BD_MISC_RATE);
    $rows[] = ['icon' => '🧰', 'label' => 'Miscellaneous (5%)', 'detail' => 'Tips, tolls, small emergencies', 'amount' => $misc];
    $total = $sub + $misc;
    foreach ($rows as &$r) $r['pct'] = $total > 0 ? round($r['amount'] / $total * 100, 1) : 0;
    unset($r);

    return ['ok' => true, 'from' => $from, 'to' => $to, 'days' => $days, 'nights' => $nights, 'people' => $people,
            'comfort' => $cl, 'spots' => $chosen, 'rows' => $rows, 'total' => $total,
            'perPerson' => (int)round($total / $people), 'perDay' => (int)round($total / $days),
            'hours' => $tr['hours']];
}
