// Chat functionality for the dashboard
class ChatSystem {
  constructor() {
    this.messages = [];
    this.isOpen = false;
    this.init();
  }
  
  init() {
    this.createChatButton();
    this.createChatWidget();
    this.setupEventListeners();
  }
  
  createChatButton() {
    const chatButton = document.createElement('div');
    chatButton.id = 'chatButton';
    chatButton.innerHTML = '<i class="fas fa-comments"></i>';
    chatButton.style.cssText = `
      position: fixed;
      bottom: 20px;
      right: 20px;
      width: 50px;
      height: 50px;
      background: linear-gradient(135deg, #007bff, #0056b3);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 20px;
      cursor: pointer;
      box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
      z-index: 1001;
      transition: all 0.3s ease;
    `;
    
    document.body.appendChild(chatButton);
  }
  
  createChatWidget() {
    const chatWidget = document.createElement('div');
    chatWidget.id = 'chatWidget';
    chatWidget.innerHTML = `
      <div class="chat-header">
        <h4>Support Chat</h4>
        <button id="closeChatBtn">&times;</button>
      </div>
      <div class="chat-messages" id="chatMessages">
        <div class="bot-message">
          <div class="message-content">
            Hello! How can I help you today?
          </div>
          <div class="message-time">${this.getCurrentTime()}</div>
        </div>
      </div>
      <div class="chat-input">
        <input type="text" id="chatInput" placeholder="Type your message...">
        <button id="sendBtn"><i class="fas fa-paper-plane"></i></button>
      </div>
    `;
    
    chatWidget.style.cssText = `
      position: fixed;
      bottom: 80px;
      right: 20px;
      width: 320px;
      height: 400px;
      background: white;
      border-radius: 12px;
      box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
      display: none;
      flex-direction: column;
      z-index: 1002;
      font-family: 'Inter', sans-serif;
    `;
    
    document.body.appendChild(chatWidget);
    this.addChatStyles();
  }
  
  addChatStyles() {
    const style = document.createElement('style');
    style.textContent = `
      .chat-header {
        background: linear-gradient(135deg, #007bff, #0056b3);
        color: white;
        padding: 15px 20px;
        border-radius: 12px 12px 0 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
      }
      
      .chat-header h4 {
        margin: 0;
        font-size: 16px;
        font-weight: 600;
      }
      
      #closeChatBtn {
        background: none;
        border: none;
        color: white;
        font-size: 20px;
        cursor: pointer;
        padding: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      
      .chat-messages {
        flex: 1;
        padding: 15px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
      }
      
      .bot-message, .user-message {
        display: flex;
        flex-direction: column;
        max-width: 80%;
      }
      
      .bot-message {
        align-self: flex-start;
      }
      
      .user-message {
        align-self: flex-end;
      }
      
      .message-content {
        padding: 10px 14px;
        border-radius: 12px;
        font-size: 14px;
        line-height: 1.4;
      }
      
      .bot-message .message-content {
        background: #f1f3f4;
        color: #333;
      }
      
      .user-message .message-content {
        background: #007bff;
        color: white;
      }
      
      .message-time {
        font-size: 11px;
        color: #64748b;
        margin-top: 4px;
        padding: 0 4px;
      }
      
      .chat-input {
        padding: 15px;
        border-top: 1px solid #e2e8f0;
        display: flex;
        gap: 10px;
        align-items: center;
      }
      
      #chatInput {
        flex: 1;
        padding: 10px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 14px;
        outline: none;
      }
      
      #chatInput:focus {
        border-color: #007bff;
      }
      
      #sendBtn {
        width: 36px;
        height: 36px;
        background: #007bff;
        border: none;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        transition: background-color 0.3s ease;
      }
      
      #sendBtn:hover {
        background: #0056b3;
      }
      
      .typing-indicator {
        display: flex;
        align-items: center;
        gap: 4px;
        padding: 10px 14px;
        background: #f1f3f4;
        border-radius: 12px;
        max-width: 60px;
      }
      
      .typing-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #64748b;
        animation: typing 1.4s infinite ease-in-out;
      }
      
      .typing-dot:nth-child(1) { animation-delay: -0.32s; }
      .typing-dot:nth-child(2) { animation-delay: -0.16s; }
      
      @keyframes typing {
        0%, 80%, 100% {
          transform: scale(0.8);
          opacity: 0.5;
        }
        40% {
          transform: scale(1);
          opacity: 1;
        }
      }
    `;
    
    document.head.appendChild(style);
  }
  
  setupEventListeners() {
    const chatButton = document.getElementById('chatButton');
    const chatWidget = document.getElementById('chatWidget');
    const closeChatBtn = document.getElementById('closeChatBtn');
    const chatInput = document.getElementById('chatInput');
    const sendBtn = document.getElementById('sendBtn');
    
    chatButton.addEventListener('click', () => this.toggleChat());
    closeChatBtn.addEventListener('click', () => this.closeChat());
    sendBtn.addEventListener('click', () => this.sendMessage());
    chatInput.addEventListener('keypress', (e) => {
      if (e.key === 'Enter') {
        this.sendMessage();
      }
    });
    
    // Add hover effect to chat button
    chatButton.addEventListener('mouseenter', () => {
      chatButton.style.transform = 'scale(1.1)';
    });
    
    chatButton.addEventListener('mouseleave', () => {
      chatButton.style.transform = 'scale(1)';
    });
  }
  
  toggleChat() {
    const chatWidget = document.getElementById('chatWidget');
    this.isOpen = !this.isOpen;
    
    if (this.isOpen) {
      chatWidget.style.display = 'flex';
      setTimeout(() => {
        chatWidget.style.opacity = '1';
        chatWidget.style.transform = 'translateY(0)';
      }, 10);
    } else {
      this.closeChat();
    }
  }
  
  closeChat() {
    const chatWidget = document.getElementById('chatWidget');
    chatWidget.style.display = 'none';
    this.isOpen = false;
  }
  
  sendMessage() {
    const chatInput = document.getElementById('chatInput');
    const message = chatInput.value.trim();
    
    if (message) {
      this.addMessage(message, 'user');
      chatInput.value = '';
      
      // Simulate bot response
      setTimeout(() => {
        this.showTypingIndicator();
        setTimeout(() => {
          this.hideTypingIndicator();
          this.addBotResponse(message);
        }, 1500);
      }, 500);
    }
  }
  
  addMessage(message, sender) {
    const chatMessages = document.getElementById('chatMessages');
    const messageDiv = document.createElement('div');
    messageDiv.className = `${sender}-message`;
    
    messageDiv.innerHTML = `
      <div class="message-content">${message}</div>
      <div class="message-time">${this.getCurrentTime()}</div>
    `;
    
    chatMessages.appendChild(messageDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
    
    this.messages.push({ message, sender, time: this.getCurrentTime() });
  }
  
  addBotResponse(userMessage) {
    const responses = this.getBotResponse(userMessage);
    this.addMessage(responses, 'bot');
  }
  
  getBotResponse(userMessage) {
    const message = userMessage.toLowerCase();
    
    if (message.includes('hello') || message.includes('hi')) {
      return 'Hello! How can I assist you with your documents today?';
    }
    
    if (message.includes('help')) {
      return 'I can help you with document management, file uploads, approvals, and system navigation. What specific assistance do you need?';
    }
    
    if (message.includes('upload') || message.includes('file')) {
      return 'To upload files, click the "Upload Documents" button in the Recent Files section. You can drag and drop files or browse to select them.';
    }
    
    if (message.includes('approval') || message.includes('approve')) {
      return 'Document approvals can be managed in the dashboard. You can see pending approvals in the summary chart and take action on individual files in the Recent Files table.';
    }
    
    if (message.includes('search')) {
      return 'Use the search bar at the top to find documents quickly, or use the search function in the Recent Files section to filter specific files.';
    }
    
    if (message.includes('status')) {
      return 'Document status can be viewed in the Recent Files section. Files can have statuses like Approved, Pending, or Rejected.';
    }
    
    return 'Thank you for your message. Our support team will help you with document management, approvals, and any other questions you might have. Is there something specific I can help you with?';
  }
  
  showTypingIndicator() {
    const chatMessages = document.getElementById('chatMessages');
    const typingDiv = document.createElement('div');
    typingDiv.className = 'bot-message typing-message';
    typingDiv.innerHTML = `
      <div class="typing-indicator">
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
        <div class="typing-dot"></div>
      </div>
    `;
    
    chatMessages.appendChild(typingDiv);
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
  
  hideTypingIndicator() {
    const typingMessage = document.querySelector('.typing-message');
    if (typingMessage) {
      typingMessage.remove();
    }
  }
  
  getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }
}

