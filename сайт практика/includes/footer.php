</div> <!-- /container -->

<!-- Виджет чата поддержки -->
<div class="support-chat-widget position-fixed bottom-0 end-0 m-4" style="z-index: 9999;">
    <button class="btn btn-primary rounded-circle shadow-lg p-3 d-flex align-items-center justify-content-center position-relative" id="openChat" style="width: 60px; height: 60px;">
        <i class="bi bi-chat-dots-fill fs-3"></i>
        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="chatBadge">
            !
        </span>
    </button>
    <div class="card border-0 shadow-lg rounded-4 overflow-hidden d-none" id="chatWindow" style="width: 350px; position: absolute; bottom: 80px; right: 0;">
        <div class="card-header bg-primary text-dark py-3 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Чат поддержки ЭЛЕКТРО МИР</h6>
            <button type="button" class="btn-close" id="closeChat"></button>
        </div>
        <div class="card-body p-0" style="height: 400px; display: flex; flex-direction: column;">
            <div class="chat-messages p-3 flex-grow-1 overflow-auto" id="chatMessages" style="background: #f8f9fa;">
                <div class="message system mb-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm small text-muted">
                        Здравствуйте! 👋 Мы готовы помочь вам с выбором товара. Напишите ваш вопрос ниже.
                    </div>
                </div>
            </div>
            <div class="chat-input p-3 border-top bg-white">
                <div class="input-group">
                    <input type="text" class="form-control border-0 bg-light rounded-pill px-3" id="chatInput" placeholder="Введите сообщение...">
                    <button class="btn btn-primary rounded-circle ms-2 p-2 d-flex align-items-center justify-content-center" id="sendMessage" style="width: 40px; height: 40px;">
                        <i class="bi bi-send-fill fs-6"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const openChat = document.getElementById('openChat');
    const closeChat = document.getElementById('closeChat');
    const chatWindow = document.getElementById('chatWindow');
    const chatInput = document.getElementById('chatInput');
    const sendMessage = document.getElementById('sendMessage');
    const chatMessages = document.getElementById('chatMessages');

    function addMessage(text, isUser = false) {
        if (!text) return;
        const msgDiv = document.createElement('div');
        msgDiv.className = `message ${isUser ? 'user text-end' : 'system'} mb-3`;
        msgDiv.innerHTML = `
            <div class="${isUser ? 'bg-primary text-white' : 'bg-white text-dark'} p-3 rounded-4 shadow-sm small d-inline-block" style="max-width: 80%; border: 1px solid #eee;">
                ${text}
            </div>
        `;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function loadMessages() {
        const isWindowOpen = !chatWindow.classList.contains('d-none');
        const url = isWindowOpen ? 'support_api.php?mark_read=1' : 'support_api.php';

        fetch(url)
            .then(response => response.json())
            .then(data => {
                const messages = data.messages || [];
                const unread = data.unread || 0;

                // Показываем/скрываем уведомление
                const chatBadge = document.getElementById('chatBadge');
                if (unread > 0 && !isWindowOpen) {
                    chatBadge.classList.remove('d-none');
                    chatBadge.innerText = unread;
                } else {
                    chatBadge.classList.add('d-none');
                }

                if (isWindowOpen) {
                    const welcomeMsg = `
                        <div class="message system mb-3">
                            <div class="bg-white text-dark p-3 rounded-4 shadow-sm small" style="border: 1px solid #eee;">
                                Здравствуйте! 👋 Мы готовы помочь вам с выбором товара. Напишите ваш вопрос ниже.
                            </div>
                        </div>
                    `;
                    
                    // Обновляем только если количество сообщений изменилось или окно только что открылось
                    const currentMsgCount = chatMessages.querySelectorAll('.message').length;
                    // +1 для приветственного сообщения
                    if (messages.length + 1 !== currentMsgCount) {
                        chatMessages.innerHTML = welcomeMsg;
                        messages.forEach(msg => {
                            if (msg.message) addMessage(msg.message, true);
                            if (msg.reply) addMessage(msg.reply, false);
                        });
                    }
                }
            });
    }

    // Проверка новых сообщений каждые 10 секунд
    setInterval(loadMessages, 10000);

    // Загрузка сообщений при открытии
    openChat.addEventListener('click', () => {
        chatWindow.classList.toggle('d-none');
        loadMessages();
    });

    closeChat.addEventListener('click', () => {
        chatWindow.classList.add('d-none');
    });

    function handleSend() {
        const text = chatInput.value.trim();
        if (text) {
            const formData = new FormData();
            formData.append('message', text);

            fetch('support_api.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    addMessage(text, true);
                    chatInput.value = '';
                    setTimeout(() => {
                        addMessage('Ваше обращение получено! Администратор ответит вам в ближайшее время.');
                    }, 1000);
                } else {
                    addMessage('Ошибка сервера: ' + (data.error || 'неизвестно'));
                    console.error('Chat error:', data);
                }
            })
            .catch(error => {
                addMessage('Ошибка сети: ' + error.message);
                console.error('Fetch error:', error);
            });
        }
    }

    sendMessage.addEventListener('click', handleSend);
    chatInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') handleSend(); });
});
</script>

<footer class="bg-white py-5 mt-5 shadow-sm">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h5 class="fw-bold text-primary mb-3">ELECTROSTORE</h5>
                <p class="text-muted">Ваш надежный магазин электроники. Мы предлагаем только лучшие товары с гарантией качества.</p>
            </div>
            <div class="col-md-2 mb-4">
                <h6 class="fw-bold mb-3">Навигация</h6>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-decoration-none text-muted">Каталог</a></li>
                    <li><a href="cart.php" class="text-decoration-none text-muted">Корзина</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6 class="fw-bold mb-3">Помощь</h6>
                <ul class="list-unstyled">
                    <li><a href="#" class="text-decoration-none text-muted">Доставка и оплата</a></li>
                    <li><a href="#" class="text-decoration-none text-muted">Контакты</a></li>
                </ul>
            </div>
            <div class="col-md-3 mb-4">
                <h6 class="fw-bold mb-3">Контакты</h6>
                <p class="text-muted small mb-1"><i class="bi bi-geo-alt me-2"></i>г. Москва, ул. Примерная, 123</p>
                <p class="text-muted small mb-1"><i class="bi bi-telephone me-2"></i>+7 (999) 000-00-00</p>
                <p class="text-muted small"><i class="bi bi-envelope me-2"></i>info@electrostore.ru</p>
            </div>
        </div>
        <hr class="my-4">
        <div class="text-center text-muted small">
            &copy; <?= date('Y') ?> ElectroStore. Все права защищены.
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
