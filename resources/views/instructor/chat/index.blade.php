@extends('instructor.layouts.app')

@section('title', 'Class Chat')
@section('page-title', 'Class Chat')
@section('page-subtitle', 'Communicate with students in your classes')

@section('content')
    <!-- Mobile Overlay -->
    <div class="chat-overlay d-none" id="chatOverlay"></div>

    <div class="chat-container d-flex h-100">
        <!-- Sidebar - Conversation List -->
        <div class="chat-sidebar bg-white border-end">
            <div class="chat-sidebar-header p-3 border-bottom bg-light">
                <div class="d-flex align-items-center justify-content-between">
                    <h5 class="mb-0 fw-bold text-primary">Class Chats</h5>
                    <div class="d-flex gap-2">
                        <div class="real-time-indicator" id="real-time-status">
                            <i class="fas fa-circle text-success" style="font-size: 8px;"></i>
                            <small class="text-muted ms-1">Real-time</small>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">{{ count($conversations) }} active chats</small>
                </div>
            </div>

            <div class="chat-conversations overflow-auto">
                @forelse($conversations as $conversation)
                    @php
                        $class = $conversation['class'];
                        $lastMessage = $conversation['last_message'];
                        $unreadCount = $conversation['unread_count'];
                    @endphp
                    <div class="conversation-item" data-class-id="{{ $class->id }}"
                        onclick="openClassChat({{ $class->id }})">
                        <div class="d-flex align-items-center p-3 border-bottom conversation-hover">
                            <div class="flex-shrink-0 position-relative">
                                <div class="chat-avatar bg-gradient-primary">
                                    {{ strtoupper(substr($class->name, 0, 2)) }}
                                </div>
                                @if ($unreadCount > 0)
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                    </span>
                                @endif
                            </div>
                            <div class="flex-grow-1 ms-3 min-width-0">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div class="min-width-0 flex-grow-1">
                                        <h6 class="mb-1 fw-semibold text-truncate">{{ $class->name }}</h6>
                                        <small class="text-muted d-block">{{ $class->category->name ?? 'General' }}</small>
                                    </div>
                                    <div class="text-end flex-shrink-0 ms-2">
                                        @if ($lastMessage)
                                            <small class="text-muted d-block">
                                                {{ $lastMessage->created_at->diffForHumans(null, true) }}
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                @if ($lastMessage)
                                    <p class="mb-0 text-muted small mt-1 text-truncate">
                                        @if ($lastMessage->sender_type === 'instructor')
                                            <i class="fas fa-check text-primary me-1"></i>You:
                                        @else
                                            <span class="fw-semibold">{{ $lastMessage->sender_name }}:</span>
                                        @endif
                                        @if ($lastMessage->is_announcement)
                                            <i class="fas fa-bullhorn text-warning me-1"></i>
                                        @endif
                                        {{ Str::limit($lastMessage->message, 35) }}
                                    </p>
                                @else
                                    <p class="mb-0 text-muted small mt-1">No messages yet</p>
                                @endif
                                <div class="mt-1 d-flex align-items-center justify-content-between">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>{{ $class->students_count }} students
                                    </small>
                                    @if ($lastMessage && $lastMessage->is_pinned)
                                        <i class="fas fa-thumbtack text-warning"></i>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center p-4">
                        <div class="mb-4">
                            <i class="fas fa-comments fa-4x text-muted opacity-50"></i>
                        </div>
                        <h6 class="text-muted mb-2">No Active Chats</h6>
                        <p class="text-muted small mb-3">You need classes with enrolled students to start chatting.</p>
                        <div class="text-center">
                            <small class="text-muted">Waiting for real-time connection...</small>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Main Chat Area -->
        <div class="chat-main flex-grow-1 d-flex flex-column bg-chat">
            <!-- Welcome Screen -->
            <div id="welcome-screen" class="chat-welcome d-flex align-items-center justify-content-center h-100">
                <div class="text-center">
                    <!-- Mobile Classes Button -->
                    <div class="d-md-none mb-4">
                        <button class="btn btn-primary btn-lg" onclick="toggleMobileSidebar()">
                            <i class="fas fa-list me-2"></i>View Classes
                        </button>
                    </div>

                    <div class="welcome-icon mb-4">
                        <i class="fas fa-comment-dots fa-5x text-primary opacity-75"></i>
                    </div>
                    <h3 class="text-dark mb-3">Welcome to Class Chat</h3>
                    <p class="text-muted mb-4 d-none d-md-block">Select a class from the sidebar to start messaging with
                        your students.</p>
                    <p class="text-muted mb-4 d-md-none">Tap "View Classes" to see your available classes and start
                        chatting.</p>

                    <div class="row text-center g-4">
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded-3 bg-white shadow-sm h-100">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-bullhorn fa-2x text-warning"></i>
                                </div>
                                <h6 class="fw-bold">Announcements</h6>
                                <small class="text-muted">Send important updates to all students in a class</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded-3 bg-white shadow-sm h-100">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-paperclip fa-2x text-success"></i>
                                </div>
                                <h6 class="fw-bold">File Sharing</h6>
                                <small class="text-muted">Share documents, images, and resources easily</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="feature-card p-4 rounded-3 bg-white shadow-sm h-100">
                                <div class="feature-icon mb-3">
                                    <i class="fas fa-reply fa-2x text-info"></i>
                                </div>
                                <h6 class="fw-bold">Reply & Pin</h6>
                                <small class="text-muted">Reply to specific messages and pin important ones</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Chat Interface (Initially Hidden) -->
            <div id="chat-interface" class="d-none h-100 d-flex flex-column">
                <!-- Chat Header -->
                <div class="chat-header bg-white border-bottom shadow-sm p-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <button class="btn btn-link d-md-none me-2" onclick="toggleMobileSidebar()">
                                <i class="fas fa-list"></i>
                            </button>
                            <div class="chat-avatar bg-gradient-primary me-3" id="chat-class-avatar">--</div>
                            <div>
                                <h5 class="mb-0 fw-bold" id="chat-class-name">Class Name</h5>
                                <small class="text-muted" id="chat-class-info">Category • 0 students</small>
                            </div>
                        </div>
                        <div class="chat-actions d-flex gap-2">
                            <button class="btn btn-outline-secondary btn-sm" onclick="showParticipants()"
                                title="View Participants">
                                <i class="fas fa-users"></i>
                            </button>
                            <div class="real-time-indicator d-flex align-items-center">
                                <i class="fas fa-circle text-success" style="font-size: 6px;"></i>
                                <small class="text-muted ms-1">Live</small>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown">
                                    <i class="fas fa-ellipsis-v"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="clearChat()">
                                            <i class="fas fa-trash me-2"></i>Clear Chat
                                        </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="exportChat()">
                                            <i class="fas fa-download me-2"></i>Export Chat
                                        </a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages Area -->
                <div class="chat-messages flex-grow-1 p-3 overflow-auto" id="messages-container">
                    <div class="d-flex justify-content-center align-items-center h-100">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading messages...</span>
                        </div>
                    </div>
                </div>

                <!-- Message Input -->
                <div class="chat-input bg-white border-top p-3">
                    <div class="reply-preview d-none mb-3" id="reply-preview">
                        <div class="bg-light p-3 rounded-3 border-start border-primary border-4">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <small class="text-primary fw-bold">Replying to <span id="reply-user"></span></small>
                                    <div class="text-muted small mt-1" id="reply-message"></div>
                                </div>
                                <button type="button" class="btn-close" onclick="cancelReply()"></button>
                            </div>
                        </div>
                    </div>

                    <form id="message-form" class="d-flex align-items-end gap-3" onsubmit="return false;">
                        <div class="flex-grow-1">
                            <div class="input-group">
                                <textarea class="form-control border-0 bg-light rounded-pill px-4 py-2" id="message-input" rows="1"
                                    placeholder="Type a message..." style="resize: none; min-height: 45px; max-height: 120px;"></textarea>
                                <input type="file" id="attachment-input" multiple
                                    accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.zip" class="d-none">
                            </div>
                            <div id="attachment-preview" class="mt-2 d-none"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-light rounded-circle p-2" onclick="triggerFileUpload()"
                                title="Attach File">
                                <i class="fas fa-paperclip text-muted"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-primary rounded-circle p-2 dropdown-toggle" type="button"
                                    data-bs-toggle="dropdown" title="Send">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="#" onclick="sendMessage(false)">
                                            <i class="fas fa-comment me-2 text-primary"></i>Send Message
                                        </a></li>
                                    <li><a class="dropdown-item" href="#" onclick="sendMessage(true)">
                                            <i class="fas fa-bullhorn me-2 text-warning"></i>Send as Announcement
                                        </a></li>
                                </ul>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Participants Modal -->
    <div class="modal fade" id="participantsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Class Participants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="participants-content">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Message Modal -->
    <div class="modal fade" id="deleteMessageModal" tabindex="-1">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h6 class="modal-title">Delete Message</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <i class="fas fa-trash fa-3x text-danger mb-3"></i>
                    <p class="mb-3">Are you sure you want to delete this message?</p>
                    <div class="d-flex gap-2 justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('styles')
    <style>
        :root {
            --chat-bg: #f0f2f5;
            --message-bg-own: #0084ff;
            --message-bg-other: #ffffff;
            --message-bg-announcement: #fff3cd;
            --sidebar-bg: #ffffff;
            --border-color: #e9ecef;
        }

        .chat-container {
            height: calc(100vh - 140px);
            max-height: 850px;
            min-height: 600px;
        }

        .chat-sidebar {
            width: 350px;
            min-width: 350px;
            max-width: 350px;
            background: var(--sidebar-bg);
            position: relative;
            z-index: 10;
        }

        /* Mobile Overlay */
        .chat-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 15;
            transition: opacity 0.3s ease;
        }

        @media (max-width: 768px) {
            .chat-overlay.show {
                display: block !important;
            }
        }

        /* Mobile responsive adjustments */
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 120px);
                min-height: 500px;
                position: relative;
            }

            .chat-sidebar {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                max-width: 100%;
                min-width: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 20;
            }

            .chat-sidebar.show {
                transform: translateX(0);
            }

            .chat-main {
                width: 100%;
                position: relative;
            }

            /* Hide welcome screen features on mobile */
            .welcome-screen .row {
                display: none;
            }

            .welcome-screen .text-center h3 {
                font-size: 1.25rem;
            }

            .welcome-screen .text-center p {
                font-size: 0.9rem;
            }

            /* Improve conversation items on mobile */
            .conversation-item .d-flex {
                padding: 1rem !important;
            }

            .chat-avatar {
                width: 40px;
                height: 40px;
                font-size: 14px;
            }

            .conversation-item h6 {
                font-size: 0.95rem;
            }

            .conversation-item .text-muted {
                font-size: 0.8rem;
            }
        }

        .chat-conversations {
            max-height: calc(100vh - 240px);
        }

        .bg-chat {
            background: var(--chat-bg);
            background-image:
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.3) 0%, transparent 50%);
        }

        .chat-avatar {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-gradient-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
        }

        .conversation-item {
            cursor: pointer;
            transition: all 0.2s ease;
            border-radius: 0;
        }

        .conversation-hover:hover {
            background-color: #f8f9fa !important;
        }

        .conversation-item.active .conversation-hover {
            background: linear-gradient(90deg, #e3f2fd 0%, #ffffff 100%);
            border-left: 4px solid #0084ff;
        }

        .chat-messages {
            height: 0;
            min-height: 400px;
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar {
            width: 6px;
        }

        .chat-messages::-webkit-scrollbar-track {
            background: transparent;
        }

        .chat-messages::-webkit-scrollbar-thumb {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 3px;
        }

        .message-bubble {
            max-width: 75%;
            margin-bottom: 16px;
            animation: fadeInMessage 0.3s ease-out;
        }

        @keyframes fadeInMessage {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .message-bubble.own {
            margin-left: auto;
        }

        .message-bubble.other {
            margin-right: auto;
        }

        .message-content {
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .message-bubble.own .message-content {
            background: var(--message-bg-own);
            color: white;
            border-bottom-right-radius: 6px;
        }

        .message-bubble.other .message-content {
            background: var(--message-bg-other);
            color: #333;
            border-bottom-left-radius: 6px;
            border: 1px solid #e9ecef;
        }

        .message-bubble.announcement .message-content {
            background: var(--message-bg-announcement);
            border: 1px solid #ffc107;
            color: #856404;
            border-radius: 12px;
        }

        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 4px;
            display: flex;
            align-items: center;
            gap: 4px;
        }

        .message-bubble.own .message-time {
            justify-content: flex-end;
        }

        .message-actions {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            padding: 6px;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 10;
        }

        .message-bubble:hover .message-actions {
            opacity: 1;
        }

        .message-bubble.own .message-actions {
            left: -60px;
        }

        .message-bubble.other .message-actions {
            right: -60px;
        }

        .message-actions .btn {
            padding: 4px 8px;
            font-size: 12px;
            border: none;
            margin: 0 2px;
        }

        .reply-indicator {
            background-color: rgba(255, 255, 255, 0.15);
            border-left: 3px solid currentColor;
            padding: 8px 12px;
            margin-bottom: 8px;
            border-radius: 8px;
            font-size: 12px;
        }

        .message-bubble.other .reply-indicator {
            background-color: rgba(0, 0, 0, 0.05);
            border-left-color: #0084ff;
        }

        .attachment-item {
            display: inline-flex;
            align-items: center;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 8px 12px;
            border-radius: 12px;
            margin: 4px 4px 0 0;
            font-size: 12px;
            text-decoration: none;
            transition: background-color 0.2s;
        }

        .message-bubble.other .attachment-item {
            background-color: rgba(0, 0, 0, 0.05);
            color: #0084ff;
        }

        .attachment-item:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .feature-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: 1px solid #e9ecef;
        }

        .feature-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
        }

        .welcome-icon {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Message input styling */
        .chat-input textarea {
            border: 2px solid transparent !important;
            transition: all 0.2s ease;
        }

        .chat-input textarea:focus {
            border-color: #0084ff !important;
            box-shadow: 0 0 0 0.2rem rgba(0, 132, 255, 0.25) !important;
        }

        /* Status indicators */
        .status-sent::after {
            content: "✓";
            color: #999;
        }

        .status-delivered::after {
            content: "✓✓";
            color: #999;
        }

        .status-read::after {
            content: "✓✓";
            color: #0084ff;
        }

        /* Typing indicator */
        .typing-indicator {
            display: flex;
            align-items: center;
            padding: 8px 16px;
            background: white;
            border-radius: 18px;
            margin-bottom: 16px;
            max-width: 75%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .typing-dots {
            display: flex;
            gap: 4px;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #999;
            animation: typing 1.4s infinite ease-in-out;
        }

        .typing-dot:nth-child(1) {
            animation-delay: -0.32s;
        }

        .typing-dot:nth-child(2) {
            animation-delay: -0.16s;
        }

        @keyframes typing {

            0%,
            80%,
            100% {
                transform: scale(0);
                opacity: 0.5;
            }

            40% {
                transform: scale(1);
                opacity: 1;
            }
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .chat-container {
                height: calc(100vh - 120px);
            }

            .chat-sidebar {
                width: 100%;
                max-width: 100%;
                position: absolute;
                z-index: 1050;
                height: 100%;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }

            .chat-sidebar.show {
                transform: translateX(0);
            }

            .message-bubble {
                max-width: 85%;
            }

            .message-actions {
                display: none;
            }

            .chat-messages {
                min-height: 300px;
            }
        }

        /* Scrollbar styling */
        .chat-conversations::-webkit-scrollbar,
        .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        .chat-conversations::-webkit-scrollbar-track,
        .modal-body::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .chat-conversations::-webkit-scrollbar-thumb,
        .modal-body::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .chat-conversations::-webkit-scrollbar-thumb:hover,
        .modal-body::-webkit-scrollbar-thumb:hover {
            background: #a8a8a8;
        }

        /* Animation for new messages */
        @keyframes newMessageGlow {
            0% {
                box-shadow: 0 0 5px rgba(0, 132, 255, 0.5);
            }

            100% {
                box-shadow: none;
            }
        }

        .message-bubble.new-message .message-content {
            animation: newMessageGlow 1s ease-out;
        }
    </style>
@endpush

@push('scripts')
    <script>
        let currentClassId = null;
        let currentReplyTo = null;
        let messagesPage = 1;
        let isLoadingMessages = false;
        let isTyping = false;
        let typingTimeout = null;

        document.addEventListener('DOMContentLoaded', function() {
            initializeChat();
        });

        function initializeChat() {
            // Auto-resize textarea
            const messageInput = document.getElementById('message-input');
            messageInput.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';

                // Handle typing indicator
                handleTyping();
            });

            // Handle Enter key for sending messages
            messageInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage(false);
                    return false;
                }
            });

            // Prevent form submission
            document.getElementById('message-form').addEventListener('submit', function(e) {
                e.preventDefault();
                return false;
            });

            // Handle file input change
            document.getElementById('attachment-input').addEventListener('change', function() {
                if (this.files.length > 0) {
                    showAttachmentPreview(Array.from(this.files));
                }
            });

            // Initialize real-time functionality only
            initializeRealTime();
        }

        function handleTyping() {
            if (!isTyping) {
                isTyping = true;
                // Here you could emit typing status to server
            }

            clearTimeout(typingTimeout);
            typingTimeout = setTimeout(() => {
                isTyping = false;
                // Here you could emit stop typing status to server
            }, 1000);
        }

        function openClassChat(classId) {
            if (currentClassId === classId) return;

            // Unsubscribe from previous class if any
            if (currentClassId) {
                unsubscribeFromClassChat(currentClassId);
            }

            currentClassId = classId;

            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-class-id="${classId}"]`).classList.add('active');

            // Show chat interface
            document.getElementById('welcome-screen').classList.add('d-none');
            document.getElementById('chat-interface').classList.remove('d-none');

            // On mobile, hide sidebar
            if (window.innerWidth <= 768) {
                document.querySelector('.chat-sidebar').classList.remove('show');
            }

            // Load messages and scroll to bottom
            loadClassMessages(classId, true);

            // Subscribe to real-time updates for this class
            subscribeToClassChat(classId);
        }

        function loadClassMessages(classId, forceScrollToBottom = false) {
            if (isLoadingMessages) return;

            isLoadingMessages = true;

            // Show loading
            document.getElementById('messages-container').innerHTML = `
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading messages...</span>
            </div>
        </div>
    `;

            fetch(`{{ route('instructor.chat.messages', ':classId') }}`.replace(':classId', classId))
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateChatHeader(data.class);
                        renderMessages(data.messages.data, data.current_user_id);

                        // Always scroll to bottom for new message, or when explicitly requested
                        setTimeout(() => {
                            scrollToBottom();
                        }, 100);
                    } else {
                        throw new Error(data.message || 'Failed to load messages');
                    }
                })
                .catch(error => {
                    console.error('Error loading messages:', error);
                    document.getElementById('messages-container').innerHTML = `
                <div class="alert alert-danger rounded-3 mx-3">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Error loading messages</strong>
                            <div class="small mt-1">${error.message}</div>
                        </div>
                    </div>
                    <button class="btn btn-outline-danger btn-sm mt-2" onclick="loadClassMessages(${classId})">
                        <i class="fas fa-sync-alt me-1"></i>Try Again
                    </button>
                </div>
            `;
                })
                .finally(() => {
                    isLoadingMessages = false;
                });
        }

        function updateChatHeader(classData) {
            document.getElementById('chat-class-avatar').textContent = classData.name.substring(0, 2).toUpperCase();
            document.getElementById('chat-class-name').textContent = classData.name;
            document.getElementById('chat-class-info').textContent =
                `${classData.category?.name || 'General'} • ${classData.students_count || 0} students`;
        }

        function renderMessages(messages, currentUserId) {
            const container = document.getElementById('messages-container');
            container.innerHTML = '';

            if (messages.length === 0) {
                container.innerHTML = `
            <div class="text-center text-muted py-5">
                <div class="mb-4">
                    <i class="fas fa-comment-slash fa-4x opacity-50"></i>
                </div>
                <h6 class="fw-bold mb-2">No messages yet</h6>
                <p class="mb-3">Start the conversation by sending a message!</p>
                <button class="btn btn-primary btn-sm" onclick="document.getElementById('message-input').focus()">
                    <i class="fas fa-paper-plane me-1"></i>Send First Message
                </button>
            </div>
        `;
                return;
            }

            // Group messages by date
            const groupedMessages = groupMessagesByDate(messages);

            Object.keys(groupedMessages).forEach(date => {
                // Add date separator
                const dateDiv = document.createElement('div');
                dateDiv.className = 'text-center my-3';
                dateDiv.innerHTML = `
            <span class="badge bg-light text-muted border px-3 py-2 rounded-pill">
                ${formatDateSeparator(date)}
            </span>
        `;
                container.appendChild(dateDiv);

                // Add messages for this date (newest at bottom)
                groupedMessages[date].forEach(message => {
                    const messageElement = createMessageElement(message, currentUserId);
                    container.appendChild(messageElement);
                });
            });
        }

        function groupMessagesByDate(messages) {
            const groups = {};
            messages.forEach(message => {
                const date = new Date(message.created_at).toDateString();
                if (!groups[date]) {
                    groups[date] = [];
                }
                groups[date].push(message);
            });
            return groups;
        }

        function formatDateSeparator(dateString) {
            const date = new Date(dateString);
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);

            if (date.toDateString() === today.toDateString()) {
                return 'Today';
            } else if (date.toDateString() === yesterday.toDateString()) {
                return 'Yesterday';
            } else {
                return date.toLocaleDateString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            }
        }

        function createMessageElement(message, currentUserId) {
            const isOwn = message.user_id === currentUserId;
            const isAnnouncement = message.is_announcement;

            const messageDiv = document.createElement('div');
            messageDiv.className = `message-bubble ${isOwn ? 'own' : 'other'} ${isAnnouncement ? 'announcement' : ''}`;
            messageDiv.dataset.messageId = message.id;

            let replyContent = '';
            if (message.reply_to) {
                replyContent = `
            <div class="reply-indicator">
                <div class="d-flex align-items-center mb-1">
                    <i class="fas fa-reply me-2"></i>
                    <small class="fw-bold">${message.reply_to.user.name}</small>
                </div>
                <div class="small">${message.reply_to.message.substring(0, 100)}${message.reply_to.message.length > 100 ? '...' : ''}</div>
            </div>
        `;
            }

            let attachmentContent = '';
            if (message.attachments && message.attachments.length > 0) {
                attachmentContent = message.attachments.map(attachment => `
            <div class="attachment-item mt-2" onclick="downloadAttachment('${attachment.file_path}', '${attachment.original_name}')">
                <i class="fas fa-paperclip me-2"></i>
                <span>${attachment.original_name}</span>
                <small class="ms-2 opacity-75">(${formatFileSize(attachment.file_size)})</small>
            </div>
        `).join('');
            }

            let actionButtons = '';
            if (!isOwn) {
                actionButtons = `
            <div class="message-actions">
                <button class="btn btn-light btn-sm" onclick="replyToMessage(${message.id}, '${message.user.name}', '${message.message.replace(/'/g, "\\'")}')" title="Reply">
                    <i class="fas fa-reply"></i>
                </button>
                <button class="btn btn-light btn-sm" onclick="togglePin(${message.id})" title="${message.is_pinned ? 'Unpin' : 'Pin'}">
                    <i class="fas fa-thumbtack ${message.is_pinned ? 'text-warning' : ''}"></i>
                </button>
            </div>
        `;
            } else {
                actionButtons = `
            <div class="message-actions">
                <button class="btn btn-light btn-sm" onclick="confirmDeleteMessage(${message.id})" title="Delete">
                    <i class="fas fa-trash text-danger"></i>
                </button>
                <button class="btn btn-light btn-sm" onclick="togglePin(${message.id})" title="${message.is_pinned ? 'Unpin' : 'Pin'}">
                    <i class="fas fa-thumbtack ${message.is_pinned ? 'text-warning' : ''}"></i>
                </button>
            </div>
        `;
            }

            const messageTime = new Date(message.created_at);
            const timeString = messageTime.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });

            messageDiv.innerHTML = `
        <div class="d-flex align-items-end gap-2 position-relative">
            <div class="message-content">
                <div class="sender-name mb-1">
                    <small class="fw-bold ${isOwn ? 'text-primary' : 'text-secondary'}">${message.user.name}</small>
                    ${isAnnouncement ? '<span class="badge bg-warning ms-2"><i class="fas fa-bullhorn me-1"></i>ANNOUNCEMENT</span>' : ''}
                </div>
                ${replyContent}
                <div class="message-text">${formatMessageText(message.message)}</div>
                ${attachmentContent}
                <div class="message-time">
                    <span>${timeString}</span>
                    ${message.is_pinned ? '<i class="fas fa-thumbtack ms-1"></i>' : ''}
                    ${isOwn ? '<span class="status-read"></span>' : ''}
                </div>
            </div>
            ${actionButtons}
        </div>
    `;

            return messageDiv;
        }

        function formatMessageText(text) {
            // Simple URL detection and link creation
            const urlRegex = /(https?:\/\/[^\s]+)/g;
            return text.replace(urlRegex, '<a href="$1" target="_blank" class="text-decoration-none">$1</a>');
        }

        function formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }

        function sendMessage(isAnnouncement = false) {
            const messageInput = document.getElementById('message-input');
            const attachmentInput = document.getElementById('attachment-input');
            const message = messageInput.value.trim();

            if (!message && attachmentInput.files.length === 0) {
                messageInput.focus();
                return;
            }

            if (!currentClassId) {
                showAlert('Please select a class first', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('class_id', currentClassId);
            formData.append('message', message);
            formData.append('is_announcement', isAnnouncement ? '1' : '0');

            if (currentReplyTo) {
                formData.append('reply_to', currentReplyTo);
            }

            // Add attachments
            for (let i = 0; i < attachmentInput.files.length; i++) {
                formData.append('attachments[]', attachmentInput.files[i]);
            }

            // Show sending state
            const sendButton = document.querySelector('.btn-primary.rounded-circle');
            const originalHTML = sendButton.innerHTML;
            sendButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            sendButton.disabled = true;

            fetch('{{ route('instructor.chat.send') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear input
                        messageInput.value = '';
                        messageInput.style.height = 'auto';
                        attachmentInput.value = '';
                        document.getElementById('attachment-preview').classList.add('d-none');

                        // Cancel reply if active
                        cancelReply();

                        // Reload messages
                        loadClassMessages(currentClassId, true); // true = scroll to bottom

                        showAlert('Message sent successfully!', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to send message');
                    }
                })
                .catch(error => {
                    console.error('Error sending message:', error);
                    showAlert('Error sending message: ' + error.message, 'danger');
                })
                .finally(() => {
                    // Re-enable send button
                    sendButton.disabled = false;
                    sendButton.innerHTML = originalHTML;
                });
        }

        function replyToMessage(messageId, userName, messageText) {
            currentReplyTo = messageId;

            document.getElementById('reply-preview').classList.remove('d-none');
            document.getElementById('reply-user').textContent = userName;
            document.getElementById('reply-message').textContent = messageText.substring(0, 100) + (messageText.length >
                100 ? '...' : '');

            document.getElementById('message-input').focus();
        }

        function cancelReply() {
            currentReplyTo = null;
            document.getElementById('reply-preview').classList.add('d-none');
        }

        function confirmDeleteMessage(messageId) {
            const modal = new bootstrap.Modal(document.getElementById('deleteMessageModal'));
            document.getElementById('confirmDeleteBtn').onclick = () => {
                deleteMessage(messageId);
                modal.hide();
            };
            modal.show();
        }

        function deleteMessage(messageId) {
            fetch(`{{ route('instructor.chat.delete', ':messageId') }}`.replace(':messageId', messageId), {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Invalid JSON response:', text);
                            throw new Error('Server returned invalid response');
                        }
                    });
                })
                .then(data => {
                    if (data.success) {
                        // Remove message element with animation
                        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                        if (messageElement) {
                            messageElement.style.animation = 'fadeOut 0.3s ease-out';
                            setTimeout(() => {
                                messageElement.remove();
                            }, 300);
                        }
                        showAlert('Message deleted successfully', 'success');
                    } else {
                        throw new Error(data.message || 'Failed to delete message');
                    }
                })
                .catch(error => {
                    console.error('Error deleting message:', error);
                    // Even if there's an error in response parsing, the message might be deleted
                    // So we refresh the messages to be sure
                    if (currentClassId) {
                        loadClassMessages(currentClassId);
                    }
                    showAlert('Message may have been deleted. Refreshing...', 'warning');
                });
        }

        function togglePin(messageId) {
            fetch(`{{ route('instructor.chat.pin', ':messageId') }}`.replace(':messageId', messageId), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update pin icon in the message
                        const messageElement = document.querySelector(`[data-message-id="${messageId}"]`);
                        const pinIcon = messageElement.querySelector('.fa-thumbtack');
                        if (pinIcon) {
                            pinIcon.classList.toggle('text-warning', data.is_pinned);
                        }

                        // Show in message time area
                        const messageTime = messageElement.querySelector('.message-time');
                        const existingPin = messageTime.querySelector('.fa-thumbtack');
                        if (data.is_pinned && !existingPin) {
                            messageTime.innerHTML += '<i class="fas fa-thumbtack ms-1"></i>';
                        } else if (!data.is_pinned && existingPin) {
                            existingPin.remove();
                        }

                        showAlert(data.message, 'success');
                    } else {
                        throw new Error(data.message || 'Failed to toggle pin');
                    }
                })
                .catch(error => {
                    console.error('Error toggling pin:', error);
                    showAlert('Error toggling pin: ' + error.message, 'danger');
                });
        }

        function triggerFileUpload() {
            document.getElementById('attachment-input').click();
        }

        function showAttachmentPreview(files) {
            const preview = document.getElementById('attachment-preview');
            preview.classList.remove('d-none');

            let html = '<div class="d-flex flex-wrap gap-2">';
            files.forEach((file, index) => {
                const fileIcon = getFileIcon(file.type);
                html += `
            <div class="attachment-preview-item bg-light rounded-2 p-2 d-flex align-items-center">
                <i class="${fileIcon} me-2"></i>
                <span class="small text-truncate" style="max-width: 150px;">${file.name}</span>
                <button type="button" class="btn-close btn-sm ms-2" onclick="removeAttachment(${index})"></button>
            </div>
        `;
            });
            html += '</div>';

            preview.innerHTML = html;

            // Update placeholder
            const messageInput = document.getElementById('message-input');
            messageInput.placeholder = `${files.length} file(s) selected. Add a message or send directly.`;
        }

        function getFileIcon(mimeType) {
            if (mimeType.startsWith('image/')) return 'fas fa-image text-success';
            if (mimeType.includes('pdf')) return 'fas fa-file-pdf text-danger';
            if (mimeType.includes('word')) return 'fas fa-file-word text-primary';
            if (mimeType.includes('zip')) return 'fas fa-file-archive text-warning';
            return 'fas fa-file text-muted';
        }

        function removeAttachment(index) {
            const attachmentInput = document.getElementById('attachment-input');
            const dt = new DataTransfer();

            for (let i = 0; i < attachmentInput.files.length; i++) {
                if (i !== index) {
                    dt.items.add(attachmentInput.files[i]);
                }
            }

            attachmentInput.files = dt.files;

            if (attachmentInput.files.length === 0) {
                document.getElementById('attachment-preview').classList.add('d-none');
                document.getElementById('message-input').placeholder = 'Type a message...';
            } else {
                showAttachmentPreview(Array.from(attachmentInput.files));
            }
        }

        function downloadAttachment(filePath, originalName) {
            // Create download link
            const link = document.createElement('a');
            link.href = `{{ url('storage') }}/${filePath}`;
            link.download = originalName;
            link.target = '_blank';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        function showParticipants() {
            if (!currentClassId) return;

            // Show loading in modal
            document.getElementById('participants-content').innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;

            fetch(`{{ route('instructor.chat.participants', ':classId') }}`.replace(':classId', currentClassId))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderParticipants(data);
                        new bootstrap.Modal(document.getElementById('participantsModal')).show();
                    } else {
                        throw new Error(data.message || 'Failed to load participants');
                    }
                })
                .catch(error => {
                    console.error('Error loading participants:', error);
                    document.getElementById('participants-content').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Error loading participants: ${error.message}
                </div>
            `;
                });
        }

        function renderParticipants(data) {
            const content = document.getElementById('participants-content');

            let html = `
        <div class="mb-4">
            <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-3">
                <div class="chat-avatar bg-gradient-primary me-3">
                    ${data.class.name.substring(0, 2).toUpperCase()}
                </div>
                <div>
                    <h6 class="fw-bold mb-1">${data.class.name}</h6>
                    <small class="text-muted">Class Information</small>
                </div>
            </div>

            <!-- Instructor -->
            <div class="mb-4">
                <h6 class="fw-bold text-primary mb-3">
                    <i class="fas fa-chalkboard-teacher me-2"></i>Instructor
                </h6>
                <div class="participant-item p-3 bg-light rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="chat-avatar bg-success me-3">
                            ${data.instructor.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div>
                            <div class="fw-semibold">${data.instructor.name}</div>
                            <small class="text-muted">Instructor</small>
                        </div>
                        <div class="ms-auto">
                            <span class="badge bg-success">Online</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Students -->
            <div>
                <h6 class="fw-bold text-primary mb-3">
                    <i class="fas fa-users me-2"></i>Students (${data.students.length})
                </h6>
                <div class="row g-2">
    `;

            data.students.forEach(student => {
                html += `
            <div class="col-md-6">
                <div class="participant-item p-3 bg-white border rounded-3">
                    <div class="d-flex align-items-center">
                        <div class="chat-avatar bg-info me-3">
                            ${student.name.substring(0, 2).toUpperCase()}
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${student.name}</div>
                            ${student.family ? `<small class="text-muted">Family: ${student.family}</small>` : ''}
                        </div>
                        <div class="text-end">
                            <span class="badge bg-light text-muted">Student</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
            });

            html += `
                </div>
            </div>
        </div>
    `;

            content.innerHTML = html;
        }

        // Manual refresh functions removed - using only real-time updates

        function scrollToBottom() {
            const container = document.getElementById('messages-container');
            if (container) {
                // Check if user is near bottom (within 100px) for smooth scroll
                const isNearBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 100;

                if (isNearBottom) {
                    // Smooth scroll if near bottom
                    container.scrollTo({
                        top: container.scrollHeight,
                        behavior: 'smooth'
                    });
                } else {
                    // Instant scroll if far from bottom (user was reading old messages)
                    container.scrollTop = container.scrollHeight;
                }

                console.log('📜 Scrolled to bottom');
            }
        }

        function closeMobileChat() {
            document.getElementById('welcome-screen').classList.remove('d-none');
            document.getElementById('chat-interface').classList.add('d-none');
            // Don't show sidebar automatically on mobile
            if (window.innerWidth <= 768) {
                document.querySelector('.chat-sidebar').classList.remove('show');
            }
            currentClassId = null;
        }

        function toggleMobileSidebar() {
            const sidebar = document.querySelector('.chat-sidebar');
            const overlay = document.getElementById('chatOverlay');

            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
            overlay.classList.toggle('d-none');
        }

        function openClassChat(classId) {
            console.log('Opening chat for class:', classId);

            // Unsubscribe from previous class
            if (currentClassId && currentClassId !== classId) {
                unsubscribeFromClassChat(currentClassId);
            }

            currentClassId = classId;

            // Hide welcome screen and show chat interface
            document.getElementById('welcome-screen').classList.add('d-none');
            document.getElementById('chat-interface').classList.remove('d-none');

            // Hide sidebar on mobile after selection
            if (window.innerWidth <= 768) {
                const sidebar = document.querySelector('.chat-sidebar');
                const overlay = document.getElementById('chatOverlay');

                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                overlay.classList.add('d-none');
            }

            // Update active conversation
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-class-id="${classId}"]`).classList.add('active');

            // Load chat details
            loadClassChat(classId);

            // Subscribe to real-time updates
            if (window.chatEcho) {
                subscribeToClassChat(classId);
            }
        }

        function showNewChat() {
            // This could open a modal to start a new chat with a class
            showAlert('Feature coming soon!', 'info');
        }

        function clearChat() {
            if (!currentClassId) return;

            if (confirm('Are you sure you want to clear this chat? This action cannot be undone.')) {
                showAlert('Feature coming soon!', 'info');
            }
        }

        function exportChat() {
            if (!currentClassId) return;

            showAlert('Chat export feature coming soon!', 'info');
        }

        function showAlert(message, type = 'info') {
            // Create toast notification
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-${getAlertIcon(type)} me-2"></i>
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;

            // Add to toast container (create if doesn't exist)
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                document.body.appendChild(toastContainer);
            }

            toastContainer.appendChild(toast);

            // Show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            // Remove toast element after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        function getAlertIcon(type) {
            switch (type) {
                case 'success':
                    return 'check-circle';
                case 'danger':
                    return 'exclamation-triangle';
                case 'warning':
                    return 'exclamation-circle';
                case 'info':
                    return 'info-circle';
                default:
                    return 'info-circle';
            }
        }

        // Auto-refresh removed - using only real-time updates

        // Real-time functionality with Laravel Echo
        function initializeRealTime() {
            console.log('Initializing real-time connection...');

            if (typeof window.Echo === 'undefined') {
                console.error('Laravel Echo not available!');
                updateRealTimeStatus('disconnected');
                return;
            }

            try {
                // Initialize Echo for real-time broadcasting
                if (!window.chatEcho) {
                    console.log('Creating new Echo instance...');

                    window.chatEcho = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ env('REVERB_APP_KEY') }}',
                        wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
                        wsPort: {{ env('REVERB_PORT', 8080) }},
                        wssPort: {{ env('REVERB_PORT', 8080) }},
                        forceTLS: false,
                        enabledTransports: ['ws', 'wss'],
                        disableStats: true,
                        encrypted: false
                    });

                    // Connection event listeners
                    window.chatEcho.connector.socket.on('connect', () => {
                        console.log('WebSocket connected!');
                        updateRealTimeStatus('connected');
                    });

                    window.chatEcho.connector.socket.on('disconnect', () => {
                        console.log('WebSocket disconnected!');
                        updateRealTimeStatus('disconnected');
                    });

                    window.chatEcho.connector.socket.on('error', (error) => {
                        console.error('WebSocket error:', error);
                        updateRealTimeStatus('disconnected');
                    });
                }

                console.log('=== REAL-TIME CHAT INITIALIZATION COMPLETE ===');
                console.log('Echo instance created:', !!window.chatEcho);
                console.log('Socket exists:', !!window.chatEcho.connector.socket);

                // Set status as connecting initially
                updateRealTimeStatus('connecting');

                // Check connection status after 3 seconds
                setTimeout(() => {
                    if (window.chatEcho && window.chatEcho.connector.socket) {
                        console.log('Socket connected:', window.chatEcho.connector.socket.connected);
                        if (window.chatEcho.connector.socket.connected) {
                            console.log('✅ WebSocket successfully connected!');
                            updateRealTimeStatus('connected');
                        } else {
                            console.log('❌ WebSocket failed to connect');
                            updateRealTimeStatus('disconnected');
                            // Try alternative: Use simple HTTP polling as fallback
                            startSimplePolling();
                        }
                    } else {
                        console.log('❌ No socket available');
                        updateRealTimeStatus('disconnected');
                        startSimplePolling();
                    }
                }, 3000);

            } catch (error) {
                console.error('❌ Failed to initialize real-time chat:', error);
                updateRealTimeStatus('disconnected');
                // Use simple polling as fallback
                startSimplePolling();
            }
        }

        // Listen to real-time message events for specific class
        function subscribeToClassChat(classId) {
            console.log('=== ATTEMPTING TO SUBSCRIBE TO CLASS CHAT ===');
            console.log('Class ID:', classId);
            console.log('Window.chatEcho exists:', !!window.chatEcho);

            if (!window.chatEcho || !classId) {
                console.error('Cannot subscribe: missing chatEcho or classId');
                return;
            }

            try {
                // Subscribe to public class conversations (no auth required)
                const publicChannel = window.chatEcho.channel(`public-class-conversations.${classId}`);
                console.log('Created public channel for class:', classId);

                publicChannel.listen('conversation.updated', (e) => {
                    console.log('🎉 PUBLIC CONVERSATION UPDATED:', e);
                    handleRealTimeUpdate(e, classId);
                });

                // Subscribe to public instructor conversations
                @if (auth()->user()->instructor)
                    const instructorChannel = window.chatEcho.channel(
                        `public-instructor-conversations.{{ auth()->user()->instructor->id }}`);
                    console.log('Created instructor channel for instructor:', {{ auth()->user()->instructor->id }});

                    instructorChannel.listen('conversation.updated', (e) => {
                        console.log('🎉 INSTRUCTOR CONVERSATION UPDATED:', e);
                        handleRealTimeUpdate(e, classId);
                    });
                @endif

                // Test real WebSocket after subscription
                setTimeout(() => {
                    console.log('🧪 Testing real-time connection...');
                    console.log('Socket state:', window.chatEcho.connector.socket.connected);

                    if (!window.chatEcho.connector.socket.connected) {
                        console.log('⚠️ WebSocket not connected, falling back to polling');
                        startSimplePolling();
                    } else {
                        console.log('✅ Real-time WebSocket is active!');
                    }
                }, 2000);

                console.log(`✅ Successfully subscribed to real-time updates for class ${classId}`);
            } catch (error) {
                console.error('❌ Failed to subscribe to real-time updates:', error);
            }
        }

        // Simple polling fallback when WebSocket fails
        function startSimplePolling() {
            console.log('🔄 Starting simple polling fallback');
            updateRealTimeStatus('connecting');

            // Check for new messages every 3 seconds
            const pollingInterval = setInterval(() => {
                if (currentClassId) {
                    console.log('📡 Polling for updates...');
                    const currentMessages = document.querySelectorAll('.message-bubble');
                    const currentMessageCount = currentMessages.length;
                    const lastMessageId = currentMessages.length > 0 ?
                        currentMessages[currentMessages.length - 1].dataset.messageId : 0;

                    // Reload messages silently
                    fetch(`{{ route('instructor.chat.messages', ':classId') }}`.replace(':classId',
                            currentClassId))
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const newMessageCount = data.messages.data.length;
                                const latestMessage = data.messages.data[data.messages.data.length - 1];

                                // Check if there's a new message (different count or different last message ID)
                                if (newMessageCount > currentMessageCount ||
                                    (latestMessage && latestMessage.id != lastMessageId)) {
                                    console.log('🎉 New messages detected via polling!');
                                    loadClassMessages(currentClassId, true); // Auto scroll to bottom
                                    updateRealTimeStatus('activity');
                                    setTimeout(() => updateRealTimeStatus('connected'), 1000);
                                }
                            }
                        })
                        .catch(error => {
                            console.error('Polling failed:', error);
                            updateRealTimeStatus('disconnected');
                        });
                }
            }, 3000);

            // Update status to show polling is active
            setTimeout(() => {
                updateRealTimeStatus('connected');
            }, 1000);

            // Store interval for cleanup
            window.pollingInterval = pollingInterval;
        }

        // Handle real-time updates
        function handleRealTimeUpdate(eventData, classId) {
            console.log('🎉 Real-time update received for class:', classId);

            // Only refresh messages if it's for the currently active class
            if (currentClassId && currentClassId == classId) {
                console.log('🔄 Refreshing current chat messages');

                // Refresh messages immediately and scroll to bottom
                setTimeout(() => {
                    if (currentClassId == classId) {
                        loadClassMessages(classId, true); // Force scroll to bottom for new messages
                    }
                }, 100);
            }

            // Show visual indicator that new message received
            updateRealTimeStatus('activity');
            setTimeout(() => {
                updateRealTimeStatus('connected');
            }, 1000);
        }

        // Unsubscribe from previous class when switching
        function unsubscribeFromClassChat(classId) {
            if (!window.chatEcho || !classId) return;

            try {
                window.chatEcho.leaveChannel(`public-class-conversations.${classId}`);
                console.log(`Unsubscribed from class ${classId}`);
            } catch (error) {
                console.error('Failed to unsubscribe from class chat:', error);
            }
        }

        // Update real-time status indicator
        function updateRealTimeStatus(status) {
            const indicators = document.querySelectorAll('.real-time-indicator');
            indicators.forEach(indicator => {
                const icon = indicator.querySelector('i');
                const text = indicator.querySelector('small');

                if (status === 'connected') {
                    icon.className = 'fas fa-circle text-success';
                    text.textContent = text.textContent.includes('Real-time') ? 'Real-time' : 'Live';
                } else if (status === 'connecting') {
                    icon.className = 'fas fa-circle text-info';
                    text.textContent = text.textContent.includes('Real-time') ? 'Connecting...' : 'Connecting...';
                } else if (status === 'activity') {
                    icon.className = 'fas fa-circle text-warning';
                    text.textContent = text.textContent.includes('Real-time') ? 'Updating...' : 'New message';
                } else {
                    icon.className = 'fas fa-circle text-danger';
                    text.textContent = text.textContent.includes('Real-time') ? 'Offline' : 'Offline';
                }
            });
        }

        // Add CSS for animations and sender name styling
        const style = document.createElement('style');
        style.textContent = `
    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: scale(1);
        }
        to {
            opacity: 0;
            transform: scale(0.95);
        }
    }

    .sender-name {
        margin-bottom: 0.25rem !important;
    }

    .sender-name small {
        font-size: 0.75rem;
        font-weight: 600;
    }

    .message-bubble.own .sender-name small {
        color: #0d6efd !important;
    }

    .message-bubble.other .sender-name small {
        color: #6c757d !important;
    }

    .real-time-indicator {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
`;
        document.head.appendChild(style);

        // Handle page cleanup
        window.addEventListener('beforeunload', () => {
            if (window.pollingInterval) {
                clearInterval(window.pollingInterval);
            }
        });

        // Handle visibility change for real-time connection management
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && currentClassId) {
                console.log('📱 Page became visible, checking connections...');

                // Reconnect to real-time if needed
                if (!window.chatEcho) {
                    console.log('🔄 Reinitializing real-time...');
                    initializeRealTime();
                }
                // Re-subscribe to current class
                subscribeToClassChat(currentClassId);

                // If we're using polling, refresh immediately
                if (window.pollingInterval) {
                    console.log('📡 Polling active, doing immediate refresh...');
                    if (currentClassId) {
                        loadClassMessages(currentClassId, false);
                    }
                }
            }
        });

        // Mobile responsive handlers
        window.addEventListener('resize', () => {
            if (window.innerWidth > 768) {
                document.querySelector('.chat-sidebar').classList.remove('show');
            }
        });

        // Initialize mobile sidebar toggle and overlay
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && !e.target.closest('.chat-sidebar') && !e.target.closest(
                    '[onclick*="openClassChat"]') && !e.target.closest('[onclick*="toggleMobileSidebar"]')) {
                const sidebar = document.querySelector('.chat-sidebar');
                const overlay = document.getElementById('chatOverlay');

                sidebar.classList.remove('show');
                overlay.classList.remove('show');
                overlay.classList.add('d-none');
            }
        });

        // Close sidebar when overlay is clicked
        document.getElementById('chatOverlay')?.addEventListener('click', () => {
            toggleMobileSidebar();
        });
    </script>
@endpush
