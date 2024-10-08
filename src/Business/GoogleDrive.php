<?php

namespace App\Business;

use Google\Service\Exception;
use Google_Client;
use Google_Service_Drive;
use ZipArchive;

class GoogleDrive
{
    private Google_Service_Drive $service;

    /**
     * @throws \Google\Exception
     */
    public function __construct(
        string $serviceAccountPath
    )
    {
        $this->service = $this->getGoogleDriveService($serviceAccountPath);
    }

    /**
     * @throws \Google\Exception
     */
    private function getGoogleDriveService(string $serviceAccountPath): Google_Service_Drive
    {
        $client = new Google_Client();
        $client->setAuthConfig($serviceAccountPath);
        $client->setScopes(Google_Service_Drive::DRIVE);
        $client->useApplicationDefaultCredentials();

        return new Google_Service_Drive($client);
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    public function downloadFolderAsZip(string $folderId, string $zipFilePath): string
    {
        $zip = new ZipArchive();
        if (dump($zip->open($zipFilePath, ZipArchive::CREATE)) !== true) {
            throw new \Exception('Could not create ZIP file.');
        }
        $this->addFolderToZip($folderId, $zip);
        $zip->close();
        return $zipFilePath;

    }

    /**
     * @throws Exception
     */
    private function addFolderToZip(string $folderId, ZipArchive $zip, string $currentPath = ''): void
    {
        $query = "'" . $folderId . "' in parents and trashed = false";
        $files = $this->service->files->listFiles(['q' => $query]);

        foreach ($files->getFiles() as $file) {
            if ($file->getMimeType() === 'application/vnd.google-apps.folder') {
                // It's a folder, so recursively add it
                $folderName = $currentPath . $file->getName() . '/';
                $zip->addEmptyDir($folderName);
                $this->addFolderToZip($file->getId(), $zip, $folderName);
            } elseif ($file->getMimeType() === 'application/vnd.google-apps.document') {
                // It's a Google Docs file, export it as PDF
                $pdfContent = $this->service->files->export($file->getId(), 'application/pdf', ['alt' => 'media']);
                $pdfName = $currentPath . $file->getName() . '.pdf';
                $zip->addFromString($pdfName, $pdfContent->getBody()->getContents());
            }
        }
    }
}
