<?php
/**
 * vCloud PHP SDK Helpers
 * @link (Github, https://github.com/amercier/vcloud-sdk-php)
 */

namespace VCloud\Helpers;

use \SplObjectStorage;
use \SplObserver;
use \SplSubject;
use \Closure;
use \VMware_VCloud_API_CatalogItemType;
use \VMware_VCloud_SDK_Constants;
use \VMware_VCloud_SDK_Exception;
use \VMware_VCloud_API_MediaType;
use \VMware_VCloud_API_ReferenceType;
use \VCloud\Helpers\Exception as ExceptionHelper;

/**
 * The Upload Helper gives you the ability to upload media and vApp templates
 * with ease:
 *
 * ```php
 * $upload = new Upload('/path/to/my/media.iso', $catalogReference, $orgVdcReference);
 * $upload->start();
 * ```
 *
 * The Upload class implements the SplSubject interface. To monitor the upload
 * progress, you can attach an SplObserver:
 *
 * ```
 * class MyClass extends SplObserver
 * {
 *     $upload = null;
 *
 *     public function doTheJob()
 *     {
 *         ...
 *         $this->upload = new Upload('/path/to/my/media.iso', $catalogReference, $orgVdcReference);
 *         $this->upload->attachObserver($this);
 *         $upload->start();
 *     }
 *
 *     // This method will be called everytime a proprty of the upload changes
 *     public function update($subject, $eventType)
 *     {
 *         if ($subject === $this->upload) {
 *            // do something depending on upload property change ...
 *         }
 *     }
 * }
 * ```
 */
class Upload implements SplSubject
{
    const SUPPORTED_FILES = 'ovf ova iso flp';
    const STATE_ERROR = 0;
    const STATE_IDLE = 1;
    const STATE_UPLOADING = 2;
    const STATE_TRANSFERRING = 3;
    const STATE_INDEXING = 4;
    const STATE_SUCCESS = 5;
    const STATE_DELETING = 6;

    const DEFAULT_REFRESH_RATE = 1.0;

    protected static $CONTENT_TYPES = array(
        'flp' => VMware_VCloud_SDK_Constants::MEDIA_CONTENT_TYPE,
        'iso' => VMware_VCloud_SDK_Constants::MEDIA_CONTENT_TYPE,
        'ova' => VMware_VCloud_SDK_Constants::VAPP_TEMPLATE_CONTENT_TYPE,
        'ovf' => VMware_VCloud_SDK_Constants::VAPP_TEMPLATE_CONTENT_TYPE,
    );

    protected $service;
    protected $observers;
    protected $filePath;
    protected $fileSize;
    protected $catalog;
    protected $orgVdc;
    protected $name;
    protected $type;

    protected $state;
    protected $progress;
    protected $error;
    protected $refreshRate;

    public function __construct(
        $service,
        $filePath,
        $catalogReference,
        $orgVdcReference,
        $refreshRate = self::DEFAULT_REFRESH_RATE
    ) {
        $this->observers = new SplObjectStorage();
        $this->service = $service;
        $this->orgVdc = $this->service->createSDkObj($orgVdcReference);
        $this->catalog = $this->service->createSDkObj($catalogReference);
        $this->refreshRate = $refreshRate;
        $this->filePath = $filePath;
        $this->updateFileInfo();
        $this->state = self::STATE_IDLE;
    }

    protected function updateFileInfo()
    {
        $basename = basename($this->filePath);
        if (!preg_match(
            '/^(.*)\\.(' . implode('|', self::getSupportedFiles()) . ')$/',
            $basename,
            $matches
        )) {
            throw new \Exception(
                'Invalid file ' . $this->filePath
                . '. Supported file types: '
                . implode(', ', self::getSupportedFiles())
            );
        }

        $this->name = $matches[1];
        $this->type = $matches[2];
        $this->fileSize = filesize($this->filePath);

        if (!file_exists($this->filePath)) {
            $this->setState(self::STATE_ERROR);
            throw new \Exception('File ' . $this->filePath . ' does not exist');
        }
        if (!is_readable($this->filePath)) {
            $this->setState(self::STATE_ERROR);
            throw new \Exception('File ' . $this->filePath . ' exists but is not readable');
        }
        if (is_dir($this->filePath)) {
            $this->setState(self::STATE_ERROR);
            throw new \Exception('File ' . $this->filePath . ' is a directory');
        }
    }

    public function attach(SplObserver $observer)
    {
        $this->observers->attach($observer);
    }

    public function detach(SplObserver $observer)
    {
        $this->observers->detach($observer);
    }

    public function notify($eventType = null, $previousValue = null)
    {
        foreach ($this->observers as $observer) {
            $observer->update($this, $eventType, $previousValue);
        }
    }

    public function getObservers()
    {
        return $this->observers;
    }

    public function getFilePath()
    {
        return $this->filePath;
    }

    protected function setFilePath($filePath)
    {
        $previousFilePath = $this->filePath;
        if ($filePath !== $previousFilePath) {
            $this->filePath = $filePath;
            $this->updateFileInfo();
            $this->notify('file', $previousFilePath);
        }
    }

    public function getCatalog()
    {
        return $this->catalog;
    }

    public function getOrgVdc()
    {
        return $this->orgVdc;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $previousName = $this->name;
        if ($name !== $previousName) {
            $this->name = $name;
            $this->notify('name', $previousName);
        }
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $previousState = $this->state;
        if ($state !== $previousState) {
            $this->state = $state;
            $this->notify('state', $previousState);
        }
    }

    public function getType()
    {
        return $this->type;
    }

    public function getFileSize()
    {
        return $this->fileSize;
    }

    public function getProgress()
    {
        return $this->progress;
    }

    public function setProgress($progress)
    {
        $previousProgress = $this->progress;
        if ($progress !== $previousProgress) {
            $this->progress = $progress;
            $this->notify('progress');
        }
    }

    protected function upload()
    {
        $this->setState(self::STATE_UPLOADING);
        $originalName = $this->getName();

        $i = 0;
        while (true) {
            try {
                while (count($this->getCatalog()->getCatalogItems($this->getName())) !== 0) {
                    $this->setName($originalName . '_' . ++$i);
                }
                switch ($this->getType()) {
                    case 'iso':
                    case 'flp':
                        $media = new VMware_VCloud_API_MediaType();
                        $media->set_name($this->getName());
                        $media->set_imageType($this->getType());
                        $media->set_size($this->getFileSize());

                        return $this->getOrgVDC()->uploadMedia(
                                $this->getFilePath(),
                                $this->getType() === 'iso' ? 'iso' : 'floppy',
                                $media,
                                Closure::bind(function ($done) {
                                    $this->setProgress($done);
                                }, $this)
                            )->get_href();
                    case 'ovf':
                        return $this->getOrgVDC()->uploadOVFAsVAppTemplate(
                            $this->getName(),
                            $this->getFilePath(),
                            null,
                            false,
                            null,
                            $this->getCatalog()->getCatalogRef()
                        );
                    case 'ova':
                        throw new \Exception('OVA upload not implemented yet');
                }
            } catch (VMware_VCloud_SDK_Exception $exception) {
                $e = new ExceptionHelper($exception);

                // If the name exists, rename _XXX
                if ($e->getMinorErrorCode() === 'DUPLICATE_NAME') {
                    $this->setName($originalName . '_' . ++$i);
                } else {
                    throw new \Exception($e->getMessage());
                }
            }
        }
    }

    protected function indexToCatalog($href)
    {
        $reference = new VMware_VCloud_API_ReferenceType();
        $reference->set_href($href);
        $reference->set_type(self::$CONTENT_TYPES[$this->getType()]);
        $sdkObject = $this->service->createSDkObj($reference);

        $this->setState(self::STATE_INDEXING);
        try {
            $item = new VMware_VCloud_API_CatalogItemType();
            $item->setEntity($reference);
            $item->set_name($this->getName());
            $this->getCatalog()->addCatalogItem($item);
            return $sdkObject;

        } catch (VMware_VCloud_SDK_Exception $e) {
            throw new \Exception(
                'Failed to index ' . $this->getName()
                . ' to catalog ' . $this->getCatalog()->getCatalog()->get_name() . '. '
                . ExceptionHelper::create($e)->getMessage()
            );
        }
    }

    protected function getDataObject($sdkObject)
    {
        switch ($this->getType()) {
            case 'iso':
            case 'flp':
                return $sdkObject->getMedia();
            case 'ovf':
            case 'ova':
                return $sdkObject->getVAppTemplate();
        }
    }

    protected function waitForTransfer($sdkObject)
    {
        $this->setState(self::STATE_TRANSFERRING);

        $status = 0;
        while ($status !== 1 && $status !== 8) {
            $dataObject = $this->getDataObject($sdkObject);
            $status = intval($dataObject->get_status());
            $tasks = $dataObject->getTasks();
            $tasks = $tasks === null ? null : $tasks->getTask();
            $task = $tasks === null ? null : $tasks[0];

            // Return true if task is finished
            if ($status === 1 || $status === 8) {
                $this->setProgress(100);
            } elseif ($status === -1) {
                // Throw an exception if status is -1
                $this->setState(self::STATE_ERROR);
                if ($task === null) {
                    throw new \Exception('Cannot upload. Unknown reason.');
                } elseif ($task->get_status() === 'error') {
                    $error = $task->getError();
                    throw new \Exception('Error during upload: ' . $error[0]->get_message());
                } else {
                    throw new \Exception('Stopped upload: ' . $task->get_status() . '.');
                }
            } else {
                // Otherwise (still transferring), update progress
                // a. Update progress
                if ($task !== null) {
                    $this->setProgress(intval($task->getProgress()));
                }

                // b. Sleep until next tick
                sleep($this->refreshRate);
            }
        }
    }

    public function start()
    {
        $href = $this->upload();
        $sdkObject = $this->indexToCatalog($href);
        try {
            $this->waitForTransfer($sdkObject);

        } catch (\Exception $e) {
            if ($e instanceof VMware_VCloud_SDK_Exception) {
                $e = new ExceptionHelper($e);
            }
            // Try to delete media if NOK
            $this->setState(self::STATE_DELETING);
            try {
                $sdkObject->delete();
                $this->setState(self::STATE_ERROR);
                throw new \Exception($e->getMessage() . ' Media has been deleted.');
            } catch (VMware_VCloud_SDK_Exception $e2) {
                $this->setState(self::STATE_ERROR);
                throw new \Exception(
                    $e->getMessage() . ' Failed to delete media. '
                    . ExceptionHelper::create($e2)->getMessage()
                );
            }
        }

        $this->setState(self::STATE_SUCCESS);
    }

    public static function getSupportedFiles()
    {
        return explode(' ', self::SUPPORTED_FILES);
    }
}
