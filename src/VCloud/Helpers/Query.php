<?php

namespace VCloud\Helpers;

/**
 * The Query Helper gives you the ability to manipulate the vCloud SDK Query
 * Service with ease. It provides abstraction for pagination.
 */
class Query
{
    /**
     * Default page size for queries
     */
    const DEFAULT_PAGE_SIZE = 128;

    /**
     * @var \VMware_VCloud_SDK_Query vCloud Director SDK for PHP Query Service
     */
    protected $queryService;

    /**
     * @var int Page size
     */
    protected $pageSize;

    /**
     * Create a new Query Helper
     * @param \VMware_VCloud_SDK_Query $queryService The vCloud Director SDK for PHP Query Service
     * @param int                      $pageSize     Page size
     */
    public function __construct(\VMware_VCloud_SDK_Query $queryService, $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        $this->queryService = $queryService;
        $this->pageSize = $pageSize;
    }

    /**
     * Create a new Query Helper and returns it without modifications. This
     * form allow chaining in ALL versions of PHP:
     *
     *     \VCloud\Helpers\Query::create($service)->queryRecords(...)
     *
     * Since PHP 5.4, Class member access on instantiation is allowed:
     *
     *     new (\VCloud\Helpers\Query($service))->queryRecords(...)
     *
     * @param \VMware_VCloud_SDK_Query $queryService The vCloud Director SDK for PHP Query Service
     * @param int                      $pageSize     Page size
     * @return Query Returns a new Query Handler
     */
    public static function create(\VMware_VCloud_SDK_Query $queryService, $pageSize = self::DEFAULT_PAGE_SIZE)
    {
        return new self($queryService, $pageSize);
    }

    /**
     * Determine whether a query page (VMware_VCloud_API_QueryResultRecordsType)
     * is the last page or not.
     *
     * @return boolean Returns true if it's the last page, false otherwise
     */
    protected function isLastPage(\VMware_VCloud_API_ResourceType $records)
    {
        foreach ($records->getLink() as $link) {
            if ($link->get_rel() === 'lastPage') {
                return false;
            }
        }
        return true;
    }

    /**
     * Send a query for a specific page and get records
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return \VMware_VCloud_API_QueryResultRecordsType Returns the query records
     */
    protected function queryRecordsPage($type, $filter, $page)
    {
        $params = new \VMware_VCloud_SDK_Query_Params();
        $params->setPageSize($this->pageSize);
        $params->setPage($page);

        if ($filter !== null) {
            $params->setFilter($filter);
        }

        return $this->queryService->queryRecords($type, $params);
    }

    /**
     * Send a query and get records
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return array Returns an array of VMware_VCloud_API_QueryResultRecordType
     */
    public function queryRecords($type, $filter = null)
    {
        $allRecords = array();
        for ($page = 1, $records = null; $records === null || !$this->isLastPage($records); $page++) {
            $records = $this->queryRecordsPage($type, $filter, $page);
            $allRecords = array_merge($allRecords, $records->getRecord());
        }
        return $allRecords;
    }

    /**
     * Send a query and get the first record
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return \VMware_VCloud_API_QueryResultRecordType|boolean Returns the first
     * record of the query, or false if there isn't any result.
     */
    public function queryRecord($type, $filter = null)
    {
        $records = $this->queryRecordsPage($type, $filter, 1)->getRecord();
        return count($records) > 0 ? $records[0] : false;
    }

    /**
     * Send a query for a specific page and get references
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return \VMware_VCloud_API_QueryResultReferencesType Returns the query references
     */
    protected function queryReferencesPage($type, $filter, $page)
    {
        $params = new \VMware_VCloud_SDK_Query_Params();
        $params->setPageSize($this->pageSize);
        $params->setPage($page);

        if ($filter !== null) {
            $params->setFilter($filter);
        }

        return $this->queryService->queryReferences($type, $params);
    }

    /**
     * Send a query and get references
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return array Returns an array of VMware_VCloud_API_QueryResultReferenceType
     */
    public function queryReferences($type, $filter = null)
    {
        $allReferences = array();
        for ($page = 1, $references = null; $references === null || !$this->isLastPage($references); $page++) {
            $references = $this->queryReferencesPage($type, $filter, $page);
            $allReferences = array_merge($allReferences, $references->getReference());
        }
        return $allReferences;
    }

    /**
     * Send a query and get the first reference
     *
     * @param string $type   The query type
     * @param string $filter The query filter
     * @param int    $page   The page to retrieve
     * @return \VMware_VCloud_API_QueryResultReferenceType|boolean Returns the first
     * reference of the query, or false if there isn't any result.
     */
    public function queryReference($type, $filter = null)
    {
        $references = $this->queryReferencesPage($type, $filter, 1)->getReference();
        return count($references) > 0 ? $references[0] : false;
    }
}
