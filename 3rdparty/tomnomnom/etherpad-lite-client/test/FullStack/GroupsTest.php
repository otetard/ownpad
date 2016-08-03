<?php
require_once __DIR__.'/../../etherpad-lite-client.php';

class GroupsTest extends \PHPUnit_Framework_TestCase {

  protected function newClient(){
    return new \EtherpadLite\Client("dcf118bfc58cc69cdf3ae870071f97149924f5f5a9a4a552fd2921b40830aaae");
  }

  public function testCreateDeleteGroup(){
    $client = $this->newClient();

    // Create a group
    $group = $client->createGroup();
    $this->assertTrue(is_string($group->groupID));

    // Delete it
    $r = $client->deleteGroup($group->groupID);
    $this->assertNull($r);

    // Try to delete it again
    try {
      $client->deleteGroup($group->groupID);
      $this->fail("deleteGroup should fail if group does not exist");
    } catch (\InvalidArgumentException $e){
      $this->assertTrue(true); // Just to keep the counter up ;)
    }
  }

  public function testListGroups(){
    $client = $this->newClient();

    // Create a couple of groups
    $one = $client->createGroup();
    $two = $client->createGroup();

    $groups = $client->listAllGroups();
    $this->assertContains($one->groupID, $groups->groupIDs);
    $this->assertContains($two->groupID, $groups->groupIDs);

    // Clean up
    $client->deleteGroup($one->groupID);
    $client->deleteGroup($two->groupID);
  }
}
