<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sandbox LLM</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js"></script>
    <script>hljs.highlightAll();</script>
    <script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
    <script>marked.setOptions({ breaks: true });</script>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
<div class="app">
    <!-- Sidebar Debug -->
    <div class="sidebar" id="sidebar">
        <div class="debug-content">
            <a href="/clear?all" style="float:right;color:#CCC;margin:0 20px 0 0;text-decoration:none;">Clear All</a>
            <?php dump($_SESSION); ?>
        </div>
    </div>
    <!-- Main -->
    <div class="container">
        <form id="chatForm" style="height:100%;display:flex;flex-direction:column;">

            <!-- Header -->
            <div class="header">
                <div class="title">
                    <div class="toggle-btn" onclick="toggleSidebar()">
                        <svg viewBox="0 0 24 24" fill="none">
                            <rect x="3" y="4" width="18" height="16" rx="4" stroke="currentColor" stroke-width="1"/>
                            <line x1="9" y1="4" x2="9" y2="20" stroke="currentColor" stroke-width="1"/>
                        </svg>
                    </div>
                    Sandbox LLM <span class="tagline">made with hope ✦</span>
                </div>

                <div class="controls">
                    <select name="model" id="model" onchange="location.href='/?model='+this.value">
                        <?php foreach ($validModels as $m): ?>
                            <option value="<?= $m ?>" <?= $m === $model ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Chat Area -->
            <div class="chat-area" id="chatArea">

                <?php if (!empty($data['chatHistory'])): ?>
                    <?php foreach ($data['chatHistory'] as $msg): ?>
                        <?php include __DIR__ . '/partials/bubble.php'; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty" id="emptyState">
                        <div class="icon">🤖</div>
                        <h2>Halo!</h2>
                        <p>Ketik prompt untuk memulai percakapan.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Input -->
             <div class="intent">
                <label><input type="radio" name="intent" value="KNOWLEDGE" checked />Knowledge</label>
                <label><input type="radio" name="intent" value="SQL" />Text to SQL</label>
                <label><input type="radio" name="intent" value="RAG" />RAG</label>
                <label><input type="radio" name="intent" value="SEARCH" />Search</label>
            </div>
            <div class="input-area">
                <textarea name="prompt" id="prompt" class="prompt"></textarea>
                <button type="button" onclick="sendMessage()">SEND</button>
            </div>

        </form>
    </div>
</div>

<script>
document.getElementById('chatForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const chatArea = document.getElementById('chatArea');
    const inputField = this.querySelector('textarea, input'); // Sesuaikan selector
    const thinking = document.createElement('span');
    const typing = document.createElement('span');
    // UI Setup

    const streamBox = document.createElement('div');
    thinking.className = 'thinking';
    typing.className = 'typing';
    streamBox.className = 'bubble ai';
    streamBox.textContent = ''; // Mulai kosong
    chatArea.appendChild(streamBox);
    chatArea.append(thinking);
    // Auto scroll helper
    const scrollToBottom = () => chatArea.scrollTop = chatArea.scrollHeight;
    scrollToBottom();

    // Disable input sementara
    if(inputField) inputField.disabled = true;

    try {
        const res = await fetch('/request', {
            method: 'POST',
            body: formData
        });

        if (!res.ok) throw new Error('Server error');

        const reader = res.body.getReader();
        const decoder = new TextDecoder("utf-8");
        let done = false;        
        let accumulatedText = ""; 

        chatArea.querySelector('.thinking')?.remove();
        chatArea.append(typing);

        while (!done) {
            const { value, done: readerDone } = await reader.read();
            done = readerDone;
            
            if (value) {
                const chunk = decoder.decode(value, { stream: true });
                accumulatedText += chunk;
                
                // Update UI: Tambahkan chunk baru ke innerHTML
                // Gunakan textContent + replace newline untuk keamanan XSS sederhana
                // Atau innerHTML jika Anda percaya sumbernya 100% aman (Lokal Ollama)
                //streamBox.innerHTML = accumulatedText.replace(/\n/g, '<br>');
                if (window.marked) {
                    streamBox.innerHTML = marked.parse(accumulatedText);
                }
                else {
                    streamBox.innerHTML = accumulatedText;
                }


                if (window.hljs) {
                    const codes = streamBox.querySelectorAll('pre code');
                    
                    codes.forEach(el => {
                        if (!el.dataset.highlighted) {
                            hljs.highlightElement(el);
                        }
                    });
                }
                
                scrollToBottom();
            }
        }

    } catch (err) {
        streamBox.innerHTML += `<br><span class="error">[Connection Lost: ${err.message}]</span>`;
    } finally {
        if(inputField) {
            inputField.disabled = false;
            inputField.focus();
        }

        chatArea.querySelector('.typing')?.remove();
        //hljs.highlightAll();
    }
});

// Helper sederhana untuk mengganti newline dengan <br> jika perlu
function formatText(text) {
    return text.replace(/\n/g, '<br>');
}


function sendMessage() {
    const input = document.getElementById('prompt');
    if (!input.value.trim()) return;

    const chatArea = document.getElementById('chatArea');
    const empty    = document.getElementById('emptyState');
    if (empty) empty.remove();

    const bubble = document.createElement('div');
    bubble.className = 'bubble user';
    bubble.innerText = input.value.trim();
    chatArea.appendChild(bubble);
    chatArea.scrollTop = chatArea.scrollHeight;

    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
    input.value = '';
}


function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
    document.querySelector('.app').classList.toggle('sidebar-open');
}

const textarea = document.getElementById('prompt');

textarea.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
        e.preventDefault();
        sendMessage();
        this.style.height = 'auto'; // reset setelah kirim
    }
});

// render markdown dari history saat page load
window.addEventListener('load', function () {
    document.querySelectorAll('.md-content').forEach(el => {
        if (window.marked) el.innerHTML = marked.parse(el.textContent);
    });
    
    if (window.hljs) document.querySelectorAll('pre code').forEach(el => hljs.highlightElement(el));

    const chatArea = document.getElementById('chatArea');
    chatArea.scrollTop = chatArea.scrollHeight;
});
</script>

</body>
</html>
