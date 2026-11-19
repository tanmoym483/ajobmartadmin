<?php

namespace App\Controllers;
use App\Controllers\BaseController;

class SharedImages extends BaseController
{
    public function show(...$segments)
    {
        $relativePath = implode('/', $segments);

        // ajobmart-shared is directly inside htdocs.
        $basePath = realpath(dirname(ROOTPATH) . '/ajobmart-shared');

        if ($basePath === false) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $filePath = realpath(
            $basePath . DIRECTORY_SEPARATOR . $relativePath
        );

        // Prevent access outside the shared folder.
        if (
            $filePath === false ||
            !str_starts_with(
                $filePath,
                $basePath . DIRECTORY_SEPARATOR
            ) ||
            !is_file($filePath)
        ) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $mime = mime_content_type($filePath);

        if (!in_array($mime, [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif'
        ], true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'public, max-age=86400')
            ->setBody(file_get_contents($filePath));
    }
}