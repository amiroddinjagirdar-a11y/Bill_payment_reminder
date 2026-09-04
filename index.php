<?php
require_once 'config.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bill'])) {
        addBill();
    } elseif (isset($_POST['update_bill'])) {
        updateBill();
    } elseif (isset($_POST['delete_bill'])) {
        deleteBill();
    } elseif (isset($_POST['mark_paid'])) {
        markAsPaid();
    } elseif (isset($_POST['send_reminder'])) {
        sendReminder();
    }
}

function addBill() {
    global $pdo;
    
    // Validate required fields
    if (empty($_POST['bill_name']) || empty($_POST['amount']) || empty($_POST['due_date']) || empty($_POST['recipient_email'])) {
        header('Location: index.php?error=All required fields must be filled');
        exit;
    }
    
    $bill_name = trim($_POST['bill_name']);
    $amount = floatval($_POST['amount']);
    $due_date = $_POST['due_date'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $recipient_email = trim($_POST['recipient_email']);
    
    // Validate email
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?error=Invalid email address');
        exit;
    }
    
    // Validate amount
    if ($amount <= 0) {
        header('Location: index.php?error=Amount must be greater than 0');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO bills (bill_name, amount, due_date, description, recipient_email) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$bill_name, $amount, $due_date, $description, $recipient_email]);
        
        header('Location: index.php?success=Bill added successfully');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=Database error: ' . $e->getMessage());
        exit;
    }
}

function updateBill() {
    global $pdo;
    
    // Validate required fields
    if (empty($_POST['id']) || empty($_POST['bill_name']) || empty($_POST['amount']) || empty($_POST['due_date']) || empty($_POST['recipient_email'])) {
        header('Location: index.php?error=All required fields must be filled');
        exit;
    }
    
    $id = intval($_POST['id']);
    $bill_name = trim($_POST['bill_name']);
    $amount = floatval($_POST['amount']);
    $due_date = $_POST['due_date'];
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $recipient_email = trim($_POST['recipient_email']);
    
    // Validate email
    if (!filter_var($recipient_email, FILTER_VALIDATE_EMAIL)) {
        header('Location: index.php?error=Invalid email address');
        exit;
    }
    
    // Validate amount
    if ($amount <= 0) {
        header('Location: index.php?error=Amount must be greater than 0');
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE bills SET bill_name=?, amount=?, due_date=?, description=?, recipient_email=? WHERE id=?");
        $stmt->execute([$bill_name, $amount, $due_date, $description, $recipient_email, $id]);
        
        header('Location: index.php?success=Bill updated successfully');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=Database error: ' . $e->getMessage());
        exit;
    }
}

function deleteBill() {
    global $pdo;
    
    if (empty($_POST['id'])) {
        header('Location: index.php?error=Bill ID is required');
        exit;
    }
    
    $id = intval($_POST['id']);
    
    try {
        $stmt = $pdo->prepare("DELETE FROM bills WHERE id=?");
        $stmt->execute([$id]);
        
        header('Location: index.php?success=Bill deleted successfully');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=Database error: ' . $e->getMessage());
        exit;
    }
}

function markAsPaid() {
    global $pdo;
    
    if (empty($_POST['id'])) {
        header('Location: index.php?error=Bill ID is required');
        exit;
    }
    
    $id = intval($_POST['id']);
    
    try {
        $stmt = $pdo->prepare("UPDATE bills SET status='paid' WHERE id=?");
        $stmt->execute([$id]);
        
        header('Location: index.php?success=Bill marked as paid');
        exit;
    } catch (PDOException $e) {
        header('Location: index.php?error=Database error: ' . $e->getMessage());
        exit;
    }
}

function sendReminder() {
    if (empty($_POST['id'])) {
        header('Location: index.php?error=Bill ID is required');
        exit;
    }
    
    $id = intval($_POST['id']);
    header('Location: mail_sender.php?bill_id=' . $id);
    exit;
}

// Get all bills
try {
    $stmt = $pdo->query("SELECT * FROM bills ORDER BY due_date ASC");
    $bills = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Update overdue bills
    $pdo->exec("UPDATE bills SET status='overdue' WHERE due_date < CURDATE() AND status = 'pending'");
    
    // Statistics
    $total_bills = $pdo->query("SELECT COUNT(*) FROM bills")->fetchColumn();
    $pending_bills = $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'pending'")->fetchColumn();
    $overdue_bills = $pdo->query("SELECT COUNT(*) FROM bills WHERE status = 'overdue'")->fetchColumn();
    $total_amount_result = $pdo->query("SELECT SUM(amount) FROM bills WHERE status IN ('pending', 'overdue')")->fetchColumn();
    $total_amount = $total_amount_result ? $total_amount_result : 0;
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <header class="bg-white rounded-lg shadow-md p-6 mb-8">
            <div class="flex justify-between items-center flex-col md:flex-row">
                <div class="mb-4 md:mb-0">
                    <h1 class="text-3xl font-bold text-gray-800"><?php echo APP_NAME; ?></h1>
                    <p class="text-gray-600">Never miss a bill payment again</p>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-sm text-gray-600">Total Pending Amount</p>
                    <p class="text-2xl font-bold text-red-600">$<?php echo number_format($total_amount, 2); ?></p>
                </div>
            </div>
        </header>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-blue-100 text-blue-600 mr-3">
                        <i class="fas fa-file-invoice-dollar"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Total Bills</p>
                        <p class="text-xl font-bold"><?php echo $total_bills; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-yellow-100 text-yellow-600 mr-3">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Pending</p>
                        <p class="text-xl font-bold"><?php echo $pending_bills; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-red-100 text-red-600 mr-3">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Overdue</p>
                        <p class="text-xl font-bold"><?php echo $overdue_bills; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-2 rounded-full bg-green-100 text-green-600 mr-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Paid</p>
                        <p class="text-xl font-bold"><?php echo $total_bills - $pending_bills - $overdue_bills; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Add Bill Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6 sticky top-4">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">
                        <i class="fas fa-plus-circle mr-2 text-blue-600"></i>
                        Add New Bill
                    </h2>
                    
                    <?php if (isset($_GET['success'])): ?>
                        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($_GET['success']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($_GET['error'])): ?>
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bill Name *</label>
                            <input type="text" name="bill_name" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="e.g., Electricity Bill"
                                   value="<?php echo isset($_POST['bill_name']) ? htmlspecialchars($_POST['bill_name']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label>
                            <input type="number" name="amount" step="0.01" min="0.01" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="0.00"
                                   value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                            <input type="date" name="due_date" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   value="<?php echo isset($_POST['due_date']) ? htmlspecialchars($_POST['due_date']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email *</label>
                            <input type="email" name="recipient_email" required 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                   placeholder="recipient@example.com"
                                   value="<?php echo isset($_POST['recipient_email']) ? htmlspecialchars($_POST['recipient_email']) : ''; ?>">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                      placeholder="Optional description or notes"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        </div>
                        
                        <button type="submit" name="add_bill" 
                                class="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                            <i class="fas fa-save mr-2"></i>Add Bill
                        </button>
                    </form>
                </div>
            </div>

            <!-- Bills List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold mb-6 text-gray-800">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        Your Bills
                    </h2>
                    
                    <?php if (empty($bills)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No bills found. Add your first bill to get started!</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full min-w-full">
                                <thead>
                                    <tr class="bg-gray-50">
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bill</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($bills as $bill): ?>
                                        <?php
                                        $due_date = new DateTime($bill['due_date']);
                                        $today = new DateTime();
                                        $days_remaining = $today->diff($due_date)->days;
                                        $is_overdue = $due_date < $today;
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-4">
                                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($bill['bill_name']); ?></div>
                                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($bill['recipient_email']); ?></div>
                                                <?php if (!empty($bill['description'])): ?>
                                                    <div class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($bill['description']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-4 text-lg font-semibold text-gray-900">
                                                $<?php echo number_format($bill['amount'], 2); ?>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="text-sm text-gray-900"><?php echo $due_date->format('M j, Y'); ?></div>
                                                <div class="text-xs <?php echo $is_overdue ? 'text-red-600' : 'text-gray-500'; ?>">
                                                    <?php echo $is_overdue ? 'Overdue by ' . $days_remaining . ' days' : $days_remaining . ' days remaining'; ?>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php echo $bill['status'] === 'paid' ? 'bg-green-100 text-green-800' : 
                                                           ($bill['status'] === 'overdue' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); ?>">
                                                    <?php echo ucfirst($bill['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex space-x-2">
                                                    <?php if ($bill['status'] !== 'paid'): ?>
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="id" value="<?php echo $bill['id']; ?>">
                                                            <button type="submit" name="send_reminder" 
                                                                    class="text-blue-600 hover:text-blue-900 transition duration-200" 
                                                                    title="Send Email Reminder">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </button>
                                                        </form>
                                                        
                                                        <form method="POST" class="inline">
                                                            <input type="hidden" name="id" value="<?php echo $bill['id']; ?>">
                                                            <button type="submit" name="mark_paid" 
                                                                    class="text-green-600 hover:text-green-900 transition duration-200" 
                                                                    title="Mark as Paid">
                                                                <i class="fas fa-check-circle"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                    
                                                    <button onclick="editBill(<?php echo htmlspecialchars(json_encode($bill)); ?>)" 
                                                            class="text-yellow-600 hover:text-yellow-900 transition duration-200" 
                                                            title="Edit Bill">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    
                                                    <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this bill?');">
                                                        <input type="hidden" name="id" value="<?php echo $bill['id']; ?>">
                                                        <button type="submit" name="delete_bill" 
                                                                class="text-red-600 hover:text-red-900 transition duration-200" 
                                                                title="Delete Bill">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Bill Modal -->
    <div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 p-4">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto">
            <h3 class="text-xl font-bold mb-4">Edit Bill</h3>
            <form method="POST" id="editForm">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="update_bill" value="1">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Bill Name</label>
                        <input type="text" name="bill_name" id="edit_bill_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount</label>
                        <input type="number" name="amount" id="edit_amount" step="0.01" min="0.01" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label>
                        <input type="date" name="due_date" id="edit_due_date" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email</label>
                        <input type="email" name="recipient_email" id="edit_recipient_email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" id="edit_description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeModal()" 
                            class="px-4 py-2 text-gray-600 hover:text-gray-800 transition duration-200">Cancel</button>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-200">
                        Update Bill
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editBill(bill) {
            document.getElementById('edit_id').value = bill.id;
            document.getElementById('edit_bill_name').value = bill.bill_name;
            document.getElementById('edit_amount').value = bill.amount;
            document.getElementById('edit_due_date').value = bill.due_date;
            document.getElementById('edit_recipient_email').value = bill.recipient_email;
            document.getElementById('edit_description').value = bill.description || '';
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
        
        // Close modal when clicking outside
        document.getElementById('editModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });

        // Set minimum date to today for due date inputs
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            const dueDateInput = document.querySelector('input[name="due_date"]');
            const editDueDateInput = document.getElementById('edit_due_date');
            
            if (dueDateInput) dueDateInput.min = today;
            if (editDueDateInput) editDueDateInput.min = today;
        });
    </script>
</body>
</html>