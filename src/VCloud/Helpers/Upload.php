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
use \VMware_VCloud_SDK_Exception;
use \VMware_VCloud_API_MediaType;
use \VMware_VCloud_API_ReferenceType;
use \VMware_VCloud_API_CatalogItemType;
use Exception as ExceptionHelper;

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

    protected $service;
    protected $observers;
    protected $filePath;
    protected $fileSize;
    protected $catalogReference;
    protected $orgVdcReference;
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
        $this->catalogReference = $catalogReference;
        $this->orgVdcReference = $orgVdcReference;
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

    public function getCatalogReference()
    {
        return $this->catalogReference;
    }

    public function getOrgVdcReference()
    {
        return $this->orgVdcReference;
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

    public function start()
    {
        $originalName = $this->getName();
        $orgVDC = $this->service->createSDkObj($this->orgVdcReference);
        $catalog = $this->service->createSDkObj($this->catalogReference);

        // TODO ovf ova flp iso
        $mediaType = new VMware_VCloud_API_MediaType();
        $mediaType->set_name($originalName);
        $mediaType->set_imageType('iso');
        $mediaType->set_size($this->getFileSize());


        // 1. Upload

        $this->setState(self::STATE_UPLOADING);

        $media = null;
        $i = 0;
        while ($media === null) {
            try {
                $media = $orgVDC->uploadIsoMedia(
                    $this->getFilePath(),
                    $mediaType,
                    Closure::bind(function ($done) {
                        $this->setProgress($done);
                    }, $this)
                );
            } catch (VMware_VCloud_SDK_Exception $exception) {
                $e = new ExceptionHelper($exception);

                // If the name exists, rename _XXX
                if ($e->getMinorErrorCode() === 'DUPLICATE_NAME') {
                    $i++;
                    $name = $originalName . '_' . $i;
                    $mediaType->set_name($name);
                    $this->setName($name);
                } else {
                    throw new \Exception($e->getMessage());
                }
            }
        }

        // 2. Transfer to vDC

        $this->setState(self::STATE_TRANSFERRING);

        $mediaReference = new VMware_VCloud_API_ReferenceType();
        $mediaReference->set_href($media->get_href());
        $mediaReference->set_type('application/vnd.vmware.vcloud.media+xml');
        $media = $this->service->createSDkObj($mediaReference);

        try {
            $status = 0;
            while ($status !== 1) {
                $mediaData = $media->getMedia();
                $status = intval($mediaData->get_status());
                $tasks = $mediaData->getTasks();
                $tasks = $tasks === null ? null : $tasks->getTask();
                $task = $tasks === null ? null : $tasks[0];

                // Return true if task is finished
                if ($status === 1) {
                    $this->setProgress(100);
                } elseif ($status === -1) {
                    // Throw an exception if status is -1
                    $this->setState(self::STATE_ERROR);
                    if ($task === null) {
                        throw new \Exception('Cannot upload media. Unknown reason.');
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
                    } else {
                        echo print_r($tasks, true) . "\n";
                    }

                    // b. Sleep until next tick
                    sleep($this->refreshRate);
                }
            }
        // Try to delete media if NOK
        } catch (\Exception $e) {
            if ($e instanceof VMware_VCloud_SDK_Exception) {
                $e = new ExceptionHelper($e);
            }
            try {
                $this->setState(self::STATE_DELETING);
                $media->delete();
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

        // 3. Indexing

        // Index to catalog
        $item = new VMware_VCloud_API_CatalogItemType();
        $item->setEntity($mediaReference) ;
        $item->set_name($name);
        $catalog->addCatalogItem($item);

        $this->setState(self::STATE_SUCCESS);
    }

    public static function getSupportedFiles()
    {
        return explode(' ', self::SUPPORTED_FILES);
    }
}
