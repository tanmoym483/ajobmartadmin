<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class HomeBanners extends BaseController
{
    protected $db;
    protected $layout = 'shipping_admin/layouts/main';
    protected $uploadPath;

    public function __construct()
    {
        $this->db = db_connect();

        $sharedPath = rtrim(
            env('AJOBMART_SHARED_UPLOAD_PATH'),
            '/\\'
        );

        $this->uploadPath = $sharedPath
            . DIRECTORY_SEPARATOR . 'uploads'
            . DIRECTORY_SEPARATOR . 'home-banners'
            . DIRECTORY_SEPARATOR;

        if (
            !is_dir($this->uploadPath) &&
            !mkdir($this->uploadPath, 0755, true) &&
            !is_dir($this->uploadPath)
        ) {
            throw new \RuntimeException('Unable to create banner upload directory.');
        }
    }
    

    /**
     * Banner listing
     */
    public function index()
    {
        $data['banners'] = $this->db
            ->table('ajobmart_home_banners')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'DESC')
            ->get()
            ->getResultArray();

        return view(
            'admin/home_banners/index',
            $data
        );
    }

    /**
     * Add banner form
     */
    public function create()
    {
        return view(
            'admin/home_banners/create',
            []
        );
    }

    /**
     * Validate banner dimensions
     */
    private function validBannerImage($image)
{
    if (!$image || !$image->isValid()) {
        return false;
    }

    $dimensions = @getimagesize($image->getTempName());

    if (!$dimensions) {
        return false;
    }

    $width  = $dimensions[0];
    $height = $dimensions[1];

    return $width >= 600 && $height >= 200;
}

    /**
     * Store banner
     */
    
public function store()
{
    $validation = [
        'title' => 'required|max_length[150]',
        'image' => 'uploaded[image]|max_size[image,712]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp,image/avif]',
        'target_url' => 'permit_empty|max_length[500]',
        'sort_order' => 'permit_empty|integer',
        'status' => 'required|in_list[active,inactive]',
        'open_new_tab' => 'permit_empty|in_list[0,1]',
    ];

    if (!$this->validate($validation)) {
        return $this->response
            ->setStatusCode(422)
            ->setJSON([
                'status' => 'error',
                'message' => 'Please correct the highlighted fields.',
                'errors' => $this->validator->getErrors(),
                'csrfHash' => csrf_hash(),
            ]);
    }

    $image = $this->request->getFile('image');

    if ($image && $image->isValid()) {
    log_message('debug', 'Banner upload details: ' . json_encode([
        'original_name' => $image->getClientName(),
        'extension'     => $image->getClientExtension(),
        'detected_mime' => $image->getMimeType(),
        'client_mime'   => $image->getClientMimeType(),
        'size'          => $image->getSize(),
    ]));
}

    if (!$this->validBannerImage($image)) {
        return $this->response
            ->setStatusCode(422)
            ->setJSON([
                'status' => 'error',
                'message' => 'Invalid banner image. Minimum 600 × 200 pixels and aspect ratio 2:1 to 4:1 required.',
                'csrfHash' => csrf_hash(),
            ]);
    }

    $newName = $image->getRandomName();

    if (!$image->move($this->uploadPath, $newName)) {
        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'status' => 'error',
                'message' => 'Banner image upload failed.',
                'csrfHash' => csrf_hash(),
            ]);
    }

    $data = [
        'title' => trim($this->request->getPost('title')),
        'image' => 'uploads/home-banners/' . $newName,
        'target_url' => trim($this->request->getPost('target_url') ?? ''),
        'open_new_tab' => (int) ($this->request->getPost('open_new_tab') ?? 0),
        'sort_order' => (int) ($this->request->getPost('sort_order') ?? 0),
        'status' => $this->request->getPost('status'),
    ];

    $inserted = $this->db
        ->table('ajobmart_home_banners')
        ->insert($data);

    if (!$inserted) {
        @unlink($this->uploadPath . $newName);

        return $this->response
            ->setStatusCode(500)
            ->setJSON([
                'status' => 'error',
                'message' => 'Unable to save banner in the database.',
                'csrfHash' => csrf_hash(),
            ]);
    }

    return $this->response->setJSON([
        'status' => 'success',
        'message' => 'Banner added successfully.',
        'csrfHash' => csrf_hash(),
    ]);
}


    /**
     * Edit banner form
     */
    public function edit($id)
    {
        $banner = $this->db
            ->table('ajobmart_home_banners')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$banner) {
            return redirect()->to(site_url('admin/home-banners'))
                ->with('error', 'Banner not found.');
        }

        return view(
            'admin/home_banners/edit',
            ['banner' => $banner]
        );
    }

    /**
     * Update banner
     */
    public function update($id)
    {
        $banner = $this->db
            ->table('ajobmart_home_banners')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$banner) {
            return redirect()->to(site_url('admin/home-banners'))
                ->with('error', 'Banner not found.');
        }

        $validation = [
            'title' => 'required|max_length[150]',
            'image' => 'permit_empty|max_size[image,512]|is_image[image]|mime_in[image,image/jpeg,image/png,image/webp]',
            'target_url' => 'permit_empty|max_length[500]',
            'sort_order' => 'permit_empty|integer',
            'status' => 'required|in_list[active,inactive]',
            'open_new_tab' => 'permit_empty|in_list[0,1]',
        ];

        if (!$this->validate($validation)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        $data = [
            'title'        => trim($this->request->getPost('title')),
            'target_url'   => trim($this->request->getPost('target_url') ?? ''),
            'open_new_tab' => (int) ($this->request->getPost('open_new_tab') ?? 0),
            'sort_order'   => (int) ($this->request->getPost('sort_order') ?? 0),
            'status'       => $this->request->getPost('status'),
        ];

        $image = $this->request->getFile('image');

        $newName = null;

        if ($image && $image->isValid()) {

            if (!$this->validBannerImage($image)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid banner dimensions.');
            }

            $newName = $image->getRandomName();

            if (!$image->move($this->uploadPath, $newName)) {
                return redirect()->back()
                    ->with('error', 'Image upload failed.');
            }

            $data['image'] = 'uploads/home-banners/' . $newName;
        }

        $updated = $this->db
            ->table('ajobmart_home_banners')
            ->where('id', (int) $id)
            ->update($data);

        if (!$updated) {

            if ($newName) {
                @unlink($this->uploadPath . $newName);
            }

            return redirect()->back()
                ->withInput()
                ->with('error', 'Unable to update banner.');
        }

        // Delete previous image only after successful update.
        if ($newName) {

            $oldPath = $this->uploadPath . basename($banner['image']);

            if (is_file($oldPath)) {
                @unlink($oldPath);
            }
        }

        return redirect()->to(site_url('admin/home-banners'))
            ->with('success', 'Banner updated successfully.');
    }

    /**
     * Delete banner
     */
    public function delete($id)
    {
        $banner = $this->db
            ->table('ajobmart_home_banners')
            ->where('id', (int) $id)
            ->get()
            ->getRowArray();

        if (!$banner) {
            return redirect()->back()
                ->with('error', 'Banner not found.');
        }

        $deleted = $this->db
            ->table('ajobmart_home_banners')
            ->where('id', (int) $id)
            ->delete();

        if (!$deleted) {
            return redirect()->back()
                ->with('error', 'Unable to delete banner.');
        }

        $oldPath = $this->uploadPath . basename($banner['image']);

        if (is_file($oldPath)) {
            @unlink($oldPath);
        }

        return redirect()->to(site_url('admin/home-banners'))
            ->with('success', 'Banner deleted successfully.');
    }
}
