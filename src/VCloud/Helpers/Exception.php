<?php
/**
 * vCloud PHP SDK Helpers
 * @link (Github, https://github.com/amercier/vcloud-sdk-php)
 */

namespace VCloud\Helpers;

/**
 * The Exception Helper gives you the ability to manipulate vCloud SDK exceptions
 * (VMware_VCloud_SDK_Exception) with ease. It allows extracting the error codes
 * and messages from the original exception message, with is just raw XML of the
 * form:
 *
 * ```xml
 * <Error
 *     xmlns="http://www.vmware.com/vcloud/v1.5"
 *     message="xs:string"
 *     majorErrorCode="xs:int"
 *     minorErrorCode="xs:string"
 *     vendorSpecificErrorCode="xs:string"
 *     stackTrace="xs:string"
 * />
 * ```
 */
class Exception
{
    /**
     * @var \Exception The original exception
     */
    protected $originalException;

    /**
     * @var \SimpleXMLElement The parsed error message
     */
    protected $document;

    /**
     * Create a new Exception Helper
     *
     * @param $originalException Any exception thrown by VMware VCloud SDK for PHP
     */
    public function __construct(\VMware_VCloud_SDK_Exception $originalException)
    {
        $this->originalException = $originalException;
        $this->document = new \SimpleXMLElement($originalException->getMessage());
    }

    /**
     * Create a new Exception Helper and returns it without modifications. This
     * form allow chaining in ALL versions of PHP:
     *
     *     \VCloud\Helpers\Exception::create($e)->getMessage()
     *
     * Since PHP 5.4, Class member access on instantiation is allowed:
     *
     *     new (\VCloud\Helpers\Exception($e))->getMessage()
     *
     * @param $originalException Any exception thrown by VMware VCloud SDK for PHP
     * @return Exception Returns a new Exception Handler
     */
    public static function create(\VMware_VCloud_SDK_Exception $originalException)
    {
        return new self($originalException);
    }

    /**
     * Get the original exception
     *
     * @return \VMware_VCloud_SDK_Exception The original exception
     */
    public function getOriginalException()
    {
        return $this->originalException;
    }

    /**
     * Get the error message
     *
     * @return string The error message
     */
    public function getMessage()
    {
        return $this->document->attributes()->message->__toString();
    }

    /**
     * Get the error major error code
     *
     * @return string The error major error code
     */
    public function getMajorErrorCode()
    {
        return $this->document->attributes()->majorErrorCode->__toString();
    }

    /**
     * Get the error minor error code
     *
     * @return string The error minor error code
     */
    public function getMinorErrorCode()
    {
        return $this->document->attributes()->minorErrorCode->__toString();
    }

    /**
     * Get the error stack trace
     *
     * @return string The error stack trace
     */
    public function getStackTrace()
    {
        return preg_replace(
            '/  +at/',
            "\n             at",
            $this->document->attributes()->stackTrace->__toString()
        );
    }
}
