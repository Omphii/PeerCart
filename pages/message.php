<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/bootstrap.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '/pages/login.php');
    exit();
}

$title = 'PeerCart - Messages';
$currentPage = 'messages';

// Set additional styles
$additionalStyles = ['messages'];

// Check if includePartial exists, otherwise include header directly
if (function_exists('includePartial')) {
    includePartial('header', compact('title', 'currentPage', 'additionalStyles'));
} else {
    $pageHead = '';
    require_once __DIR__ . '/../includes/header.php';
}

$user_id = $_SESSION['user_id'];
$db = Database::getInstance();

// Get current user info
$current_user = $db->getRow("SELECT name, user_type FROM users WHERE id = ?", [$user_id]);

// Get action parameter
$action = $_GET['action'] ?? 'inbox';
$conversation_id = $_GET['conversation_id'] ?? null;
$recipient_id = $_GET['recipient_id'] ?? null;

// Handle message actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $recipient_id = $_POST['recipient_id'] ?? null;
        $message = trim($_POST['message'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        
        if ($recipient_id && !empty($message)) {
            try {
                // Check if conversation exists
                $conversation = $db->getRow("
                    SELECT id FROM conversations 
                    WHERE (user1_id = ? AND user2_id = ?) 
                    OR (user1_id = ? AND user2_id = ?)
                ", [$user_id, $recipient_id, $recipient_id, $user_id]);
                
                if (!$conversation) {
                    // Create new conversation
                    $db->insert('conversations', [
                        'user1_id' => $user_id,
                        'user2_id' => $recipient_id,
                        'last_message_at' => date('Y-m-d H:i:s')
                    ]);
                    $conversation_id = $db->lastInsertId();
                } else {
                    $conversation_id = $conversation['id'];
                }
                
                // Send message
                $db->insert('messages', [
                    'conversation_id' => $conversation_id,
                    'sender_id' => $user_id,
                    'recipient_id' => $recipient_id,
                    'subject' => $subject,
                    'message' => $message,
                    'is_read' => 0,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                
                // Update conversation timestamp
                $db->update('conversations', [
                    'last_message_at' => date('Y-m-d H:i:s')
                ], ['id' => $conversation_id]);
                
                $_SESSION['success'] = 'Message sent successfully!';
                header('Location: messages.php?action=conversation&conversation_id=' . $conversation_id);
                exit();
                
            } catch(Exception $e) {
                $error = "Error sending message: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['delete_conversation'])) {
        $conversation_id = $_POST['conversation_id'] ?? null;
        
        if ($conversation_id) {
            try {
                // Soft delete conversation for current user
                $db->query("
                    UPDATE messages 
                    SET deleted_by_sender = 1 
                    WHERE conversation_id = ? AND sender_id = ?
                ", [$conversation_id, $user_id]);
                
                $db->query("
                    UPDATE messages 
                    SET deleted_by_recipient = 1 
                    WHERE conversation_id = ? AND recipient_id = ?
                ", [$conversation_id, $user_id]);
                
                $_SESSION['success'] = 'Conversation deleted successfully!';
                header('Location: messages.php');
                exit();
                
            } catch(Exception $e) {
                $error = "Error deleting conversation: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['mark_as_read'])) {
        $message_id = $_POST['message_id'] ?? null;
        
        if ($message_id) {
            try {
                $db->update('messages', [
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s')
                ], [
                    'id' => $message_id,
                    'recipient_id' => $user_id
                ]);
                
                echo json_encode(['success' => true]);
                exit();
                
            } catch(Exception $e) {
                echo json_encode(['error' => $e->getMessage()]);
                exit();
            }
        }
    }
}

// Display success message if set
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}

// Get conversations for sidebar
$conversations = $db->getRows("
    SELECT 
        c.*,
        u1.name as user1_name,
        u2.name as user2_name,
        (SELECT message FROM messages m 
         WHERE m.conversation_id = c.id 
         ORDER BY m.created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages m 
         WHERE m.conversation_id = c.id 
         ORDER BY m.created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages m 
         WHERE m.conversation_id = c.id 
         AND m.recipient_id = ? 
         AND m.is_read = 0 
         AND (m.deleted_by_recipient IS NULL OR m.deleted_by_recipient = 0)) as unread_count
    FROM conversations c
    JOIN users u1 ON c.user1_id = u1.id
    JOIN users u2 ON c.user2_id = u2.id
    WHERE (c.user1_id = ? OR c.user2_id = ?)
    AND EXISTS (
        SELECT 1 FROM messages m 
        WHERE m.conversation_id = c.id 
        AND (
            (m.sender_id = ? AND (m.deleted_by_sender IS NULL OR m.deleted_by_sender = 0))
            OR 
            (m.recipient_id = ? AND (m.deleted_by_recipient IS NULL OR m.deleted_by_recipient = 0))
        )
    )
    ORDER BY c.last_message_at DESC
", [$user_id, $user_id, $user_id, $user_id, $user_id]);

// Get unread count for badge
$total_unread = $db->getRow("
    SELECT COUNT(*) as count 
    FROM messages 
    WHERE recipient_id = ? 
    AND is_read = 0
    AND (deleted_by_recipient IS NULL OR deleted_by_recipient = 0)
", [$user_id])['count'];

// Get users for new message
$users = $db->getRows("
    SELECT id, name, user_type, city 
    FROM users 
    WHERE id != ? 
    AND is_active = 1
    ORDER BY name
", [$user_id]);

// Get conversation messages if viewing a conversation
$conversation_messages = [];
$other_user = null;
$conversation_info = null;

if ($conversation_id) {
    // Get conversation details
    $conversation_info = $db->getRow("
        SELECT c.*, 
               u1.name as user1_name, u1.user_type as user1_type,
               u2.name as user2_name, u2.user_type as user2_type
        FROM conversations c
        JOIN users u1 ON c.user1_id = u1.id
        JOIN users u2 ON c.user2_id = u2.id
        WHERE c.id = ?
        AND (c.user1_id = ? OR c.user2_id = ?)
    ", [$conversation_id, $user_id, $user_id]);
    
    if ($conversation_info) {
        // Determine the other user
        $other_user_id = ($conversation_info['user1_id'] == $user_id) 
            ? $conversation_info['user2_id'] 
            : $conversation_info['user1_id'];
        
        $other_user = $db->getRow("SELECT id, name, user_type, city FROM users WHERE id = ?", [$other_user_id]);
        
        // Get messages
        $conversation_messages = $db->getRows("
            SELECT m.*, 
                   u.name as sender_name,
                   u.user_type as sender_type
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.conversation_id = ?
            AND (
                (m.sender_id = ? AND (m.deleted_by_sender IS NULL OR m.deleted_by_sender = 0))
                OR 
                (m.recipient_id = ? AND (m.deleted_by_recipient IS NULL OR m.deleted_by_recipient = 0))
            )
            ORDER BY m.created_at ASC
        ", [$conversation_id, $user_id, $user_id]);
        
        // Mark messages as read
        $db->query("
            UPDATE messages 
            SET is_read = 1, 
                read_at = NOW() 
            WHERE conversation_id = ? 
            AND recipient_id = ? 
            AND is_read = 0
        ", [$conversation_id, $user_id]);
    }
}

// Get sent messages
$sent_messages = $db->getRows("
    SELECT m.*, 
           u.name as recipient_name,
           u.user_type as recipient_type
    FROM messages m
    JOIN users u ON m.recipient_id = u.id
    WHERE m.sender_id = ?
    AND (m.deleted_by_sender IS NULL OR m.deleted_by_sender = 0)
    ORDER BY m.created_at DESC
    LIMIT 50
", [$user_id]);

// Helper function to format time
function formatMessageTime($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}
?>

<!-- END OF PHP SECTION -->
<div class="messages-container">
    <?php if(isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <?php if(isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>
    
    <div class="messages-header">
        <h1><i class="fas fa-comments"></i> Messages</h1>
        <p class="subtitle">Stay connected with buyers and sellers</p>
        <?php if($total_unread > 0): ?>
        <span class="unread-badge"><?php echo $total_unread; ?> unread</span>
        <?php endif; ?>
    </div>
    
    <div class="messages-grid">
        <!-- Left Sidebar - Conversations -->
        <div class="conversations-sidebar">
            <div class="sidebar-header">
                <div class="user-info">
                    <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($current_user['name']); ?></h3>
                    <p class="user-type"><?php echo ucfirst($current_user['user_type']); ?></p>
                </div>
                <a href="?action=new" class="btn-new-message">
                    <i class="fas fa-plus"></i> New Message
                </a>
            </div>
            
            <div class="conversations-list">
                <div class="list-header">
                    <h4><i class="fas fa-inbox"></i> Conversations</h4>
                    <span class="count"><?php echo count($conversations); ?></span>
                </div>
                
                <?php if(empty($conversations)): ?>
                <div class="no-conversations">
                    <i class="fas fa-comment-slash"></i>
                    <p>No conversations yet</p>
                    <a href="?action=new" class="btn-start-chat">Start a chat</a>
                </div>
                <?php else: ?>
                <?php foreach($conversations as $conv): 
                    $other_user_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                    $other_user_name = ($conv['user1_id'] == $user_id) ? $conv['user2_name'] : $conv['user1_name'];
                    $is_active = ($conversation_id == $conv['id']);
                ?>
                <a href="?action=conversation&conversation_id=<?php echo $conv['id']; ?>" 
                   class="conversation-item <?php echo $is_active ? 'active' : ''; ?>">
                    <div class="conversation-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="conversation-details">
                        <div class="conversation-header">
                            <h5><?php echo htmlspecialchars($other_user_name); ?></h5>
                            <span class="time"><?php echo formatMessageTime($conv['last_message_time']); ?></span>
                        </div>
                        <p class="last-message">
                            <?php echo htmlspecialchars(substr($conv['last_message'], 0, 60)); ?>
                            <?php echo strlen($conv['last_message']) > 60 ? '...' : ''; ?>
                        </p>
                        <?php if($conv['unread_count'] > 0): ?>
                        <span class="unread-indicator"><?php echo $conv['unread_count']; ?></span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <div class="sidebar-footer">
                <a href="?action=sent" class="sidebar-link">
                    <i class="fas fa-paper-plane"></i> Sent Messages
                </a>
                <a href="?action=inbox" class="sidebar-link">
                    <i class="fas fa-inbox"></i> Inbox
                </a>
            </div>
        </div>
        
        <!-- Right Content Area -->
        <div class="messages-content">
            <?php if($action === 'new' || $recipient_id): ?>
                <!-- New Message Form -->
                <div class="new-message-section">
                    <div class="section-header">
                        <h2><i class="fas fa-edit"></i> New Message</h2>
                        <a href="messages.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Back to Inbox
                        </a>
                    </div>
                    
                    <form method="POST" class="new-message-form">
                        <div class="form-group">
                            <label for="recipient">
                                <i class="fas fa-user"></i> To:
                            </label>
                            <?php if($recipient_id): 
                                $recipient = $db->getRow("SELECT name FROM users WHERE id = ?", [$recipient_id]);
                            ?>
                            <input type="hidden" name="recipient_id" value="<?php echo $recipient_id; ?>">
                            <div class="selected-recipient">
                                <i class="fas fa-user-check"></i>
                                <span><?php echo htmlspecialchars($recipient['name']); ?></span>
                            </div>
                            <?php else: ?>
                            <select id="recipient" name="recipient_id" required>
                                <option value="">Select a user...</option>
                                <?php foreach($users as $user): ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['name']); ?> 
                                    (<?php echo ucfirst($user['user_type']); ?>)
                                    <?php if(!empty($user['city'])): ?> - <?php echo htmlspecialchars($user['city']); ?><?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">
                                <i class="fas fa-tag"></i> Subject (optional):
                            </label>
                            <input type="text" id="subject" name="subject" placeholder="Message subject...">
                        </div>
                        
                        <div class="form-group">
                            <label for="message">
                                <i class="fas fa-comment-dots"></i> Message:
                            </label>
                            <textarea id="message" name="message" rows="8" placeholder="Type your message here..." required></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="send_message" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                            <a href="messages.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
            <?php elseif($action === 'conversation' && $conversation_info && $other_user): ?>
                <!-- Conversation View -->
                <div class="conversation-section">
                    <div class="conversation-header">
                        <div class="conversation-user">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-info">
                                <h3><?php echo htmlspecialchars($other_user['name']); ?></h3>
                                <p class="user-meta">
                                    <span class="user-type"><?php echo ucfirst($other_user['user_type']); ?></span>
                                    <?php if(!empty($other_user['city'])): ?>
                                    <span class="user-location">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($other_user['city']); ?>
                                    </span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <div class="conversation-actions">
                            <a href="?action=new&recipient_id=<?php echo $other_user['id']; ?>" class="btn-action">
                                <i class="fas fa-reply"></i> Reply
                            </a>
                            <form method="POST" class="delete-form">
                                <input type="hidden" name="conversation_id" value="<?php echo $conversation_id; ?>">
                                <button type="submit" name="delete_conversation" class="btn-action btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this conversation?');">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="messages-list" id="messagesList">
                        <?php if(empty($conversation_messages)): ?>
                        <div class="no-messages">
                            <i class="fas fa-comment-slash"></i>
                            <p>No messages yet</p>
                            <p class="hint">Start the conversation by sending a message</p>
                        </div>
                        <?php else: ?>
                        <?php foreach($conversation_messages as $msg): 
                            $is_sender = ($msg['sender_id'] == $user_id);
                        ?>
                        <div class="message-item <?php echo $is_sender ? 'sent' : 'received'; ?>">
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="sender"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                    <span class="time"><?php echo formatMessageTime($msg['created_at']); ?></span>
                                </div>
                                <?php if(!empty($msg['subject'])): ?>
                                <div class="message-subject"><?php echo htmlspecialchars($msg['subject']); ?></div>
                                <?php endif; ?>
                                <div class="message-text"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="reply-section">
                        <form method="POST" class="reply-form">
                            <input type="hidden" name="recipient_id" value="<?php echo $other_user['id']; ?>">
                            <div class="form-group">
                                <textarea name="message" placeholder="Type your reply..." rows="3" required></textarea>
                            </div>
                            <div class="form-actions">
                                <button type="submit" name="send_message" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Send Reply
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
            <?php elseif($action === 'sent'): ?>
                <!-- Sent Messages -->
                <div class="sent-messages-section">
                    <div class="section-header">
                        <h2><i class="fas fa-paper-plane"></i> Sent Messages</h2>
                        <span class="count"><?php echo count($sent_messages); ?> messages</span>
                    </div>
                    
                    <?php if(empty($sent_messages)): ?>
                    <div class="no-messages">
                        <i class="fas fa-paper-plane"></i>
                        <p>No sent messages</p>
                        <a href="?action=new" class="btn-start-chat">Send your first message</a>
                    </div>
                    <?php else: ?>
                    <div class="sent-messages-list">
                        <?php foreach($sent_messages as $msg): ?>
                        <div class="sent-message-item">
                            <div class="message-header">
                                <div class="recipient-info">
                                    <i class="fas fa-user-circle"></i>
                                    <div>
                                        <h5>To: <?php echo htmlspecialchars($msg['recipient_name']); ?></h5>
                                        <p class="recipient-type"><?php echo ucfirst($msg['recipient_type']); ?></p>
                                    </div>
                                </div>
                                <span class="time"><?php echo formatMessageTime($msg['created_at']); ?></span>
                            </div>
                            <?php if(!empty($msg['subject'])): ?>
                            <div class="message-subject">Subject: <?php echo htmlspecialchars($msg['subject']); ?></div>
                            <?php endif; ?>
                            <div class="message-preview">
                                <?php echo htmlspecialchars(substr($msg['message'], 0, 150)); ?>
                                <?php if(strlen($msg['message']) > 150): ?>...<?php endif; ?>
                            </div>
                            <div class="message-status">
                                <?php if($msg['is_read']): ?>
                                <span class="status read">
                                    <i class="fas fa-check-double"></i> Read 
                                    <?php if($msg['read_at']): ?>at <?php echo date('H:i', strtotime($msg['read_at'])); ?><?php endif; ?>
                                </span>
                                <?php else: ?>
                                <span class="status unread">
                                    <i class="fas fa-check"></i> Delivered
                                </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            <?php else: ?>
                <!-- Default Inbox View -->
                <div class="inbox-section">
                    <div class="section-header">
                        <h2><i class="fas fa-inbox"></i> Inbox</h2>
                        <?php if($total_unread > 0): ?>
                        <span class="unread-count"><?php echo $total_unread; ?> unread messages</span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if(empty($conversations)): ?>
                    <div class="welcome-message">
                        <div class="welcome-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h3>Welcome to Messages</h3>
                        <p>Your messages will appear here when you start conversations with buyers or sellers.</p>
                        <div class="welcome-actions">
                            <a href="?action=new" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Start a Conversation
                            </a>
                            <a href="<?php echo BASE_URL; ?>/pages/listings.php" class="btn btn-outline">
                                <i class="fas fa-shopping-cart"></i> Browse Listings
                            </a>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="conversations-overview">
                        <?php foreach($conversations as $conv): 
                            $other_user_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                            $other_user_name = ($conv['user1_id'] == $user_id) ? $conv['user2_name'] : $conv['user1_name'];
                        ?>
                        <a href="?action=conversation&conversation_id=<?php echo $conv['id']; ?>" class="conversation-overview">
                            <div class="overview-avatar">
                                <i class="fas fa-user"></i>
                                <?php if($conv['unread_count'] > 0): ?>
                                <span class="unread-badge"><?php echo $conv['unread_count']; ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="overview-details">
                                <div class="overview-header">
                                    <h4><?php echo htmlspecialchars($other_user_name); ?></h4>
                                    <span class="time"><?php echo formatMessageTime($conv['last_message_time']); ?></span>
                                </div>
                                <p class="overview-message">
                                    <?php echo htmlspecialchars(substr($conv['last_message'], 0, 80)); ?>
                                    <?php if(strlen($conv['last_message']) > 80): ?>...<?php endif; ?>
                                </p>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll to bottom of messages list
    const messagesList = document.getElementById('messagesList');
    if (messagesList) {
        messagesList.scrollTop = messagesList.scrollHeight;
    }
    
    // Auto-resize textareas
    const textareas = document.querySelectorAll('textarea');
    textareas.forEach(textarea => {
        textarea.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
        // Trigger once to set initial height
        textarea.dispatchEvent(new Event('input'));
    });
    
    // Mark messages as read when viewed
    document.querySelectorAll('.message-item.received').forEach(message => {
        const messageId = message.dataset.messageId;
        if (messageId) {
            fetch('messages.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'mark_as_read=1&message_id=' + messageId
            });
        }
    });
    
    // Real-time message checking (simplified)
    function checkNewMessages() {
        const currentConversationId = <?php echo $conversation_id ?? 'null'; ?>;
        
        fetch('messages.php?check_new=1&conversation_id=' + currentConversationId)
            .then(response => response.json())
            .then(data => {
                if (data.hasNewMessages) {
                    // Show notification
                    showNotification('New message received');
                    // Reload page if not in conversation view
                    if (!currentConversationId) {
                        location.reload();
                    }
                }
            });
    }
    
    // Check for new messages every 30 seconds
    setInterval(checkNewMessages, 30000);
    
    // Notification function
    function showNotification(message) {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification('PeerCart Messages', {
                body: message,
                icon: '<?php echo BASE_URL; ?>/assets/images/logo.png'
            });
        } else if ('Notification' in window && Notification.permission !== 'denied') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    new Notification('PeerCart Messages', {
                        body: message,
                        icon: '<?php echo BASE_URL; ?>/assets/images/logo.png'
                    });
                }
            });
        }
    }
    
    // Request notification permission on page load
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission();
    }
    
    // Message search functionality
    const searchInput = document.getElementById('messageSearch');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            document.querySelectorAll('.conversation-item, .sent-message-item').forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = text.includes(searchTerm) ? 'flex' : 'none';
            });
        });
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + Enter to send message
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            const textarea = document.querySelector('textarea:focus');
            if (textarea && textarea.closest('form')) {
                textarea.closest('form').submit();
            }
        }
        
        // Escape to close/cancel
        if (e.key === 'Escape') {
            const cancelBtn = document.querySelector('.btn-back, .btn-outline');
            if (cancelBtn) cancelBtn.click();
        }
    });
});
</script>

<style>
/* ============================================
   PEERCART MESSAGES PAGE - MODERN GLASSMORPHISM
   ============================================ */

:root {
    /* Modern Color Palette */
    --primary: #4361ee;
    --primary-light: #4895ef;
    --primary-dark: #3a0ca3;
    --secondary: #7209b7;
    --accent: #f72585;
    --success: #4cc9f0;
    --warning: #f8961e;
    --danger: #e63946;
    --info: #3a86ff;
    
    /* Neutrals */
    --dark: #1a1a2e;
    --dark-gray: #2d3047;
    --gray: #6c757d;
    --light-gray: #f8f9fa;
    --white: #ffffff;
    --bg-primary: #ffffff;
    --bg-secondary: #f8f9fa;
    --text-primary: #1a1a2e;
    --text-secondary: #6c757d;
    --border-color: rgba(0, 0, 0, 0.1);
    
    /* Glassmorphism */
    --glass-bg: rgba(255, 255, 255, 0.95);
    --glass-border: rgba(255, 255, 255, 0.2);
    --glass-shadow: rgba(0, 0, 0, 0.1);
    
    /* Effects */
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.05);
    --shadow-md: 0 8px 25px rgba(0,0,0,0.1);
    --shadow-lg: 0 15px 35px rgba(0,0,0,0.15);
    --shadow-xl: 0 25px 50px rgba(0,0,0,0.2);
    --glow-primary: 0 0 20px rgba(67, 97, 238, 0.3);
    
    /* Spacing */
    --space-xs: 0.5rem;
    --space-sm: 1rem;
    --space-md: 1.5rem;
    --space-lg: 2rem;
    --space-xl: 3rem;
    
    /* Border Radius */
    --radius-sm: 8px;
    --radius-md: 12px;
    --radius-lg: 16px;
    --radius-xl: 24px;
    --radius-full: 100px;
    
    /* Transitions */
    --transition-fast: 0.2s ease;
    --transition-normal: 0.3s ease;
    --transition-slow: 0.5s ease;
}

/* Dark Mode Variables */
[data-theme="dark"] {
    --primary: #5a76ff;
    --primary-light: #6d8aff;
    --primary-dark: #4a5fcc;
    --secondary: #8d2bd4;
    --accent: #ff2b8c;
    --success: #5cd3f7;
    --warning: #ffaa47;
    --danger: #ff4d5c;
    --info: #4d8eff;
    
    --dark: #ffffff;
    --dark-gray: #e0e0e0;
    --gray: #a0a0a0;
    --light-gray: #2a2a3e;
    --white: #1a1a2e;
    --bg-primary: #121225;
    --bg-secondary: #1a1a2e;
    --text-primary: #ffffff;
    --text-secondary: #b0b0c0;
    --border-color: rgba(255, 255, 255, 0.1);
    
    --glass-bg: rgba(26, 26, 46, 0.95);
    --glass-border: rgba(255, 255, 255, 0.1);
    --glass-shadow: rgba(0, 0, 0, 0.3);
    
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.2);
    --shadow-md: 0 8px 25px rgba(0,0,0,0.3);
    --shadow-lg: 0 15px 35px rgba(0,0,0,0.4);
    --shadow-xl: 0 25px 50px rgba(0,0,0,0.5);
    --glow-primary: 0 0 20px rgba(90, 118, 255, 0.4);
}

/* ============ BASE STYLES ============ */
.messages-container {
    position: relative;
    z-index: 1;
    padding: var(--space-md);
    max-width: 1400px;
    margin: 0 auto;
    min-height: calc(100vh - 200px);
}

.messages-header {
    margin-bottom: var(--space-lg);
    padding: var(--space-lg);
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.messages-header h1 {
    font-size: 2.5rem;
    font-weight: 800;
    margin: 0;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.messages-header .subtitle {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-top: var(--space-xs);
}

.unread-badge {
    background: linear-gradient(135deg, var(--accent), var(--danger));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-full);
    font-weight: 700;
    font-size: 0.9rem;
    box-shadow: var(--shadow-sm);
}

/* ===== MESSAGES GRID ===== */
.messages-grid {
    display: grid;
    grid-template-columns: 350px 1fr;
    gap: var(--space-lg);
    height: calc(100vh - 300px);
    min-height: 600px;
}

@media (max-width: 992px) {
    .messages-grid {
        grid-template-columns: 1fr;
        height: auto;
    }
}

/* ===== CONVERSATIONS SIDEBAR ===== */
.conversations-sidebar {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

.sidebar-header {
    padding: var(--space-md);
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.1) 0%, 
        rgba(114, 9, 183, 0.1) 100%);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.user-info h3 {
    font-size: 1.1rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
}

.user-type {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: var(--space-xs);
}

.btn-new-message {
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all var(--transition-normal);
}

.btn-new-message:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Conversations List */
.conversations-list {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-md);
}

.list-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-md);
    padding-bottom: var(--space-sm);
    border-bottom: 1px solid var(--border-color);
}

.list-header h4 {
    font-size: 1rem;
    font-weight: 600;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    margin: 0;
}

.count {
    background: var(--primary);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-size: 0.85rem;
    font-weight: 700;
}

.no-conversations {
    text-align: center;
    padding: var(--space-xl) var(--space-md);
    color: var(--text-secondary);
}

.no-conversations i {
    font-size: 3rem;
    margin-bottom: var(--space-md);
    color: var(--primary);
    opacity: 0.5;
}

.no-conversations p {
    margin-bottom: var(--space-lg);
}

.btn-start-chat {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-radius: var(--radius-full);
    text-decoration: none;
    font-weight: 600;
    transition: all var(--transition-normal);
}

.btn-start-chat:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Conversation Items */
.conversation-item {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--text-primary);
    transition: all var(--transition-normal);
    margin-bottom: var(--space-xs);
    border: 1px solid transparent;
}

.conversation-item:hover {
    background: rgba(67, 97, 238, 0.05);
    border-color: var(--border-color);
    transform: translateX(5px);
}

.conversation-item.active {
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.15) 0%, 
        rgba(114, 9, 183, 0.15) 100%);
    border-color: var(--primary);
    border-left: 3px solid var(--primary);
}

.conversation-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
}

.conversation-details {
    flex: 1;
    min-width: 0;
}

.conversation-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-xs);
}

.conversation-header h5 {
    font-size: 0.95rem;
    font-weight: 600;
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.conversation-header .time {
    font-size: 0.8rem;
    color: var(--text-secondary);
    white-space: nowrap;
}

.last-message {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.unread-indicator {
    position: absolute;
    right: var(--space-md);
    background: linear-gradient(135deg, var(--accent), var(--danger));
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-full);
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Sidebar Footer */
.sidebar-footer {
    padding: var(--space-md);
    border-top: 1px solid var(--border-color);
    display: flex;
    flex-direction: column;
    gap: var(--space-xs);
}

.sidebar-link {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-sm);
    color: var(--text-secondary);
    text-decoration: none;
    border-radius: var(--radius-md);
    transition: all var(--transition-normal);
}

.sidebar-link:hover {
    background: rgba(67, 97, 238, 0.05);
    color: var(--primary);
}

/* ===== MESSAGES CONTENT ===== */
.messages-content {
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-radius: var(--radius-lg);
    box-shadow: var(--shadow-lg);
    border: 1px solid var(--glass-border);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Section Headers */
.section-header {
    padding: var(--space-md);
    background: linear-gradient(135deg, 
        rgba(67, 97, 238, 0.05) 0%, 
        rgba(114, 9, 183, 0.05) 100%);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.section-header h2 {
    font-size: 1.5rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.btn-back {
    padding: 0.5rem 1rem;
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all var(--transition-normal);
}

.btn-back:hover {
    background: rgba(67, 97, 238, 0.2);
    transform: translateX(-3px);
}

/* New Message Form */
.new-message-form {
    padding: var(--space-lg);
    flex: 1;
}

.form-group {
    margin-bottom: var(--space-md);
}

.form-group label {
    display: flex;
    align-items: center;
    gap: var(--space-xs);
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-sm);
    font-size: 0.95rem;
}

.form-group label i {
    color: var(--primary);
    width: 20px;
}

.form-group select,
.form-group input,
.form-group textarea {
    width: 100%;
    padding: var(--space-md);
    border: 1.5px solid var(--border-color);
    border-radius: var(--radius-md);
    font-size: 1rem;
    transition: all var(--transition-normal);
    background: var(--glass-bg);
    color: var(--text-primary);
    backdrop-filter: blur(10px);
}

.form-group select:focus,
.form-group input:focus,
.form-group textarea:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.15);
}

.form-group textarea {
    resize: none;
    min-height: 120px;
}

.selected-recipient {
    padding: var(--space-md);
    background: rgba(67, 97, 238, 0.1);
    border-radius: var(--radius-md);
    border: 1px solid var(--primary);
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    font-weight: 600;
    color: var(--primary);
}

.form-actions {
    display: flex;
    gap: var(--space-sm);
    margin-top: var(--space-xl);
}

.btn {
    padding: var(--space-md) var(--space-xl);
    border-radius: var(--radius-full);
    font-weight: 600;
    font-size: 1rem;
    cursor: pointer;
    transition: all var(--transition-normal);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--space-sm);
    border: 2px solid transparent;
    text-decoration: none;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.btn-outline {
    background: transparent;
    border-color: var(--border-color);
    color: var(--text-primary);
}

.btn-outline:hover {
    border-color: var(--primary);
    color: var(--primary);
    transform: translateY(-3px);
}

/* Conversation View */
.conversation-section {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.conversation-header {
    padding: var(--space-md);
    background: var(--glass-bg);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.conversation-user {
    display: flex;
    align-items: center;
    gap: var(--space-md);
}

.user-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
}

.user-info h3 {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0;
    color: var(--text-primary);
}

.user-meta {
    display: flex;
    align-items: center;
    gap: var(--space-md);
    margin-top: var(--space-xs);
    font-size: 0.9rem;
    color: var(--text-secondary);
}

.user-type {
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
    font-weight: 600;
}

.user-location {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.conversation-actions {
    display: flex;
    gap: var(--space-sm);
}

.btn-action {
    padding: 0.5rem 1rem;
    background: rgba(67, 97, 238, 0.1);
    color: var(--primary);
    border-radius: var(--radius-md);
    font-size: 0.9rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    transition: all var(--transition-normal);
}

.btn-action:hover {
    background: rgba(67, 97, 238, 0.2);
    transform: translateY(-2px);
}

.btn-danger {
    background: rgba(230, 57, 70, 0.1);
    color: var(--danger);
}

.btn-danger:hover {
    background: rgba(230, 57, 70, 0.2);
}

.delete-form {
    margin: 0;
}

/* Messages List */
.messages-list {
    flex: 1;
    overflow-y: auto;
    padding: var(--space-lg);
    display: flex;
    flex-direction: column;
    gap: var(--space-md);
}

.no-messages {
    text-align: center;
    padding: var(--space-xl);
    color: var(--text-secondary);
}

.no-messages i {
    font-size: 3rem;
    margin-bottom: var(--space-md);
    color: var(--primary);
    opacity: 0.5;
}

.no-messages .hint {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin-top: var(--space-xs);
}

/* Message Items */
.message-item {
    max-width: 70%;
    animation: slideIn 0.3s ease-out;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.message-item.sent {
    align-self: flex-end;
}

.message-item.received {
    align-self: flex-start;
}

.message-content {
    padding: var(--space-md);
    border-radius: var(--radius-lg);
    position: relative;
}

.message-item.sent .message-content {
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    color: white;
    border-bottom-right-radius: var(--radius-sm);
}

.message-item.received .message-content {
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border: 1px solid var(--border-color);
    border-bottom-left-radius: var(--radius-sm);
}

.message-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-sm);
    font-size: 0.85rem;
}

.message-item.sent .message-header {
    color: rgba(255, 255, 255, 0.9);
}

.message-item.received .message-header {
    color: var(--text-secondary);
}

.sender {
    font-weight: 600;
}

.message-subject {
    font-weight: 600;
    margin-bottom: var(--space-sm);
    padding-bottom: var(--space-xs);
    border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.message-item.received .message-subject {
    border-bottom-color: var(--border-color);
    color: var(--text-primary);
}

.message-text {
    line-height: 1.5;
    white-space: pre-wrap;
}

/* Reply Section */
.reply-section {
    padding: var(--space-md);
    border-top: 1px solid var(--border-color);
    background: var(--glass-bg);
}

.reply-form {
    display: flex;
    gap: var(--space-sm);
}

.reply-form .form-group {
    flex: 1;
    margin: 0;
}

.reply-form textarea {
    resize: none;
    min-height: 60px;
    max-height: 120px;
}

/* Sent Messages */
.sent-messages-section {
    flex: 1;
    overflow-y: auto;
}

.sent-messages-list {
    padding: var(--space-md);
}

.sent-message-item {
    padding: var(--space-md);
    background: var(--glass-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-sm);
    transition: all var(--transition-normal);
}

.sent-message-item:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.message-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-sm);
}

.recipient-info {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
}

.recipient-info i {
    font-size: 1.5rem;
    color: var(--primary);
}

.recipient-info h5 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: var(--text-primary);
}

.recipient-type {
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 0.25rem;
}

.message-subject {
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: var(--space-sm);
    padding: var(--space-xs) var(--space-sm);
    background: rgba(67, 97, 238, 0.1);
    border-radius: var(--radius-sm);
    display: inline-block;
}

.message-preview {
    color: var(--text-secondary);
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: var(--space-sm);
}

.message-status {
    font-size: 0.85rem;
}

.status {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-full);
}

.status.read {
    background: rgba(76, 201, 240, 0.1);
    color: var(--success);
}

.status.unread {
    background: rgba(108, 117, 125, 0.1);
    color: var(--gray);
}

/* Inbox View */
.inbox-section {
    flex: 1;
    overflow-y: auto;
}

.unread-count {
    background: linear-gradient(135deg, var(--accent), var(--danger));
    color: white;
    padding: 0.5rem 1rem;
    border-radius: var(--radius-full);
    font-size: 0.9rem;
    font-weight: 700;
}

.welcome-message {
    text-align: center;
    padding: var(--space-xl);
}

.welcome-icon {
    font-size: 4rem;
    color: var(--primary);
    opacity: 0.5;
    margin-bottom: var(--space-lg);
}

.welcome-message h3 {
    font-size: 1.75rem;
    font-weight: 700;
    margin-bottom: var(--space-md);
    color: var(--text-primary);
}

.welcome-message p {
    color: var(--text-secondary);
    max-width: 500px;
    margin: 0 auto var(--space-lg);
    line-height: 1.6;
}

.welcome-actions {
    display: flex;
    gap: var(--space-sm);
    justify-content: center;
    flex-wrap: wrap;
}

/* Conversations Overview */
.conversations-overview {
    padding: var(--space-md);
}

.conversation-overview {
    display: flex;
    align-items: center;
    gap: var(--space-sm);
    padding: var(--space-md);
    background: var(--glass-bg);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    text-decoration: none;
    color: var(--text-primary);
    transition: all var(--transition-normal);
    margin-bottom: var(--space-sm);
}

.conversation-overview:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary);
}

.overview-avatar {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--secondary));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    position: relative;
}

.overview-avatar .unread-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    min-width: 20px;
    height: 20px;
    font-size: 0.7rem;
}

.overview-details {
    flex: 1;
}

.overview-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--space-xs);
}

.overview-header h4 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.overview-message {
    font-size: 0.9rem;
    color: var(--text-secondary);
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Alerts */
.alert {
    padding: var(--space-md);
    border-radius: var(--radius-md);
    margin-bottom: var(--space-lg);
    font-size: 0.95rem;
    background: var(--glass-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid var(--glass-border);
    box-shadow: var(--shadow-md);
    display: flex;
    align-items: flex-start;
    gap: var(--space-sm);
    animation: slideIn 0.3s ease-out;
}

.alert i {
    font-size: 1.2rem;
    margin-top: 2px;
}

.alert-danger {
    border-left: 4px solid var(--danger);
    background: linear-gradient(135deg, 
        rgba(230, 57, 70, 0.1), 
        rgba(230, 57, 70, 0.05));
    color: var(--text-primary);
}

.alert-danger i {
    color: var(--danger);
}

.alert-success {
    border-left: 4px solid var(--success);
    background: linear-gradient(135deg, 
        rgba(76, 201, 240, 0.1), 
        rgba(76, 201, 240, 0.05));
    color: var(--text-primary);
}

.alert-success i {
    color: var(--success);
}

/* Responsive Design */
@media (max-width: 768px) {
    .messages-container {
        padding: var(--space-sm);
    }
    
    .messages-header {
        padding: var(--space-md);
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-sm);
    }
    
    .messages-header h1 {
        font-size: 2rem;
    }
    
    .messages-grid {
        height: auto;
    }
    
    .conversation-header {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-md);
    }
    
    .conversation-actions {
        width: 100%;
        justify-content: flex-end;
    }
    
    .reply-form {
        flex-direction: column;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .btn {
        width: 100%;
        justify-content: center;
    }
    
    .message-item {
        max-width: 85%;
    }
}

@media (max-width: 480px) {
    .messages-header h1 {
        font-size: 1.75rem;
    }
    
    .conversation-user {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-sm);
    }
    
    .user-meta {
        flex-direction: column;
        align-items: flex-start;
        gap: var(--space-xs);
    }
    
    .welcome-actions {
        flex-direction: column;
    }
    
    .conversation-item,
    .conversation-overview {
        padding: var(--space-sm);
    }
}
</style>

<?php 
if (function_exists('includePartial')) {
    includePartial('footer');
} else {
    require_once __DIR__ . '/../includes/footer.php';
}