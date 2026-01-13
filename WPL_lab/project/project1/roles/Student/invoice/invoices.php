<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/project1/bootstrap.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../../public/login.php');
    exit;
}

$student_id = $_SESSION['user_id'];

// Fetch invoices with payment info
$stmt = $conn->prepare("
    SELECT i.invoice_id, i.invoice_date, i.due_date, i.total_amount, 
           i.status, i.description,
           c.course_code, c.course_name,
           COALESCE(SUM(p.amount_paid), 0) as paid_amount,
           (i.total_amount - COALESCE(SUM(p.amount_paid), 0)) as balance
    FROM invoice i
    LEFT JOIN enrollment e ON i.enrollment_id = e.enrollment_id
    LEFT JOIN course_offering co ON e.offering_id = co.offering_id
    LEFT JOIN course c ON co.course_id = c.course_id
    LEFT JOIN payment p ON i.invoice_id = p.invoice_id
    WHERE i.student_id = ?
    GROUP BY i.invoice_id
    ORDER BY i.invoice_date DESC
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$invoices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$total_due = array_sum(array_column(array_filter($invoices, fn($inv) => 
    $inv['status'] !== 'paid' && $inv['status'] !== 'cancelled'), 'balance'));
$total_paid = array_sum(array_column($invoices, 'paid_amount'));
$overdue_count = count(array_filter($invoices, fn($inv) => 
    $inv['status'] === 'overdue'));

// Fetch payment history
$stmt = $conn->prepare("
    SELECT p.payment_id, p.payment_date, p.amount_paid, p.payment_method,
           p.payment_reference, i.invoice_id, i.description
    FROM payment p
    JOIN invoice i ON p.invoice_id = i.invoice_id
    WHERE i.student_id = ?
    ORDER BY p.payment_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

ob_start();
?>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total Outstanding</h6>
                <h2 class="text-<?= $total_due > 0 ? 'danger' : 'success' ?>">
                    Rs. <?= number_format($total_due, 2) ?>
                </h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Total Paid</h6>
                <h2 class="text-success">Rs. <?= number_format($total_paid, 2) ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center shadow-sm">
            <div class="card-body">
                <h6 class="text-muted">Overdue Invoices</h6>
                <h2 class="text-<?= $overdue_count > 0 ? 'warning' : 'success' ?>">
                    <?= $overdue_count ?>
                </h2>
            </div>
        </div>
    </div>
</div>

<!-- Invoices Table -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">💰 My Invoices</h5>
    </div>
    <div class="card-body">
        <?php if (empty($invoices)): ?>
            <div class="alert alert-info">No invoices found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Balance</th>
                            <th>Due Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><strong>#<?= $inv['invoice_id'] ?></strong></td>
                                <td><?= date('M d, Y', strtotime($inv['invoice_date'])) ?></td>
                                <td>
                                    <?= htmlspecialchars($inv['description'] ?? 'General Fee') ?>
                                    <?php if ($inv['course_code']): ?>
                                        <br><small class="text-muted"><?= $inv['course_code'] ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>Rs. <?= number_format($inv['total_amount'], 2) ?></td>
                                <td class="text-success">Rs. <?= number_format($inv['paid_amount'], 2) ?></td>
                                <td class="text-danger">Rs. <?= number_format($inv['balance'], 2) ?></td>
                                <td><?= date('M d, Y', strtotime($inv['due_date'])) ?></td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'pending' => 'warning',
                                        'paid' => 'success',
                                        'partially_paid' => 'info',
                                        'overdue' => 'danger',
                                        'cancelled' => 'secondary'
                                    ];
                                    ?>
                                    <span class="badge bg-<?= $status_colors[$inv['status']] ?? 'secondary' ?>">
                                        <?= ucfirst(str_replace('_', ' ', $inv['status'])) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="invoice_detail.php?invoice_id=<?= $inv['invoice_id'] ?>" 
                                       class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Payment History -->
<div class="card shadow-sm">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">📝 Recent Payment History</h5>
    </div>
    <div class="card-body">
        <?php if (empty($payments)): ?>
            <div class="alert alert-info">No payment history found.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Invoice</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $pay): ?>
                            <tr>
                                <td><?= date('M d, Y g:i A', strtotime($pay['payment_date'])) ?></td>
                                <td>#<?= $pay['invoice_id'] ?></td>
                                <td><?= htmlspecialchars($pay['description']) ?></td>
                                <td class="text-success"><strong>Rs. <?= number_format($pay['amount_paid'], 2) ?></strong></td>
                                <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $pay['payment_method'])) ?></span></td>
                                <td><small><?= htmlspecialchars($pay['payment_reference'] ?? '-') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Help Notice -->
<div class="alert alert-info mt-4">
    <h6><strong>💡 Payment Information:</strong></h6>
    <ul class="mb-0">
        <li>For payment queries, contact the Accounts Office</li>
        <li>Payments can be made via bank transfer or in person at the cashier</li>
        <li>Keep your payment receipt/reference number for records</li>
        <li>Overdue payments may affect course enrollment and examination eligibility</li>
    </ul>
</div>

<?php
$content = ob_get_clean();
$page_title = 'Invoices & Payments';
require_once '../../../templates/layout/master_base.php';
$conn->close();
?>
