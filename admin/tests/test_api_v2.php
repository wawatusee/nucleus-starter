<?php
/**
 * Test des endpoints API v2
 */

session_start();
$_SESSION['user'] = 'testeur';

require_once __DIR__ . '/config_admin.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test API v2</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a2e; color: #eee; }
        .test { background: #16213e; padding: 15px; margin: 10px 0; border-radius: 8px; }
        .success { border-left: 4px solid #4ade80; }
        .error { border-left: 4px solid #f87171; }
        pre { background: #0f0f23; padding: 10px; overflow-x: auto; }
        button { background: #3b82f6; color: white; border: none; padding: 10px 20px; cursor: pointer; margin: 5px; border-radius: 4px; }
        button:hover { background: #2563eb; }
        #results { margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🧪 Test API v2 - Nucleus</h1>
    
    <div>
        <button onclick="testList()">📋 Lister les articles</button>
        <button onclick="testGet()">📄 Charger un article</button>
        <button onclick="testSaveValid()">✅ Sauvegarder (valide)</button>
        <button onclick="testSaveInvalid()">❌ Sauvegarder (invalide)</button>
        <button onclick="testDelete()">🗑️ Supprimer</button>
    </div>

    <div id="results"></div>

    <script>
        const resultsDiv = document.getElementById('results');

        function showResult(title, data, isSuccess) {
            const div = document.createElement('div');
            div.className = 'test ' + (isSuccess ? 'success' : 'error');
            div.innerHTML = `<strong>${title}</strong><pre>${JSON.stringify(data, null, 2)}</pre>`;
            resultsDiv.prepend(div);
        }

        async function testList() {
            const res = await fetch('api/v2/list_articles.php?meta=1');
            const data = await res.json();
            showResult('Liste des articles', data, Array.isArray(data));
        }

        async function testGet() {
            const res = await fetch('api/v2/get_article.php?file=test-component-model.json');
            const data = await res.json();
            showResult('Chargement article', data, data.meta !== undefined);
        }

        async function testSaveValid() {
            const article = {
                type: 'article',
                meta: {
                    id: 'test-api-v2',
                    created: '2025-01-15',
                    status: 'draft'
                },
                content: [
                    {
                        type: 'title',
                        level: 2,
                        data: { fr: 'Test API v2', en: 'API v2 Test' }
                    },
                    {
                        type: 'text',
                        data: { fr: 'Créé via API v2', en: 'Created via API v2' }
                    }
                ]
            };

            const res = await fetch('api/v2/save_article.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(article)
            });
            const data = await res.json();
            showResult('Sauvegarde valide', data, data.success === true);
        }

        async function testSaveInvalid() {
            const article = {
                type: 'article',
                meta: { id: 'test-invalide' },
                content: [
                    {
                        type: 'link',
                        data: { fr: 'Cliquer' }
                        // URL manquante, EN manquant
                    }
                ]
            };

            const res = await fetch('api/v2/save_article.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(article)
            });
            const data = await res.json();
            showResult('Sauvegarde invalide (attendu: erreurs)', data, data.success === false);
        }

        async function testDelete() {
            if (!confirm('Supprimer test-api-v2.json ?')) return;
            
            const res = await fetch('api/v2/delete_article.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ filename: 'test-api-v2.json' })
            });
            const data = await res.json();
            showResult('Suppression', data, data.success === true);
        }
    </script>
</body>
</html>