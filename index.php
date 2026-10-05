<?php
declare(strict_types=1);
require __DIR__ . '/includes/functions.php';

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$result = $isPost ? calculate($_POST) : null;
$old = [
    'from' => (string)($_POST['from'] ?? ''),
    'to' => (string)($_POST['to'] ?? ''),
    'transport' => (string)($_POST['transport'] ?? ''),
    'spots' => array_map('strval', (array)($_POST['spots'] ?? [])),
];
$days = (int)($_POST['days'] ?? 3);
$people = (int)($_POST['people'] ?? 2);
$comfortSel = (string)($_POST['comfort'] ?? 'standard');
$groups = districtsByDivision();

function districtSelect(string $id, array $groups, string $sel, string $ph): void {
    echo '<select id="' . h($id) . '" name="' . h($id) . '" required><option value="">' . h($ph) . '</option>';
    foreach ($groups as $div => $names) {
        echo '<optgroup label="' . h($div) . ' Division">';
        foreach ($names as $n) echo '<option value="' . h($n) . '"' . ($n === $sel ? ' selected' : '') . '>' . h($n) . '</option>';
        echo '</optgroup>';
    }
    echo '</select>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Bangladesh Tour Gambit – Trip Budget Calculator</title>
<script>
(function(){var t=null;try{t=localStorage.getItem('bd-theme');}catch(e){}
if(!t){t=window.matchMedia&&matchMedia('(prefers-color-scheme: dark)').matches?'dark':'light';}
document.documentElement.dataset.theme=t;})();
</script>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="top">
  <div class="brand"><span class="king">♚</span>
    <div><h1>Bangladesh Tour Gambit</h1><p>Plan your move. Know your budget. Checkmate the guesswork.</p></div>
  </div>
  <button type="button" id="themeBtn" class="btn ghost" aria-label="Toggle dark and light mode">🌙 Dark</button>
</header>
<div class="boardstrip" aria-hidden="true"></div>

<main class="wrap">
<?php if ($result && !$result['ok']): ?>
  <div class="alert" role="alert"><strong>♟ Illegal move:</strong>
    <ul><?php foreach ($result['errors'] as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" action="index.php#result" id="tourForm" novalidate>
  <section class="card"><h2><span class="piece">♙</span> Move 1 · Where are you starting from?</h2>
    <?php districtSelect('from', $groups, $old['from'], 'Select your district…'); ?>
  </section>

  <section class="card"><h2><span class="piece">♘</span> Move 2 · Where do you want to go?</h2>
    <?php districtSelect('to', $groups, $old['to'], 'Select destination…'); ?>
    <p class="hint">Every district is listed; its tourist spots load automatically.</p>
  </section>

  <section class="card"><h2><span class="piece">♗</span> Move 3 · How will you travel?</h2>
    <select id="transport" name="transport" required disabled><option value="">Choose start &amp; destination first</option></select>
    <p class="hint" id="transportHint">Only transport that actually runs on your route is shown.</p>
  </section>

  <section class="card"><h2><span class="piece">♖</span> Move 4 · Pick tourist spots</h2>
    <div class="toolbar">
      <button type="button" class="btn small" id="selAll">Select all</button>
      <button type="button" class="btn small ghost" id="selNone">Clear</button>
      <span id="spotCount" class="count">0 selected</span>
    </div>
    <div id="spots" class="spots"><p class="hint">Choose start and destination to see spots.</p></div>
  </section>

  <section class="card" id="infoCard" hidden><h2><span class="piece">♕</span> Move 5 · Average costs there</h2>
    <div class="stats" id="info"></div>
  </section>

  <section class="card"><h2><span class="piece">♔</span> Move 6 · Trip details</h2>
    <div class="grid3">
      <label>Days <input type="number" id="days" name="days" min="1" max="30" value="<?= h($days) ?>"></label>
      <label>Travellers <input type="number" id="people" name="people" min="1" max="30" value="<?= h($people) ?>"></label>
      <label>Comfort
        <select name="comfort"><?php foreach (BD_COMFORT as $k => $c): ?>
          <option value="<?= h($k) ?>"<?= $k === $comfortSel ? ' selected' : '' ?>><?= h($c[0]) ?></option>
        <?php endforeach; ?></select></label>
    </div>
    <p class="hint">1 day = no hotel night. Rooms are counted for 2 people each; local vehicles for 4 people.</p>
  </section>

  <button type="submit" class="btn big" id="submitBtn" disabled>♚ Calculate My Tour Budget</button>
</form>

<?php if ($result && $result['ok']): $r = $result; ?>
<section class="card result" id="result">
  <h2>♚ Checkmate Budget</h2>
  <p class="route"><strong><?= h($r['from']) ?></strong> → <strong><?= h($r['to']) ?></strong> ·
    <?= h($r['days']) ?> day(s) / <?= h($r['nights']) ?> night(s) · <?= h($r['people']) ?> traveller(s) · <?= h($r['comfort']) ?> comfort</p>
  <div class="stats big">
    <div><span>Total budget</span><b><?= taka($r['total']) ?></b></div>
    <div><span>Per person</span><b><?= taka($r['perPerson']) ?></b></div>
    <div><span>Per day</span><b><?= taka($r['perDay']) ?></b></div>
    <div><span>Travel time (one way)</span><b>~<?= h(fmtHours($r['hours'])) ?></b></div>
  </div>
  <div class="tablewrap"><table>
    <thead><tr><th>Item</th><th>Calculation</th><th class="num">Amount</th></tr></thead>
    <tbody>
    <?php foreach ($r['rows'] as $row): ?>
      <tr><td><?= h($row['icon']) ?> <?= h($row['label']) ?>
          <div class="bar"><i style="width:<?= h($row['pct']) ?>%"></i></div></td>
        <td><?= h($row['detail']) ?></td><td class="num"><?= taka($row['amount']) ?> <small>(<?= h($row['pct']) ?>%)</small></td></tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><td colspan="2">Estimated total</td><td class="num"><?= taka($r['total']) ?></td></tr></tfoot>
  </table></div>
  <p><strong>Spots:</strong> <?= h(implode(' · ', $r['spots'])) ?></p>
  <p class="hint">Estimates only. Real prices change with season, booking time and bargaining. Edit rates in <code>includes/config.php</code> and <code>includes/data.php</code>.</p>
  <button type="button" class="btn ghost" onclick="window.print()">🖨 Print / Save as PDF</button>
</section>
<?php endif; ?>
</main>

<footer class="foot">♟ Built with PHP · no database · all data lives in <code>includes/</code></footer>

<script>window.INITIAL = <?= json_encode($old, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
<script src="assets/app.js"></script>
</body>
</html>
