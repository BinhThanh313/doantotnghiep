<div id="chatbot-widget">
    <button id="chatbot-toggle" title="Trợ lý mua sắm">
        <i class="fa fa-comment-dots"></i>
    </button>

    <div id="chatbot-box" class="d-none">
        <div id="chatbot-header">
            <span>Trợ lý mua sắm Electro</span>
            <div>
                <button id="chatbot-reset" title="Bắt đầu hội thoại mới">↺</button>
                <button id="chatbot-close" aria-label="Đóng">&times;</button>
            </div>
        </div>
        <div id="chatbot-messages"></div>
        <form id="chatbot-form">
            <input type="text" id="chatbot-input" placeholder="Nhập câu hỏi..." autocomplete="off" required>
            <button type="submit"><i class="fa fa-paper-plane"></i></button>
        </form>
    </div>
</div>

<style>
    #chatbot-widget { position: fixed; bottom: 100px; right: 30px; z-index: 1050; font-family: inherit; }
    #chatbot-toggle {
        width: 56px; height: 56px; border-radius: 50%; border: none;
        background: var(--bs-primary, #F28B00); color: #fff; font-size: 22px;
        box-shadow: 0 4px 12px rgba(0,0,0,.2); cursor: pointer;
    }
    #chatbot-box {
        position: absolute; bottom: 70px; right: 0; width: 340px; max-width: 90vw;
        height: 460px; background: #fff; border-radius: 12px; box-shadow: 0 8px 24px rgba(0,0,0,.25);
        display: flex; flex-direction: column; overflow: hidden;
    }
    #chatbot-header {
        background: var(--bs-primary, #F28B00); color: #fff; padding: 12px 16px;
        display: flex; align-items: center; justify-content: space-between; font-weight: 600;
    }
    #chatbot-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; }
    #chatbot-reset { background: none; border: none; color: #fff; font-size: 16px; cursor: pointer; margin-right: 8px; opacity: .85; }
    #chatbot-reset:hover { opacity: 1; }
    #chatbot-messages { flex: 1; overflow-y: auto; padding: 12px; background: #f8f9fa; }
    .chatbot-msg { margin-bottom: 10px; max-width: 85%; padding: 8px 12px; border-radius: 10px; font-size: 14px; white-space: pre-line; }
    .chatbot-msg.user { margin-left: auto; background: var(--bs-primary, #F28B00); color: #fff; }
    .chatbot-msg.bot { margin-right: auto; background: #e9ecef; color: #212529; }
    #chatbot-form { display: flex; border-top: 1px solid #eee; }
    #chatbot-input { flex: 1; border: none; padding: 10px 12px; outline: none; }
    #chatbot-form button { border: none; background: #fff; color: var(--bs-primary, #F28B00); padding: 0 14px; cursor: pointer; }
</style>

<script>
(function () {
    const toggleBtn = document.getElementById('chatbot-toggle');
    const closeBtn  = document.getElementById('chatbot-close');
    const resetBtn  = document.getElementById('chatbot-reset');
    const box       = document.getElementById('chatbot-box');
    const form      = document.getElementById('chatbot-form');
    const input     = document.getElementById('chatbot-input');
    const messages  = document.getElementById('chatbot-messages');

    const STORAGE_KEY = 'chatbot_session_token';
    const MESSAGES_KEY = 'chatbot_messages';
    
    // Dùng url() của Laravel thay vì gõ chết "/doantotnghiep/public/..." —
    // để widget vẫn chạy đúng nếu deploy ở domain/subfolder khác APP_URL.
    const CHATBOT_ENDPOINT = @json(url('/api/chatbot/message'));
    let sessionToken = localStorage.getItem(STORAGE_KEY) || null;
    let chatHistory = JSON.parse(localStorage.getItem(MESSAGES_KEY) || '[]');

    function appendMessage(sender, text, save = true) {
        const el = document.createElement('div');
        el.className = 'chatbot-msg ' + sender;
        el.textContent = text;
        messages.appendChild(el);
        messages.scrollTop = messages.scrollHeight;
        
        if (save) {
            chatHistory.push({ sender, text });
            localStorage.setItem(MESSAGES_KEY, JSON.stringify(chatHistory));
        }
    }

    // Phục hồi lịch sử chat
    if (chatHistory.length > 0) {
        chatHistory.forEach(msg => appendMessage(msg.sender, msg.text, false));
    }

    toggleBtn.addEventListener('click', () => {
        box.classList.toggle('d-none');
        if (!box.classList.contains('d-none') && !messages.hasChildNodes()) {
            appendMessage('bot', 'Xin chào! Mình là trợ lý mua sắm của Electro Shop. Bạn cần tìm sản phẩm gì hôm nay?');
        }
    });
    closeBtn.addEventListener('click', () => box.classList.add('d-none'));

    resetBtn.addEventListener('click', () => {
        localStorage.removeItem(STORAGE_KEY);
        localStorage.removeItem(MESSAGES_KEY);
        sessionToken = null;
        chatHistory = [];
        messages.innerHTML = '';
        appendMessage('bot', 'Đã bắt đầu hội thoại mới. Bạn cần tìm sản phẩm gì?');
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const text = input.value.trim();
        if (!text) return;

        appendMessage('user', text);
        input.value = '';

        try {
            const res = await fetch(CHATBOT_ENDPOINT, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ message: text, session_token: sessionToken }),
            });
            const data = await res.json();

            if (data.session_token) {
                sessionToken = data.session_token;
                localStorage.setItem(STORAGE_KEY, sessionToken);
            }

            appendMessage('bot', data.reply || 'Xin lỗi, mình chưa xử lý được câu hỏi này.');
        } catch (err) {
            appendMessage('bot', 'Có lỗi kết nối, bạn vui lòng thử lại sau.');
        }
    });
})();
</script>