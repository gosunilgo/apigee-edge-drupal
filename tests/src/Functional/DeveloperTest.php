<?php

namespace Drupal\Tests\apigee_edge\Functional;

use Apigee\Edge\Api\Management\Entity\Developer;
use Apigee\Edge\Exception\ClientErrorException;
use Drupal\Tests\BrowserTestBase;

/**
 * Create, delete, update Developer entity tests.
 *
 * @group ApigeeEdge
 */
class DeveloperTest extends BrowserTestBase {

  /**
   * The DeveloperController object.
   *
   * @var \Apigee\Edge\Api\Management\Controller\DeveloperController
   */
  protected $developerController;

  public static $modules = [
    'apigee_edge',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->profile = 'standard';
    parent::setUp();

    $this->developerController = $this->container->get('apigee_edge.sdk_connector')->getControllerByEntity('developer');
  }

  /**
   * Tests user/developer registration and edit.
   */
  public function testDeveloperRegister() {
    $this->drupalGet('/user/register');

    $test_user = [
      'email' => 'edge.functional.test@pronovix.com',
      'first_name' => 'Functional',
      'last_name' => 'Test',
      'username' => 'UserByAdmin',
    ];

    $formdata = [
      'mail' => $test_user['email'],
      'first_name[0][value]' => $test_user['first_name'],
      'last_name[0][value]' => $test_user['last_name'],
      'name' => $test_user['username'],
    ];
    $this->submitForm($formdata, 'Create new account');

    /** @var Developer $developer */
    $developer = $this->developerController->load($test_user['email']);

    $this->assertEquals($developer->getEmail(), $test_user['email']);
    $this->assertEquals($developer->getFirstName(), $test_user['first_name']);
    $this->assertEquals($developer->getLastName(), $test_user['last_name']);
    $this->assertEquals($developer->getUserName(), $test_user['username']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_INACTIVE);

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/user/2/edit');

    $formdata['status'] = '1';
    $this->submitForm($formdata, 'Save');

    $developer = $this->developerController->load($test_user['email']);

    $this->assertEquals($developer->getEmail(), $test_user['email']);
    $this->assertEquals($developer->getFirstName(), $test_user['first_name']);
    $this->assertEquals($developer->getLastName(), $test_user['last_name']);
    $this->assertEquals($developer->getUserName(), $test_user['username']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_ACTIVE);
  }

  /**
   * Create user by admin.
   *
   * Tests creating, editing and deleting developer entity
   * if the Drupal user registered by the admin.
   */
  public function testDeveloperRegisteredByAdmin() {
    // Create blocked user by the admin.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/people/create');

    $test_user = [
      'email' => 'edge.functional.test@pronovix.com',
      'first_name' => 'Functional',
      'last_name' => 'Test',
      'username' => 'UserByAdmin',
      'password' => user_password(),
      'status' => '0',
    ];

    $formdata = [
      'mail' => $test_user['email'],
      'first_name[0][value]' => $test_user['first_name'],
      'last_name[0][value]' => $test_user['last_name'],
      'name' => $test_user['username'],
      'pass[pass1]' => $test_user['password'],
      'pass[pass2]' => $test_user['password'],
      'status' => $test_user['status'],
    ];
    $this->submitForm($formdata, 'Create new account');

    /** @var Developer $developer */
    $developer = $this->developerController->load($test_user['email']);

    $this->assertEquals($developer->getEmail(), $test_user['email']);
    $this->assertEquals($developer->getFirstName(), $test_user['first_name']);
    $this->assertEquals($developer->getLastName(), $test_user['last_name']);
    $this->assertEquals($developer->getUserName(), $test_user['username']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_INACTIVE);

    // Unblock and edit the user's email, first name, last name by the admin.
    $this->drupalGet('/user/2/edit');
    $test_user['email'] = 'mod.edge.functional.test@pronovix.com';
    $test_user['first_name'] = '(mod) Functional';
    $test_user['last_name'] = '(mod) Test';
    $test_user['status'] = '1';

    $formdata['mail'] = $test_user['email'];
    $formdata['first_name[0][value]'] = $test_user['first_name'];
    $formdata['last_name[0][value]'] = $test_user['last_name'];
    $formdata['status'] = $test_user['status'];
    $this->submitForm($formdata, 'Save');

    $developer = $this->developerController->load($test_user['email']);

    $this->assertEquals($developer->getEmail(), $test_user['email']);
    $this->assertEquals($developer->getFirstName(), $test_user['first_name']);
    $this->assertEquals($developer->getLastName(), $test_user['last_name']);
    $this->assertEquals($developer->getUserName(), $test_user['username']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_ACTIVE);

    // Block the user's account on the people form.
    $this->drupalGet('/admin/people');
    $this->getSession()->getPage()->selectFieldOption('edit-action', 'user_block_user_action');
    $this->getSession()->getPage()->checkField('edit-user-bulk-form-0');
    $this->getSession()->getPage()->pressButton('edit-submit');

    $developer = $this->developerController->load($test_user['email']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_INACTIVE);

    // Block user on the cancel form using the user_cancel_block method.
    $this->drupalGet('/user/2/edit');
    $test_user['status'] = '1';
    $formdata = [
      'mail' => $test_user['email'],
      'first_name[0][value]' => $test_user['first_name'],
      'last_name[0][value]' => $test_user['last_name'],
      'name' => $test_user['username'],
      'pass[pass1]' => $test_user['password'],
      'pass[pass2]' => $test_user['password'],
      'status' => $test_user['status'],
    ];
    $this->submitForm($formdata, 'Save');

    $this->drupalGet('/user/2/cancel');
    $formdata = [
      'user_cancel_method' => 'user_cancel_block',
    ];
    $this->submitForm($formdata, 'Cancel account');

    $developer = $this->developerController->load($test_user['email']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_INACTIVE);

    // Block user on the cancel form using the user_cancel_reassign method.
    $this->drupalGet('/user/2/edit');
    $test_user['status'] = '1';
    $formdata = [
      'mail' => $test_user['email'],
      'first_name[0][value]' => $test_user['first_name'],
      'last_name[0][value]' => $test_user['last_name'],
      'name' => $test_user['username'],
      'pass[pass1]' => $test_user['password'],
      'pass[pass2]' => $test_user['password'],
      'status' => $test_user['status'],
    ];
    $this->submitForm($formdata, 'Save');

    $this->drupalGet('/user/2/cancel');
    $formdata = [
      'user_cancel_method' => 'user_cancel_block_unpublish',
    ];
    $this->submitForm($formdata, 'Cancel account');

    $developer = $this->developerController->load($test_user['email']);
    $this->assertEquals($developer->getStatus(), $developer::STATUS_INACTIVE);

    // Delete user by admin.
    $this->drupalGet('/user/2/cancel');
    $formdata = [
      'user_cancel_method' => 'user_cancel_delete',
    ];
    $this->submitForm($formdata, 'Cancel account');

    $this->setExpectedException(ClientErrorException::class);
    $this->developerController->load($test_user['email']);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown() {
    parent::tearDown();
    try {
      $this->developerController->delete('edge.functional.test@pronovix.com');
    }
    catch (\Exception $ex) {

    }
    try {
      $this->developerController->delete('mod.edge.functional.test@pronovix.com');
    }
    catch (\Exception $ex) {

    }
  }

}