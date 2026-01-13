<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') exit;

$student_id = $_SESSION['user_id'];
$invoice_id = $_GET['invoice_id'] ?? null;

if (!$invoice_id) {
    header('Location: invoices.php');
    exit;
}

// Fetch invoice details
$stmt = $conn->prepare("
    SELECT i.*, 
           c.course_code, c.course_name,
           CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
           s.student_number,
           u2.full_name as student_name
    FROM invoice i
    LEFT JOIN enrollment e ON i.enrollment_id = e.enrollment_id
    LEFT JOIN course_offering co ON e.offering_id = co.offering_id
    LEFT JOIN course c ON co.course_id = c.course_id
    LEFT JOIN users u ON i.created_by = u.id
    JOIN student s ON i.student_id = s.student_id
    JOIN users u2 ON s.student_id = u2.id
    WHERE i.invoice_id = ? AND i.student_id = ?
");
$stmt->bind_param("ii", $invoice_id, $student_id);
$stmt->execute();
$invoice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$invoice) {
    header('Location: invoices.php');
    exit;
}

// Fetch payments for this invoice
$stmt = $conn->prepare("
    SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as received_by_name
    FROM payment p
    LEFT JOIN users u ON p.received_by = u.id
    WHERE p.invoice_id = ?
    ORDER BY p.payment_date DESC
");
$stmt->bind_param("i", $invoice_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_paid = array_sum(array_column($payments, 'amount_paid'));
$balance = $invoice['total_amount'] - $total_paid;

ob_start();
?>

<a href="invoices.php" class="btn btn-secondary mb-3">← Back to Invoices</a>

<!-- Invoice Header -->
<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <h3>Invoice #<?= $invoice['invoice_id'] ?></h3>
                <p class="mb-1"><strong>Student:</strong> <?= htmlspecialchars($invoice['student_name']) ?></p>
                <p class="mb-1"><strong>Student ID:</strong> <?= htmlspecialchars($invoice['student_number']) ?></p>
                <?php if ($invoice['course_code']): ?>
                    <p class="mb-0"><strong>Course:</strong> <?= htmlspecialchars($invoice['course_code'] . ' - ' . $invoice['course_name']) ?></p>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-end">
                <p class="mb-1"><strong>Issue Date:</strong> <?= date('F d, Y', strtotime($invoice['invoice_date'])) ?></p>
                <p class="mb-1"><strong>Due Date:</strong> 
                    <span class="text-<?= strtotime($invoice['due_date']) < time() && $balance > 0 ? 'danger' : 'muted' ?>">
                        <?= date('F d, Y', strtotime($invoice['due_date'])) ?>
                    </span>
                </p>
                <p class="mb-0">
                    <span class="badge bg-<?= 
                        $invoice['status'] === 'paid' ? 'success' : 
                        ($invoice['status'] === 'overdue' ? 'danger' : 'warning') 
                    ?> fs-6">
                        <?= ucfirst(str_replace('_', ' ', $invoice['status'])) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Details -->
<div class="card shadow-sm mb-3">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">Invoice Details</h5>
    </div>
    <div class="card-body">
        <table class="table">
            <tr>
                <th width="30%">Description:</th>
                <td><?= htmlspecialchars($invoice['description'] ?? 'Fee Payment') ?></td>
            </tr>
            <tr>
                <th>Total Amount:</th>
                <td><strong class="fs-5">Rs. <?= number_format($invoice['total_amount'], 2) ?></strong></td>
            </tr>
            <tr>
                <th>Amount Paid:</th>
                <td class="text-success"><strong>Rs. <?= number_format($total_paid, 2) ?></strong></td>
            </tr>
            <tr>
                <th>Balance Due:</th>
                <td class="text-danger"><strong class="fs-5">Rs. <?= number_format($balance, 2) ?></strong></td>
            </tr>
        </table>
    </div>
</div>

<!-- Payment History for this Invoice -->
<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">Payment History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="alert alert-warning">No payments recorded yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Payment Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Received By</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td><?= date('M d, Y g:i A', strtotime($pay['payment_date'])) ?></td>
                                <td class="text-success"><strong>Rs. <?= number_format($pay['amount_paid'], 2) ?></strong></td>
                                <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $pay['payment_method'])) ?></span></td>
                                <td><?= htmlspecialchars($pay['payment_reference'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($pay['received_by_name'] ?? 'System') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($balance > 0): ?>
<div class="alert alert-info mt-3">
    <h6><strong>How to Pay:</strong></h6>
    <p class="mb-0">Please visit the Accounts Office or contact them for payment options. 
    Keep your invoice number (#<?= $invoice['invoice_id'] ?>) handy.</p>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
$page_title = 'Invoice Detail';
require_once '../../../templates/layout/master_base.php';
$conn->close();
?>