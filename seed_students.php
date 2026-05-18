<?php
// ── Direct DB connection ──
$conn = new mysqli('localhost', 'root', '', 'sims_db');
if ($conn->connect_error) {
    die('DB connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

// ── Student data ──
$students = [
    ['05202601', 'Anderson',  'James',     'user001'],
    ['05202602', 'Martinez',  'Liam',      'user002'],
    ['05202603', 'Thompson',  'Noah',      'user003'],
    ['05202604', 'Garcia',    'Oliver',    'user004'],
    ['05202605', 'Robinson',  'Ethan',     'user005'],
    ['05202606', 'Clark',     'Lucas',     'user006'],
    ['05202607', 'Lewis',     'Mason',     'user007'],
    ['05202608', 'Walker',    'Logan',     'user008'],
    ['05202609', 'Hall',      'Aiden',     'user009'],
    ['05202610', 'Allen',     'Jackson',   'user010'],
    ['05202611', 'Young',     'Sebastian', 'user011'],
    ['05202612', 'Hernandez', 'Carter',    'user012'],
    ['05202613', 'King',      'Wyatt',     'user013'],
    ['05202614', 'Wright',    'Dylan',     'user014'],
    ['05202615', 'Lopez',     'Henry',     'user015'],
    ['05202616', 'Hill',      'Owen',      'user016'],
    ['05202617', 'Scott',     'Gabriel',   'user017'],
    ['05202618', 'Green',     'Julian',    'user018'],
    ['05202619', 'Adams',     'Levi',      'user019'],
    ['05202620', 'Baker',     'Isaac',     'user020'],
];

$year_level  = 3;
$course      = 'BS Information Technology';
$address     = 'Cebu City';
$middle_name = '';

$results = [];

$chk  = $conn->prepare("SELECT id FROM students WHERE id_number = ?");
$stmt = $conn->prepare(
    "INSERT INTO students (id_number, last_name, first_name, middle_name, year_level, course, address, email, password)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
);

foreach ($students as [$id_number, $last_name, $first_name, $password]) {
    $chk->bind_param('s', $id_number);
    $chk->execute();
    $chk->store_result();

    if ($chk->num_rows > 0) {
        $results[] = [$id_number, $first_name . ' ' . $last_name, $password, 'skip'];
        continue;
    }

    // Unique placeholder email per student using their ID number
    $email  = $id_number . '@placeholder.com';
    $hashed = password_hash($password, PASSWORD_DEFAULT);

    $stmt->bind_param(
        'ssssissss',
        $id_number, $last_name, $first_name, $middle_name,
        $year_level, $course, $address, $email, $hashed
    );
    $ok = $stmt->execute();
    $results[] = [$id_number, $first_name . ' ' . $last_name, $password, $ok ? 'ok' : 'error'];
}

$chk->close();
$stmt->close();
$conn->close();

$inserted = count(array_filter($results, fn($r) => $r[3] === 'ok'));
$skipped  = count(array_filter($results, fn($r) => $r[3] === 'skip'));
$errors   = count(array_filter($results, fn($r) => $r[3] === 'error'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Student Seeder</title>
  <style>
    body  { font-family: Arial, sans-serif; max-width: 720px; margin: 40px auto; padding: 20px; }
    h2    { color: #1a5276; }
    table { border-collapse: collapse; width: 100%; margin-top: 16px; font-size: 14px; }
    th,td { border: 1px solid #ccc; padding: 8px 12px; text-align: left; }
    th    { background: #1a5276; color: #fff; }
    tr:nth-child(even) { background: #f4f4f4; }
    .ok    { color: #155724; background: #d4edda; padding: 2px 8px; border-radius: 4px; }
    .skip  { color: #856404; background: #fff3cd; padding: 2px 8px; border-radius: 4px; }
    .error { color: #721c24; background: #f8d7da; padding: 2px 8px; border-radius: 4px; }
    .summary { margin-top: 16px; padding: 12px; background: #eaf2ff; border-radius: 6px; }
    .warn    { color: #c0392b; margin-top: 20px; font-weight: bold; }
  </style>
</head>
<body>
  <h2>🎓 Student Seeder Results</h2>
  <table>
    <tr><th>#</th><th>ID Number</th><th>Full Name</th><th>Password</th><th>Status</th></tr>
    <?php foreach ($results as $i => [$id, $name, $pw, $status]): ?>
    <tr>
      <td><?= $i + 1 ?></td>
      <td><?= htmlspecialchars($id) ?></td>
      <td><?= htmlspecialchars($name) ?></td>
      <td><?= htmlspecialchars($pw) ?></td>
      <td>
        <?php if ($status === 'ok'): ?>
          <span class="ok">Inserted ✓</span>
        <?php elseif ($status === 'skip'): ?>
          <span class="skip">Already exists</span>
        <?php else: ?>
          <span class="error">Failed ✗</span>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </table>

  <div class="summary">
    ✅ <strong><?= $inserted ?></strong> inserted &nbsp;|&nbsp;
    ⚠️ <strong><?= $skipped ?></strong> skipped &nbsp;|&nbsp;
    ❌ <strong><?= $errors ?></strong> failed
  </div>

  <p class="warn">⚠️ Delete <code>seed_students.php</code> from your project after use!</p>
</body>
</html>
