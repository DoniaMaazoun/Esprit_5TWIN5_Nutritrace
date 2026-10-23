@extends('frontend.layouts.app')

@section('title', 'NutriAI - Assistant Conversationnel')

@section('extra-css')
<style>
    .chat-container {
        height: calc(100vh - 200px);
        min-height: 500px;
    }
    .chat-messages {
        height: calc(100% - 80px);
        overflow-y: auto;
        background: #f8f9fa;
        border-radius: 0 0 0.5rem 0.5rem;
    }
    .message-user {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 1rem 1rem 0 1rem;
        max-width: 70%;
        margin-left: auto;
    }
    .message-ai {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 1rem 1rem 1rem 0;
        max-width: 70%;
        margin-right: auto;
    }
    .source-badge {
        font-size: 0.75rem;
        background: #e7f3ff;
        color: #0066cc;
        padding: 0.25rem 0.5rem;
        border-radius: 0.25rem;
        margin-top: 0.5rem;
    }
    .missing-info {
        font-size: 0.8rem;
        color: #dc3545;
        margin-top: 0.5rem;
    }
    .local-indicator {
        font-size: 0.75rem;
        color: #6c757d;
        font-style: italic;
    }
    .typing-indicator {
        display: none;
        padding: 0.5rem 1rem;
        background: white;
        border-radius: 1rem;
        margin-right: auto;
        width: fit-content;
    }
    .typing-indicator span {
        display: inline-block;
        width: 8px;
        height: 8px;
        background: #6c757d;
        border-radius: 50%;
        margin: 0 2px;
        animation: typing 1.4s infinite;
    }
    .typing-indicator span:nth-child(2) { animation-delay: 0.2s; }
    .typing-indicator span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing {
        0%, 60%, 100% { transform: translateY(0); }
        30% { transform: translateY(-5px); }
    }
    .chat-input-area {
        padding: 1rem;
        background: white;
        border-top: 1px solid #dee2e6;
        border-radius: 0 0 0.5rem 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-robot me-2"></i>
                    <h5 class="mb-0">NutriAI - Votre Assistant Nutrition</h5>
                    <span class="badge bg-light text-primary ms-auto">Beta</span>
                </div>
                <div class="card-body p-0">
                    <div class="chat-container">
                        <div class="chat-messages p-3" id="chatMessages">
                            <!-- Message de bienvenue -->
                            <div class="d-flex mb-3">
                                <div class="message-ai p-3 shadow-sm">
                                    <p class="mb-0">Bonjour ! Je suis NutriAI, votre assistant pour Nutritrace. Je peux vous aider à :</p>
                                    <ul class="mb-0 mt-2">
                                        <li>Rechercher des produits dans le catalogue</li>
                                        <li>Comparer les prix et disponibilités</li>
                                        <li>Expliquer la traçabilité des lots</li>
                                        <li>Trouver des informations sur les ingrédients</li>
                                    </ul>
                                    <p class="mb-0 mt-2">Que souhaitez-vous savoir ?</p>
                                    <div class="local-indicator">
                                        <i class="fas fa-info-circle"></i> Mode hors-ligne (réponses locales)
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="typing-indicator ms-3 mb-3" id="typingIndicator">
                            <span></span><span></span><span></span>
                        </div>
                        <div class="chat-input-area">
                            <form id="chatForm" class="d-flex gap-2">
                                <input 
                                    type="text" 
                                    class="form-control" 
                                    id="messageInput" 
                                    placeholder="Posez votre question ici..." 
                                    maxlength="500"
                                    required
                                >
                                <button type="submit" class="btn btn-primary" id="sendButton">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-shield-alt"></i> Vos données ne sont partagées avec aucun tiers.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="alert alert-info mt-3">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Conseils :</strong>
                <ul class="mb-0 mt-2">
                    <li>Utilisez des questions précises pour de meilleurs résultats</li>
                    <li>Scannez le QR code d'un produit pour connaître sa traçabilité</li>
                    <li>Les informations proviennent directement de la base de données Nutritrace</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

@section('extra-js')
<script>
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const chatMessages = document.getElementById('chatMessages');
    const typingIndicator = document.getElementById('typingIndicator');
    const sendButton = document.getElementById('sendButton');
    
    let conversationHistory = [];
    
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const message = messageInput.value.trim();
        if (!message) return;
        
        // Désactiver le formulaire
        messageInput.disabled = true;
        sendButton.disabled = true;
        
        // Ajouter le message utilisateur
        addMessage(message, 'user');
        conversationHistory.push({ role: 'user', content: message });
        
        // Afficher l'indicateur de frappe
        typingIndicator.style.display = 'block';
        scrollToBottom();
        
        try {
            const response = await fetch('/consumer/nutri-ai/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    message: message,
                    history: conversationHistory
                })
            });
            
            const data = await response.json();
            
            // Masquer l'indicateur de frappe
            typingIndicator.style.display = 'none';
            
            if (response.ok) {
                // Ajouter la réponse AI
                addMessage(data.answer, 'ai', data.sources, data.missing, data.source);
                conversationHistory.push({ role: 'assistant', content: data.answer });
            } else {
                addMessage('Erreur : ' + (data.error || 'Une erreur est survenue'), 'ai');
            }
        } catch (error) {
            typingIndicator.style.display = 'none';
            addMessage('Erreur de connexion. Veuillez réessayer.', 'ai');
        }
        
        // Réactiver le formulaire
        messageInput.disabled = false;
        sendButton.disabled = false;
        messageInput.value = '';
        messageInput.focus();
    });
    
    function addMessage(content, type, sources = [], missing = [], source = 'local') {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'd-flex mb-3';
        
        const bubble = document.createElement('div');
        bubble.className = type === 'user' ? 'message-user p-3 shadow-sm' : 'message-ai p-3 shadow-sm';
        
        const text = document.createElement('p');
        text.className = 'mb-0';
        text.textContent = content;
        bubble.appendChild(text);
        
        // Afficher les sources si disponibles
        if (sources.length > 0) {
            const sourcesDiv = document.createElement('div');
            sourcesDiv.className = 'source-badge';
            sourcesDiv.innerHTML = '<i class="fas fa-database"></i> Source : ' + sources.length + ' donnée(s) vérifiée(s)';
            bubble.appendChild(sourcesDiv);
        }
        
        // Afficher les informations manquantes
        if (missing.length > 0) {
            const missingDiv = document.createElement('div');
            missingDiv.className = 'missing-info';
            missingDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + missing.join(', ');
            bubble.appendChild(missingDiv);
        }
        
        // Indicateur de mode local
        if (source === 'local') {
            const localDiv = document.createElement('div');
            localDiv.className = 'local-indicator mt-2';
            localDiv.innerHTML = '<i class="fas fa-info-circle"></i> Réponse locale (mode hors-ligne)';
            bubble.appendChild(localDiv);
        }
        
        messageDiv.appendChild(bubble);
        chatMessages.appendChild(messageDiv);
        scrollToBottom();
    }
    
    function scrollToBottom() {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
</script>
@endsection
