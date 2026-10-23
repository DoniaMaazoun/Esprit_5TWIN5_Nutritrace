# Configuration NutriAI avec Groq API

## Vue d'ensemble

NutriAI utilise l'API Groq pour générer des réponses conversationnelles intelligentes. Groq est un fournisseur d'IA compatible avec l'API OpenAI, offrant des modèles performants et gratuits pour le développement.

## Configuration requise

### 1. Obtenir une clé API Groq

1. Créez un compte sur [console.groq.com](https://console.groq.com)
2. Connectez-vous et accédez à la section "API Keys"
3. Créez une nouvelle clé API
4. Copiez la clé (elle ne sera affichée qu'une seule fois)

### 2. Configurer l'environnement

Ajoutez la clé API à votre fichier `.env`:

```env
AI_API_KEY=gsk_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
AI_BASE_URL=https://api.groq.com/openai/v1
AI_MODEL=llama-3.3-70b-versatile
```

**Variables:**
- `AI_API_KEY`: Votre clé API Groq (obligatoire)
- `AI_BASE_URL`: URL de l'API Groq (par défaut: `https://api.groq.com/openai/v1`)
- `AI_MODEL`: Modèle à utiliser (par défaut: `llama-3.3-70b-versatile`)

### 3. Modèles disponibles

Groq propose plusieurs modèles Llama 3:

- `llama-3.1-70b-versatile` - Modèle polyvalent (recommandé)
- `llama-3.1-8b-instant` - Modèle rapide pour réponses courtes
- `llama-3.3-70b-versatile` - **OBSOLÈTE** (arrêté le 16 août 2026)
- `mixtral-8x7b-32768` - Modèle Mistral
- `gemma-7b-it` - Modèle Google Gemma

Pour NutriAI, `llama-3.1-70b-versatile` est recommandé pour sa meilleure compréhension du français et de l'arabe.

## Fonctionnement

### Architecture RAG (Retrieval-Augmented Generation)

NutriAI utilise une architecture RAG légère:

1. **Analyse de la question:** Le service détecte l'intention (recherche produit, traçabilité, question libre)
2. **Récupération des données:** Les données pertinentes sont récupérées depuis MySQL via Eloquent
3. **Construction du prompt:** Les données sont injectées dans le prompt avec des instructions strictes
4. **Appel API:** Le prompt est envoyé à Groq pour générer une réponse naturelle
5. **Retour:** La réponse + les sources (IDs des modèles utilisés) sont retournées

### Sources de données

NutriAI utilise exclusivement les données de la base `farm_to_fork`:

- **Produits:** `distributor_stocks` → `production_lots` → `recipes`
- **Traçabilité:** `harvests` → `farms`
- **Catégories:** `categories`
- **Points de vente:** `points_of_sale`
- **Distributeurs:** `users` (role: pro, pro_type: distributor)

Aucune donnée n'est inventée. Si une information n'est pas dans la base, NutriAI le signale explicitement.

## Sécurité

- La clé API n'est jamais exposée dans le frontend
- Les données privées (mots de passe, tokens) ne sont jamais transmises à l'API
- Les requêtes sont limitées (rate limiting: 10/minute)
- L'historique de conversation est limité aux 10 derniers messages
- Seuls les consommateurs authentifiés peuvent accéder à NutriAI

## Limites

- **Taille des questions:** 2-500 caractères
- **Taille de l'historique:** 10 derniers messages
- **Rate limiting:** 10 requêtes/minute par utilisateur
- **Timeout API:** 25 secondes

## Dépannage

### Erreur: "NutriAI est temporairement indisponible. La clé API n'est pas configurée."

**Solution:** Ajoutez `AI_API_KEY` dans votre fichier `.env` et redémarrez le serveur.

### Erreur: "NutriAI est temporairement indisponible. L'appel API a échoué."

**Causes possibles:**
- Clé API invalide ou expirée
- Problème de réseau
- API Groq en maintenance
- Quota dépassé

**Solution:** Vérifiez votre clé API et votre connexion internet.

### Tests automatisés

Les tests utilisent des mocks pour simuler les réponses API. Ils ne nécessitent pas de clé API réelle:

```bash
php artisan test --filter NutriAIServiceTest
```

Pour tester avec une vraie clé API, configurez `.env` et utilisez l'interface web.

## Coût

Groq offre un niveau gratuit généreux pour le développement. Consultez [pricing.groq.com](https://pricing.groq.com) pour les détails.

## Alternatives

Si vous préférez utiliser un autre fournisseur compatible OpenAI:

1. Changez `AI_BASE_URL` pour l'URL du fournisseur
2. Changez `AI_MODEL` pour le modèle correspondant
3. Utilisez votre clé API du fournisseur

Exemples:
- OpenAI: `https://api.openai.com/v1` avec modèle `gpt-4`
- Mistral: `https://api.mistral.ai/v1` avec modèle `mistral-large`
