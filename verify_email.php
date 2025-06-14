<?php
include_once 'includes/functions.php';
include_once 'includes/db.php';
session_start();

$message = '';
$msg_type = 'error'; 

if (isset($_GET['token'])) {
    $token = sanitize_input($_GET['token']);

    $stmt = $conn->prepare("SELECT id, is_verified FROM users WHERE verification_token = ?");
    if ($stmt === false) {
        $message = "Database error: Could not prepare statement. " . $conn->error;
    } else {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if ($user['is_verified'] == 1) {
                $message = "This email has already been verified. You can login.";
                $msg_type = 'success';
            } else {
            
                $update_stmt = $conn->prepare("UPDATE users SET is_verified = 1, verification_token = NULL WHERE id = ?");
                if ($update_stmt === false) {
                    $message = "Database error: Could not update user. " . $conn->error;
                } else {
                    $update_stmt->bind_param("i", $user['id']);
                    if ($update_stmt->execute()) {
                        $message = "Email verified successfully! You can now login.";
                        $msg_type = 'success';
                    } else {
                        $message = "Failed to verify email. Please try again. " . $update_stmt->error;
                    }
                    $update_stmt->close();
                }
            }
        } else {
            $message = "Invalid or expired verification token.";
        }
        $stmt->close();
    }
} else {
    $message = "No verification token provided.";
}

$conn->close();
set_message($message, $msg_type);

// Redirect to MVC route
header("Location: public/index.php?controller=verifyEmail&action=index" . (isset($_GET['token']) ? "&token=" . urlencode($_GET['token']) : ''));
exit();


include_once 'includes/header.php';
?>

<h2>Email Verification</h2>
<?php display_message();  ?>
<p><a href="login.php">Go to Login</a></p>

<?php include_once 'includes/footer.php'; ?>
