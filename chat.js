function startChat(carId, receiverId, userId) {
    const chatBox = document.getElementById('chat-box');
    let lastMessageId = 0;

    function fetchMessages() {
        fetch(`messages.php?car_id=${carId}&receiver_id=${receiverId}&last_id=${lastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(msg => {
                        const messageDiv = document.createElement('div');
                        messageDiv.className = `chat-message ${msg.sender_id == userId ? 'sent' : 'received'}`;
                        messageDiv.innerHTML = `
                            <p><strong>${msg.sender_name}:</strong> ${msg.message}</p>
                            <small>${msg.created_at}</small>
                        `;
                        chatBox.appendChild(messageDiv);
                        lastMessageId = Math.max(lastMessageId, msg.id);
                    });
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    setInterval(fetchMessages, 2000);
    fetchMessages();

    const chatForm = document.getElementById('chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const message = messageInput.value.trim();

            if (message) {
                const formData = new FormData();
                formData.append('send_message', true);
                formData.append('message', message);
                formData.append('car_id', carId);
                formData.append('receiver_id', receiverId);

                fetch('messages.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    messageInput.value = '';
                    fetchMessages();
                })
                .catch(error => console.error('Error sending message:', error));
            }
        });
    }
}
