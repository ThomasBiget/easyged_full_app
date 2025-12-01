<?php

namespace App\Services;

use Exception;

class DocumentService
{
    private string $uploadDir;
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
    private int $maxFileSize = 10 * 1024 * 1024; // 10 MB

    public function __construct(?string $uploadDir = null)
    {
        $this->uploadDir = $uploadDir ?? __DIR__ . '/../../uploads';
        
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Gère l'upload d'un fichier
     * 
     * @param array $file Le fichier ($_FILES['document'])
     * @return string Le chemin du fichier sauvegardé
     */
    public function upload(array $file): string
    {
        $this->validateFile($file);

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = $this->generateFilename($extension);
        $destination = $this->uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception('Erreur lors de la sauvegarde du fichier');
        }

        return $destination;
    }

    /**
     * Valide le fichier uploadé
     */
    private function validateFile(array $file): void
    {
        // Vérifie les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception($this->getUploadErrorMessage($file['error']));
        }

        // Vérifie la taille
        if ($file['size'] > $this->maxFileSize) {
            throw new Exception('Le fichier est trop volumineux (max 10 MB)');
        }

        // Vérifie l'extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowedExtensions)) {
            throw new Exception(
                'Type de fichier non autorisé. Extensions acceptées : ' . 
                implode(', ', $this->allowedExtensions)
            );
        }

        // Vérifie le type MIME réel du fichier
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf'
        ];

        if (!in_array($mimeType, $allowedMimes)) {
            throw new Exception('Type MIME non autorisé : ' . $mimeType);
        }
    }

    /**
     * Génère un nom de fichier unique
     */
    private function generateFilename(string $extension): string
    {
        return sprintf(
            'invoice_%s_%s.%s',
            date('Ymd_His'),
            bin2hex(random_bytes(4)),
            $extension
        );
    }

    /**
     * Retourne un message d'erreur lisible pour les erreurs d'upload
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée par PHP',
            UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire',
            UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement uploadé',
            UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été uploadé',
            UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
            UPLOAD_ERR_CANT_WRITE => 'Échec de l\'écriture sur le disque',
            UPLOAD_ERR_EXTENSION => 'Une extension PHP a arrêté l\'upload'
        ];

        return $errors[$errorCode] ?? 'Erreur d\'upload inconnue';
    }

    /**
     * Supprime un fichier
     */
    public function delete(string $filePath): bool
    {
        if (file_exists($filePath)) {
            return unlink($filePath);
        }
        return false;
    }

    /**
     * Retourne le chemin absolu du dossier uploads
     */
    public function getUploadDir(): string
    {
        return $this->uploadDir;
    }
}

