<?php
require __DIR__ . '/config.php';
header('Content-Type: application/json; charset=utf-8');

function out($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function validate(array $in): array {
    $sku  = trim((string)($in['sku'] ?? ''));
    $name = trim((string)($in['name'] ?? ''));
    $cat  = trim((string)($in['category'] ?? ''));
    if ($sku === '' || $name === '') out(['error' => 'SKU and product name are required.'], 422);
    if (mb_strlen($sku) > 40 || mb_strlen($name) > 100 || mb_strlen($cat) > 40) out(['error' => 'A field is too long.'], 422);
    // Block spreadsheet formula injection (cells starting with = + - @)
    foreach ([&$sku, &$name, &$cat] as &$v) if ($v !== '' && strpos('=+-@', $v[0]) !== false) $v = "'" . $v;
    $qty   = filter_var($in['qty']   ?? 0, FILTER_VALIDATE_INT);
    $cost  = filter_var($in['cost']  ?? 0, FILTER_VALIDATE_FLOAT);
    $price = filter_var($in['price'] ?? 0, FILTER_VALIDATE_FLOAT);
    if ($qty === false || $qty < 0 || $cost === false || $cost < 0 || $price === false || $price < 0)
        out(['error' => 'Quantity and prices must be zero or more.'], 422);
    return ['sku' => $sku, 'name' => $name, 'category' => $cat, 'qty' => $qty, 'cost' => round($cost, 2), 'price' => round($price, 2)];
}

$action = $_GET['action'] ?? 'list';
$in = json_decode(file_get_contents('php://input'), true) ?: [];

if ($action !== 'list' && $_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'POST required.'], 405);

try {
    switch ($action) {
        case 'list':
            $res = sheets(['action' => 'list']);
            break;
        case 'create':
            $res = sheets(['action' => 'create'] + validate($in));
            break;
        case 'update':
            $id = trim((string)($in['id'] ?? ''));
            if ($id === '') out(['error' => 'Invalid id.'], 422);
            $res = sheets(['action' => 'update', 'id' => $id] + validate($in));
            break;
        case 'delete':
            $id = trim((string)($in['id'] ?? ''));
            if ($id === '') out(['error' => 'Invalid id.'], 422);
            $res = sheets(['action' => 'delete', 'id' => $id]);
            break;
        default:
            out(['error' => 'Unknown action.'], 400);
    }

    if (isset($res['error'])) {
        $map = [
            'DUPLICATE_SKU' => ['That SKU already exists.', 409],
            'NOT_FOUND'     => ['That product no longer exists. Refresh the page.', 404],
            'Unauthorized'  => ['Token mismatch: SHEETS_TOKEN in config.php must equal TOKEN in Code.gs.', 500],
        ];
        [$msg, $code] = $map[$res['error']] ?? ['Google Sheets error: ' . $res['error'], 500];
        out(['error' => $msg], $code);
    }
    out($action === 'list' ? ($res['data'] ?? []) : ['ok' => true]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    out(['error' => $e->getMessage()], 500);
}
