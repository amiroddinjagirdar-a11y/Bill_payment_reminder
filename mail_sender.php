<?php
require_once 'config.php';

// Include PHPMailer
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


if (isset($_GET['bill_id'])) {
    $bill_id = $_GET['bill_id'];
    
    // Get bill details
    $stmt = $pdo->prepare("SELECT * FROM bills WHERE id = ?");
    $stmt->execute([$bill_id]);
    $bill = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($bill) {
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            $mail->SMTPSecure = SMTP_SECURE;
            $mail->Port = SMTP_PORT;
            $mail->CharSet = 'UTF-8';
            
            // Recipients
            $mail->setFrom(SMTP_USER, APP_NAME);
            $mail->addAddress($bill['recipient_email']);
            $mail->addReplyTo(SMTP_USER, APP_NAME);
            
            // Content
            $mail->isHTML(true);
            
            $due_date = new DateTime($bill['due_date']);
            $today = new DateTime();
            $days_remaining = $today->diff($due_date)->days;
            $is_overdue = $due_date < $today;
            
            if ($is_overdue) {
                $mail->Subject = '🚨 OVERDUE: ' . $bill['bill_name'] . ' - Payment Required';
            } else {
                $mail->Subject = '💰 Reminder: ' . $bill['bill_name'] . ' - Due ' . $due_date->format('M j, Y');
            }
            
            $mail->Body = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='utf-8'>
                    <style>
                        body { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                            line-height: 1.6; 
                            color: #333; 
                            margin: 0; 
                            padding: 0; 
                            background-color: #f6f9fc;
                        }
                        .container { 
                            max-width: 600px; 
                            margin: 0 auto; 
                            background: white;
                            border-radius: 12px;
                            overflow: hidden;
                            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
                        }
                        .header { 
                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                            color: white; 
                            padding: 40px 30px; 
                            text-align: center; 
                        }
                        .header h1 {
                            margin: 0;
                            font-size: 28px;
                            font-weight: 600;
                        }
                        .header p {
                            margin: 10px 0 0 0;
                            opacity: 0.9;
                        }
                        .content { 
                            padding: 40px 30px; 
                        }
                        .bill-details { 
                            background: #f8fafc; 
                            padding: 25px; 
                            border-radius: 10px; 
                            margin: 25px 0; 
                            border-left: 4px solid #667eea;
                        }
                        .status-overdue { 
                            color: #dc2626; 
                            font-weight: bold;
                            background: #fef2f2;
                            padding: 4px 8px;
                            border-radius: 4px;
                            display: inline-block;
                        }
                        .status-pending { 
                            color: #d97706; 
                            font-weight: bold;
                            background: #fffbeb;
                            padding: 4px 8px;
                            border-radius: 4px;
                            display: inline-block;
                        }
                        .footer { 
                            text-align: center; 
                            margin-top: 40px; 
                            color: #6b7280; 
                            font-size: 14px;
                            padding-top: 20px;
                            border-top: 1px solid #e5e7eb;
                        }
                        .amount {
                            font-size: 24px;
                            font-weight: bold;
                            color: #1f2937;
                        }
                        .due-date {
                            font-size: 18px;
                            color: #6b7280;
                        }
                        .urgency-note {
                            background: #fff3cd;
                            border: 1px solid #ffeaa7;
                            border-radius: 8px;
                            padding: 15px;
                            margin: 20px 0;
                            text-align: center;
                        }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>💰 Bill Payment Reminder</h1>
                            <p>" . APP_NAME . " - Never Miss a Payment</p>
                        </div>
                        <div class='content'>
                            <h2 style='color: #1f2937; margin-top: 0;'>Hello,</h2>
                            <p style='color: #6b7280; font-size: 16px;'>This is a friendly reminder about your upcoming bill payment. Please review the details below:</p>
                            
                            <div class='bill-details'>
                                <h3 style='color: #374151; margin-top: 0;'>Bill Information:</h3>
                                <p><strong>Bill Name:</strong> " . htmlspecialchars($bill['bill_name']) . "</p>
                                <p class='amount'>Amount Due: $" . number_format($bill['amount'], 2) . "</p>
                                <p class='due-date'><strong>Due Date:</strong> " . $due_date->format('F j, Y') . "</p>
                                <p><strong>Status:</strong> <span class='" . ($is_overdue ? 'status-overdue' : 'status-pending') . "'>" . 
                                  ($is_overdue ? '🚨 OVERDUE by ' . $days_remaining . ' days' : '⏰ ' . $days_remaining . ' days remaining') . "</span></p>
                                " . ($bill['description'] ? "<p><strong>Description:</strong> " . htmlspecialchars($bill['description']) . "</p>" : "") . "
                            </div>
                            
                            " . ($is_overdue ? "
                            <div class='urgency-note'>
                                <strong>⚠️ Important:</strong> This bill is now overdue. Please make payment immediately to avoid any late fees or service interruptions.
                            </div>
                            " : "") . "
                            
                            <p style='color: #6b7280;'>Please ensure this payment is made on time to maintain good standing.</p>
                            
                            <div class='footer'>
                                <p>This is an automated reminder from <strong>" . APP_NAME . "</strong>.</p>
                                <p>If you've already made this payment, please disregard this message.</p>
                                <p style='margin-top: 20px; font-size: 12px; color: #9ca3af;'>
                                    Need help? Contact support at " . SMTP_USER . "
                                </p>
                            </div>
                        </div>
                    </div>
                </body>
                </html>
            ";
            
            // Plain text version for email clients that don't support HTML
            $mail->AltBody = "BILL PAYMENT REMINDER\n\n" .
                            "Bill Name: " . $bill['bill_name'] . "\n" .
                            "Amount Due: $" . number_format($bill['amount'], 2) . "\n" .
                            "Due Date: " . $due_date->format('F j, Y') . "\n" .
                            "Status: " . ($is_overdue ? 'OVERDUE by ' . $days_remaining . ' days' : $days_remaining . ' days remaining') . "\n" .
                            ($bill['description'] ? "Description: " . $bill['description'] . "\n" : "") . "\n" .
                            ($is_overdue ? "IMPORTANT: This bill is now overdue. Please make payment immediately.\n\n" : "\n") .
                            "Please ensure this payment is made on time.\n\n" .
                            "This is an automated reminder from " . APP_NAME . ".\n" .
                            "If you've already made this payment, please disregard this message.";
            
            $mail->send();
            
            // Update reminder sent status and count
            $stmt = $pdo->prepare("UPDATE bills SET reminder_sent = TRUE, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$bill_id]);
            
            header('Location: index.php?success=📧 Reminder sent successfully to ' . $bill['recipient_email']);
            exit;
            
        } catch (Exception $e) {
            header('Location: index.php?error=❌ Email could not be sent. Error: ' . $e->getMessage());
            exit;
        }
    } else {
        header('Location: index.php?error=❌ Bill not found');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
?>