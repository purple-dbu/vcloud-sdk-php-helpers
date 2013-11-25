<?php

namespace Test\VCloud\Helpers\Unit;

class QueryTestCase extends \PHPUnit_Framework_TestCase
{
    protected $queryService;

    public function setUp()
    {
        $this->queryService = new Stub\QueryService();
    }

    public function testQueryRecords()
    {
        $queryRecords = \VCloud\Helpers\Query::create($this->queryService)->queryRecords('adminUser');
        $this->assertEquals(
            86,
            count($queryRecords)
        );
    }

    public function testQueryRecord()
    {
        $queryRecord = \VCloud\Helpers\Query::create($this->queryService)->queryRecord(
            'adminUser',
            'href==https://vcloud-director.local/api/admin/user/23d6deb1-1778-4325-8289-2f150d122674'
        );
        $this->assertEquals(
            'VMware_VCloud_API_QueryResultAdminUserRecordType',
            get_class($queryRecord)
        );
    }

    public function testQueryRecordNotFound()
    {
        $queryRecord = \VCloud\Helpers\Query::create($this->queryService)->queryRecord(
            'adminUser',
            'href==tupeuxpastest'
        );
        $this->assertEquals(
            false,
            $queryRecord
        );
    }

    public function testQueryReferences()
    {
        $queryReferences = \VCloud\Helpers\Query::create($this->queryService)->queryReferences('adminUser');
        $this->assertEquals(
            87,
            count($queryReferences)
        );
    }

    public function testQueryReference()
    {
        $queryReference = \VCloud\Helpers\Query::create($this->queryService)->queryReference(
            'adminUser',
            'href==https://vcloud-director.local/api/admin/user/23d6deb1-1778-4325-8289-2f150d122674'
        );
        $this->assertEquals(
            'VMware_VCloud_API_ReferenceType',
            get_class($queryReference)
        );
    }

    public function testQueryReferenceNotFound()
    {
        $queryReference = \VCloud\Helpers\Query::create($this->queryService)->queryReference(
            'adminUser',
            'href==tupeuxpastest'
        );
        $this->assertEquals(
            false,
            $queryReference
        );
    }
}
