<?php

namespace Para\Tests\Unit\Entity;

use Para\Entity\Group;
use Para\Entity\GroupInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class GroupTest
 *
 * @package Para\Tests\Unit\Entity
 */
class GroupTest extends TestCase
{
    /**
     * The group to test.
     *
     * @var \Para\Entity\GroupInterface
     */
    private $group;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->group = new Group();
    }

    /**
     * Tests that the getName() method returns the name of the group.
     */
    public function testTheMethodGetNameReturnsTheGroupName()
    {
        $name = 'new_group';
        $this->group->setName($name);

        $result = $this->group->getName();

        $this->assertEquals($name, $result);
    }

    /**
     * Tests that the getProjects() method returns an array of projects.
     */
    public function testTheGetProjectMethodReturnsAnArrayOfProjects()
    {
        $project1 = $this->prophesize(GroupInterface::class);
        $project1->getName()->willReturn('project1');
        $project2 = $this->prophesize(GroupInterface::class);
        $project2->getName()->willReturn('project2');

        $projects = [$project1->reveal(), $project2->reveal()];
        $this->group->setProjects($projects);

        $result = $this->group->getProjects();

        $this->assertTrue(is_array($result));
        $this->assertEquals($projects, $result);
    }
}
