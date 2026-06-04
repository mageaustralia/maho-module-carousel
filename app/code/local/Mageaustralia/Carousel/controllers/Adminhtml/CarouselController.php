<?php

declare(strict_types=1);

/**
 * Mageaustralia_Carousel
 *
 * @copyright  Copyright (c) 2026 Mage Australia (https://mageaustralia.com.au)
 * @license    https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Mageaustralia_Carousel_Adminhtml_CarouselController extends Mage_Adminhtml_Controller_Action
{
    public const ADMIN_RESOURCE = 'admin/cms/carousel';

    /**
     * Force form_key (CSRF token) validation on all state-changing actions.
     */
    #[\Override]
    public function preDispatch()
    {
        $this->_setForcedFormKeyActions([
            'save',
            'delete',
            'massDelete',
            'saveSlide',
            'deleteSlide',
            'updateSlideOrder',
            'uploadImage',
        ]);
        return parent::preDispatch();
    }

    /**
     * Check ACL permissions
     */
    #[\Override]
    protected function _isAllowed(): bool
    {
        return Mage::getSingleton('admin/session')->isAllowed('cms/carousel');
    }

    /**
     * Initialize carousel
     */
    protected function _initCarousel(): Mage_Core_Model_Abstract
    {
        $carouselId = $this->getRequest()->getParam('id');
        $carousel = Mage::getModel('carousel/carousel');

        if ($carouselId) {
            $carousel->load($carouselId);
        }

        Mage::register('current_carousel', $carousel);
        return $carousel;
    }

    /**
     * List carousels
     */
    #[Maho\Config\Route('/admin/carousel/index')]
    public function indexAction(): void
    {
        $this->_title($this->__('CMS'))
             ->_title($this->__('DaisyUI Carousel Builder'));

        $this->loadLayout();
        $this->_setActiveMenu('cms/carousel');

        $this->_addContent($this->getLayout()->createBlock('carousel/adminhtml_carousel'));
        $this->renderLayout();
    }

    /**
     * New carousel
     */
    #[Maho\Config\Route('/admin/carousel/new')]
    public function newAction(): void
    {
        $this->_forward('edit');
    }

    /**
     * Edit carousel
     */
    #[Maho\Config\Route('/admin/carousel/edit')]
    public function editAction(): void
    {
        $carousel = $this->_initCarousel();

        $this->_title($this->__('CMS'))
             ->_title($this->__('DaisyUI Carousel Builder'))
             ->_title($carousel->getId() ? $carousel->getTitle() : $this->__('New Carousel'));

        $this->loadLayout();
        $this->_setActiveMenu('cms/carousel');

        $this->_addContent($this->getLayout()->createBlock('carousel/adminhtml_carousel_edit'));
        $this->_addLeft($this->getLayout()->createBlock('carousel/adminhtml_carousel_edit_tabs'));

        $this->renderLayout();
    }

    /**
     * Save carousel
     */
    #[Maho\Config\Route('/admin/carousel/save')]
    public function saveAction(): void
    {
        if ($data = $this->getRequest()->getPost()) {
            try {
                $carousel = $this->_initCarousel();

                // Save carousel data
                $carousel->addData($data);

                // Process identifier
                if (!$carousel->getIdentifier()) {
                    $identifier = Mage::helper('core')->formatUrlKey($carousel->getTitle());
                    $carousel->setIdentifier($identifier);
                }

                $carousel->save();

                // Handle slides data
                if (isset($data['slides']) && is_array($data['slides'])) {
                    $this->_saveSlides($carousel, $data['slides']);
                }

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('Carousel was successfully saved'),
                );

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', ['id' => $carousel->getId()]);
                } else {
                    $this->_redirect('*/*/');
                }
                return;

            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id')]);
                return;
            }
        }

        $this->_redirect('*/*/');
    }

    /**
     * Save carousel slides
     */
    protected function _saveSlides(Mage_Core_Model_Abstract $carousel, array $slidesData): void
    {
        $existingSlides = [];

        // Process slides data
        foreach ($slidesData as $slideData) {
            if (!empty($slideData['delete'])) {
                // Delete slide
                if (!empty($slideData['slide_id'])) {
                    $slide = Mage::getModel('carousel/slide')->load($slideData['slide_id']);
                    $slide->delete();
                }
                continue;
            }

            if (!empty($slideData['slide_id'])) {
                // Update existing slide
                $slide = Mage::getModel('carousel/slide')->load($slideData['slide_id']);
            } else {
                // Create new slide
                $slide = Mage::getModel('carousel/slide');
                $slide->setCarouselId($carousel->getId());
            }

            // Handle image upload
            if (!empty($_FILES['slides']['name'][$slideData['position']]['image'])) {
                $imageData = [
                    'name' => $_FILES['slides']['name'][$slideData['position']]['image'],
                    'type' => $_FILES['slides']['type'][$slideData['position']]['image'],
                    'tmp_name' => $_FILES['slides']['tmp_name'][$slideData['position']]['image'],
                    'error' => $_FILES['slides']['error'][$slideData['position']]['image'],
                    'size' => $_FILES['slides']['size'][$slideData['position']]['image'],
                ];

                $imagePath = $this->_processImageUpload($imageData, $carousel->getId());
                if ($imagePath) {
                    $slideData['image'] = $imagePath;
                }
            }

            $slide->addData($slideData);
            $slide->save();

            $existingSlides[] = $slide->getId();
        }

        // Delete slides that were removed
        $slidesToDelete = Mage::getModel('carousel/slide')->getCollection()
            ->addFieldToFilter('carousel_id', $carousel->getId());

        if (!empty($existingSlides)) {
            $slidesToDelete->addFieldToFilter('slide_id', ['nin' => $existingSlides]);
        }

        foreach ($slidesToDelete as $slide) {
            $slide->delete();
        }
    }

    /**
     * Process image upload with modern format conversion
     */
    protected function _processImageUpload(array $fileData, int|string $carouselId): ?string
    {
        try {
            // Sanitise the carousel id used as a directory name: digits only, or
            // the literal "temp" bucket. Prevents path traversal / null-byte
            // injection through the carousel_id request param.
            $safeId = ((string) $carouselId === 'temp') ? 'temp' : (string) (int) $carouselId;

            $uploader = new Varien_File_Uploader($fileData);
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png', 'webp']);
            // Validate by real (finfo-detected) content type, not just the
            // extension/client header. Blocks PHP or SVG-with-script renamed to
            // an image extension.
            $uploader->setValidMimeTypes([
                'image/jpeg',
                'image/png',
                'image/gif',
                'image/webp',
            ]);
            $uploader->setAllowRenameFiles(true);
            $uploader->setFilesDispersion(false);

            // Use public/media directory for MAHO
            $mediaPath = BP . DS . 'public' . DS . 'media' . DS . 'carousel' . DS . $safeId;

            // Create directory if it doesn't exist
            if (!file_exists($mediaPath)) {
                mkdir($mediaPath, 0775, true);
            }
            $result = $uploader->save($mediaPath);

            if ($result['file']) {
                // Process with our Image model for WebP/AVIF conversion
                $imageModel = Mage::getModel('carousel/image');
                $processed = $imageModel->processCarouselImage(
                    $result['path'] . DS . $result['file'],
                    $safeId,
                    [
                        'formats' => ['webp', 'avif', 'original'],
                        'quality' => 85,
                    ],
                );

                // Return the original path, but we'll have WebP/AVIF versions available
                return $safeId . '/' . $result['file'];
            }
        } catch (Exception $e) {
            Mage::logException($e);
        }

        return null;
    }

    /**
     * Delete carousel
     */
    #[Maho\Config\Route('/admin/carousel/delete')]
    public function deleteAction(): void
    {
        if ($id = $this->getRequest()->getParam('id')) {
            try {
                $carousel = Mage::getModel('carousel/carousel')->load($id);
                $carousel->delete();

                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('Carousel was successfully deleted'),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/');
    }

    /**
     * Grid action for AJAX
     */
    #[Maho\Config\Route('/admin/carousel/grid')]
    public function gridAction(): void
    {
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('carousel/adminhtml_carousel_grid')->toHtml(),
        );
    }

    /**
     * Slides grid for AJAX
     */
    #[Maho\Config\Route('/admin/carousel/slidesGrid')]
    public function slidesGridAction(): void
    {
        $this->_initCarousel();
        $this->loadLayout();
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('carousel/adminhtml_carousel_edit_tab_slides')->toHtml(),
        );
    }

    /**
     * Upload slide image via AJAX
     */
    #[Maho\Config\Route('/admin/carousel/uploadImage')]
    public function uploadImageAction(): void
    {
        $result = ['error' => false];

        try {
            $carouselId = $this->getRequest()->getParam('carousel_id', 'temp');

            if (isset($_FILES['image'])) {
                $imagePath = $this->_processImageUpload($_FILES['image'], $carouselId);
                if ($imagePath) {
                    // Get responsive sources for the uploaded image
                    $imageModel = Mage::getModel('carousel/image');
                    $sources = $imageModel->getResponsiveImageSources($imagePath);

                    $result['file'] = $imagePath;
                    $result['url'] = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'carousel/' . $imagePath;
                    $result['sources'] = $sources;
                } else {
                    $result['error'] = true;
                    $result['message'] = $this->__('Failed to upload image');
                }
            } else {
                $result['error'] = true;
                $result['message'] = $this->__('No file uploaded');
            }
        } catch (Exception $e) {
            $result['error'] = true;
            $result['message'] = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($result));
    }

    /**
     * Mass delete action
     */
    #[Maho\Config\Route('/admin/carousel/massDelete')]
    public function massDeleteAction(): void
    {
        $carouselIds = $this->getRequest()->getParam('carousel');
        if (!is_array($carouselIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select carousel(s)'));
        } else {
            try {
                foreach ($carouselIds as $carouselId) {
                    $carousel = Mage::getModel('carousel/carousel')->load($carouselId);
                    $carousel->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    $this->__('Total of %d carousel(s) were successfully deleted', count($carouselIds)),
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }

        $this->_redirect('*/*/index');
    }

    /**
     * Save individual slide via AJAX
     */
    #[Maho\Config\Route('/admin/carousel/saveSlide')]
    public function saveSlideAction(): void
    {
        $response = ['success' => false];

        try {
            $data = $this->getRequest()->getPost();
            $carouselId = $this->getRequest()->getParam('carousel_id');

            if (!$carouselId) {
                throw new Exception('Carousel ID is required');
            }

            if (!empty($data['slide_id'])) {
                $slide = Mage::getModel('carousel/slide')->load($data['slide_id']);
                if (!$slide->getId()) {
                    throw new Exception('Slide not found');
                }
            } else {
                $slide = Mage::getModel('carousel/slide');
                $slide->setCarouselId($carouselId);
            }

            // Handle image upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $imagePath = $this->_processImageUpload($_FILES['image'], $carouselId);
                if ($imagePath) {
                    $data['image'] = $imagePath;
                }
            }

            $slide->addData($data);
            $slide->save();

            $response['success'] = true;
            $response['slide_id'] = $slide->getId();
            $response['message'] = $this->__('Slide saved successfully');

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Delete slide via AJAX
     */
    #[Maho\Config\Route('/admin/carousel/deleteSlide')]
    public function deleteSlideAction(): void
    {
        $response = ['success' => false];

        try {
            $slideId = $this->getRequest()->getParam('slide_id');

            if (!$slideId) {
                throw new Exception('Slide ID is required');
            }

            $slide = Mage::getModel('carousel/slide')->load($slideId);
            if (!$slide->getId()) {
                throw new Exception('Slide not found');
            }

            // Delete image files
            if ($slide->getImage()) {
                $mediaPath = BP . DS . 'public' . DS . 'media' . DS . 'carousel' . DS;
                $imagePath = $mediaPath . $slide->getImage();

                // Delete original and converted formats
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }

                $pathInfo = pathinfo($imagePath);
                $webpPath = $pathInfo['dirname'] . DS . $pathInfo['filename'] . '.webp';
                $avifPath = $pathInfo['dirname'] . DS . $pathInfo['filename'] . '.avif';

                if (file_exists($webpPath)) {
                    unlink($webpPath);
                }
                if (file_exists($avifPath)) {
                    unlink($avifPath);
                }
            }

            $slide->delete();

            $response['success'] = true;
            $response['message'] = $this->__('Slide deleted successfully');

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Update slide order via AJAX
     */
    #[Maho\Config\Route('/admin/carousel/updateSlideOrder')]
    public function updateSlideOrderAction(): void
    {
        $response = ['success' => false];

        try {
            // Handle JSON request body
            $rawBody = $this->getRequest()->getRawBody();
            $data = json_decode($rawBody, true);
            $order = $data['order'] ?? $this->getRequest()->getParam('order');

            if (!is_array($order)) {
                throw new Exception('Invalid order data');
            }

            foreach ($order as $item) {
                if (isset($item['slide_id']) && isset($item['sort_order'])) {
                    $slide = Mage::getModel('carousel/slide')->load($item['slide_id']);
                    if ($slide->getId()) {
                        $slide->setSortOrder($item['sort_order']);
                        $slide->save();
                    }
                }
            }

            $response['success'] = true;
            $response['message'] = $this->__('Slide order updated successfully');

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }

    /**
     * Get slide data for editing via AJAX
     */
    #[Maho\Config\Route('/admin/carousel/getSlide')]
    public function getSlideAction(): void
    {
        $response = ['success' => false];

        try {
            $slideId = $this->getRequest()->getParam('slide_id');

            if (!$slideId) {
                throw new Exception('Slide ID is required');
            }

            $slide = Mage::getModel('carousel/slide')->load($slideId);
            if (!$slide->getId()) {
                throw new Exception('Slide not found');
            }

            $response['success'] = true;
            $response['slide'] = [
                'slide_id' => $slide->getId(),
                'title' => $slide->getTitle(),
                'subtitle' => $slide->getSubtitle(),
                'image' => $slide->getImage(),
                'image_url' => $slide->getImage() ? Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA) . 'carousel/' . $slide->getImage() : '',
                'link_url' => $slide->getLinkUrl(),
                'button_text' => $slide->getButtonText(),
                'button_style' => $slide->getButtonStyle(),
                'custom_html' => $slide->getCustomHtml(),
                'text_width' => $slide->getTextWidth(),
                'text_alignment' => $slide->getTextAlignment(),
                'sort_order' => $slide->getSortOrder(),
                'status' => $slide->getStatus(),
            ];

        } catch (Exception $e) {
            $response['message'] = $e->getMessage();
        }

        $this->getResponse()->setHeader('Content-Type', 'application/json');
        $this->getResponse()->setBody(json_encode($response));
    }
}
