<?php
require_once("../config/config.php");
?>
<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérifie si la variable "gallery-name" existe dans la requête POST
    if (isset($_POST['galleryName']) && !empty($_POST['galleryName'])) {
        // Récupère et sécurise la valeur
        $galleryName = htmlspecialchars($_POST['galleryName']);
    } else {
        echo "Aucune galerie sélectionnée.";
    }
} else {
    echo "Accès non autorisé.";
}
$repgalleries = $repImg . 'galleries/' . $galleryName . '/original';
?>
<!DOCTYPE html>
<html>

<head>
    <link rel="stylesheet" href="css/admin.css">
    <title>Gestion des Images de galerie</title>
</head>

<body>
    <header>
        <h1>Gestion des Images de la galerie: <?= $galleryName ?></h1>
        <a href="index.php">Back to galleries</a>
    </header>
    <main>
        <section class="form-contener">
            <!-- Sélection des fichiers à uploader -->
            <input type="file" id="fileInput" multiple />
            <button onclick="uploadImages()">Upload</button>
            <!--Fin de Sélection des fichiers à uploader -->
        </section>
        <section class="form-contener">
            <!-- Rafraîchir les miniatures -->
            <button id="refreshThumbsBtn">Rafraîchir les miniatures</button>
            <!--Fin de Rafraîchir les miniatures -->
        </section>
        <!-- Nouvelle section : Mini-galerie -->
        <section>
            <h2>Images</h2>
            <div id="thumbnailsContainer" class="thumbnails-grid"></div>
        </section>

    </main>
    <script>
        function uploadImages() {
            const fileInput = document.getElementById('fileInput');
            // On récupère le nom de la galerie depuis la variable PHP de la page
            const galleryName = '<?= $galleryName ?>';

            if (fileInput.files.length === 0) {
                alert("Please select image files");
                return;
            }

            const formData = new FormData();
            const files = fileInput.files;

            for (let i = 0; i < files.length; i++) {
                formData.append('images[]', files[i]);
            }

            formData.append('action', 'upload');
            formData.append('galleryName', galleryName);

            fetch('api/galleries_api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Upload réussi !");
                        loadThumbnails();
                    } else {
                        // C'est ici que tu reçois "Paramètres manquants"
                        alert("Erreur serveur : " + data.error);
                    }
                })
                .catch(error => alert("Erreur : " + error));
        }
    </script>
    <script>
        document.getElementById('refreshThumbsBtn').addEventListener('click', () => {
            const galleryName = '<?= htmlspecialchars($galleryName, ENT_QUOTES) ?>';

            // On prépare les données pour l'API centralisée
            const formData = new FormData();
            formData.append('action', 'refresh'); // On définit une action claire
            formData.append('galleryName', galleryName);

            fetch('api/galleries_api.php', { // <-- Nouvelle destination
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error("Erreur réseau");
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        alert("Miniatures et index rafraîchis avec succès !");
                        loadThumbnails(); // On recharge l'affichage des images
                    } else {
                        alert("Erreur : " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Erreur détaillée:", error);
                    alert("Une erreur critique est survenue. Vérifiez la console.");
                });
        });
    </script>
    <script>
        const currentGalleryName = '<?= htmlspecialchars($galleryName, ENT_QUOTES) ?>';
        function loadThumbnails() {
            const params = new URLSearchParams();
            params.append('action', 'list');
            params.append('galleryName', currentGalleryName); // Assure-toi que cette variable est définie

            fetch('api/galleries_api.php', { // <-- On pointe vers l'API
                method: 'POST',
                body: params
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const thumbnailsContainer = document.getElementById('thumbnailsContainer');
                        thumbnailsContainer.innerHTML = ''; // Clear existing thumbnails

                        data.thumbnails.forEach(thumbnail => {
                            const thumbnailElement = document.createElement('div');
                            thumbnailElement.className = 'thumbnail-item';

                            thumbnailElement.innerHTML = `
                        <img src="${thumbnail.url}" alt="${thumbnail.name}" />
                        <button onclick="deleteImage('${thumbnail.name}')">Suppr</button>
                        <button onclick="renameImage('${thumbnail.name}')">Renommer</button>
                    `;

                            thumbnailsContainer.appendChild(thumbnailElement);
                        });
                    } else {
                        alert("Erreur : " + data.error);
                    }
                }).catch(err => console.error("Erreur de chargement:", err));
        }
        function refreshGalleryIndex() {
            const formData = new FormData();
            formData.append('action', 'list'); // Ou une action dédiée si tu veux juste forcer l'index
            formData.append('galleryName', currentGalleryName);

            fetch('api/galleries_api.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        console.log("Index et miniatures synchronisés.");
                        // Update ton UI ici
                    }
                });
        }

        // Supprimer une image
        function deleteImage(filename) {
            if (!confirm('Supprimer cette image ?')) return;

            const formData = new FormData();
            formData.append('action', 'delete'); // On dit à l'API ce qu'on veut faire
            formData.append('galleryName', currentGalleryName);
            formData.append('filename', filename);

            // CHANGE ICI : On pointe vers l'API centrale
            fetch('api/galleries_api.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    if (!response.ok) throw new Error("Fichier API introuvable (404)");
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        loadThumbnails(); // On rafraîchit l'affichage
                    } else {
                        alert("Erreur : " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Erreur:", error);
                    alert("Impossible de supprimer l'image.");
                });
        }

        // RENOMMER UNE IMAGE (DANS UNE GALERIE)
        function renameImage(oldName) {
            const newName = prompt("Nouveau nom (sans extension) :", oldName.split('.')[0]);
            if (!newName) return;

            const formData = new FormData();
            formData.append('action', 'renameImage'); // À ajouter dans l'API si tu veux renommer les fichiers
            formData.append('galleryName', '<?= $galleryName ?>');
            formData.append('oldName', oldName);
            formData.append('newName', newName);

            fetch('api/galleries_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => data.success ? loadThumbnails() : alert(data.error));
        }
        // SUPPRIMER LA GALERIE ENTIÈRE
        function deleteThisGallery() {
            if (!confirm("Attention : cela supprimera tous les fichiers. Continuer ?")) return;

            const formData = new FormData();
            formData.append('action', 'deleteGallery');
            formData.append('galleryName', '<?= $galleryName ?>');

            fetch('api/galleries_api.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => data.success ? window.location.href = 'index.php' : alert(data.error));
        }

        // Charger les miniatures au démarrage
        document.addEventListener('DOMContentLoaded', loadThumbnails);
    </script>
</body>

</html>