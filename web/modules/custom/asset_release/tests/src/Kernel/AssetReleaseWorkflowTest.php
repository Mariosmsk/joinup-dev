<?php

namespace Drupal\Tests\asset_release\Kernel;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Entity\OgRole;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfInterface;
use Drupal\Tests\joinup_core\JoinupWorkflowTestBase;

/**
 * Tests the support of saving various encoded stings in the triple store.
 *
 * @group asset_release
 */
class AssetReleaseWorkflowTest extends JoinupWorkflowTestBase {

  /**
   * A user with the authenticated role.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userAuthenticated;

  /**
   * A user with the moderator role.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userModerator;

  /**
   * A user to be used as a solution facilitator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgFacilitator;

  /**
   * A user to be used as a solution administrator.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $userOgAdministrator;

  /**
   * The solution parent entity.
   *
   * @var \Drupal\rdf_entity\RdfInterface
   */
  protected $solutionGroup;

  /**
   * The solution facilitator role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $roleFacilitator;

  /**
   * The solution administrator role.
   *
   * @var \Drupal\og\Entity\OgRole
   */
  protected $roleAdministrator;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->userAuthenticated = $this->createUserWithRoles();
    $this->userModerator = $this->createUserWithRoles(['moderator']);
    $this->userOgFacilitator = $this->createUserWithRoles();
    $this->userOgAdministrator = $this->createUserWithRoles();

    $this->roleFacilitator = OgRole::getRole('rdf_entity', 'solution', 'facilitator');
    $this->roleAdministrator = OgRole::getRole('rdf_entity', 'solution', 'administrator');
  }

  /**
   * Creates a user with roles.
   *
   * @param array $roles
   *    An array of roles to initialize the user with.
   *
   * @return \Drupal\Core\Session\AccountInterface
   *    The created user object.
   */
  public function createUserWithRoles($roles = []) {
    $user = $this->createUser();
    foreach ($roles as $role) {
      $user->addRole($role);
    }
    $user->save();

    return $user;
  }

  /**
   * Tests the CRUD operations for the asset release entities.
   */
  public function testCrudAccess() {
    // Test create access.
    foreach ($this->createAccessProvider() as $parent_state => $test_data_arrays) {
      $parent = $this->createDefaultParent($parent_state);
      $content = Rdf::create([
        'rid' => 'asset_release',
        'field_isr_is_version_of' => $parent->id(),
      ]);
      foreach ($test_data_arrays as $test_data) {
        $operation = 'create';
        $user_var = $test_data[0];
        $expected_result = $test_data[1];

        $access = $this->ogAccess->userAccessEntity('create', $content, $this->{$user_var})->isAllowed();
        $result = $expected_result ? t('have') : t('not have');
        $message = "User {$user_var} should {$result} {$operation} access for bundle 'asset_release'.";
        $this->assertEquals($expected_result, $access, $message);
      }
    }

    // Test view, update, delete access.
    foreach ($this->rudAccessProvider() as $parent_state => $content_data) {
      $parent = $this->createDefaultParent($parent_state);

      foreach ($content_data as $content_state => $test_data_arrays) {
        $content = Rdf::create([
          'rid' => 'asset_release',
          'label' => $this->randomMachineName(),
          'field_isr_state' => $content_state,
          'field_isr_is_version_of' => $parent->id(),
        ]);
        $content->save();

        foreach ($test_data_arrays as $test_data_array) {
          $operation = $test_data_array[0];
          $user_var = $test_data_array[1];
          $expected_result = $test_data_array[2];

          $access = $this->entityAccess->access($content, $operation, $this->{$user_var});
          $result = $expected_result ? t('have') : t('not have');
          $message = "User {$user_var} should {$result} {$operation} access for entity {$content->label()} ({$content_state}) with the parent entity in {$parent_state} state.";
          $this->assertEquals($expected_result, $access, $message);
        }
      }
    }
  }

  /**
   * Provides data for create access check.
   */
  public function createAccessProvider() {
    return [
      // Unpublished parent.
      'draft' => [
        ['userAuthenticated', FALSE],
        ['userModerator', TRUE],
        ['userOgFacilitator', TRUE],
        ['userOgAdministrator', FALSE],
      ],
      // Published parent.
      'validated' => [
        ['userAuthenticated', FALSE],
        ['userModerator', TRUE],
        ['userOgFacilitator', TRUE],
        ['userOgAdministrator', FALSE],
      ],
    ];
  }

  /**
   * Generates a solution entity and initializes default memberships.
   *
   * @param string $state
   *    The state of the entity.
   *
   * @return \Drupal\rdf_entity\RdfInterface
   *    The created solution entity.
   */
  public function createDefaultParent($state) {
    $parent = Rdf::create([
      'rid' => 'solution',
      'field_is_state' => $state,
      'label' => $this->randomMachineName(),
    ]);
    $parent->save();
    $this->assertInstanceOf(RdfInterface::class, $parent, "The solution group was created.");
    $this->createOgMembership($parent, $this->userOgFacilitator, [$this->roleFacilitator]);
    $this->createOgMembership($parent, $this->userOgAdministrator, [$this->roleAdministrator]);
    return $parent;
  }

  /**
   * Creates and asserts an Og membership.
   *
   * @param \Drupal\Core\Entity\EntityInterface $group
   *    The Og group.
   * @param \Drupal\Core\Session\AccountInterface $user
   *    The user this membership refers to.
   * @param array $roles
   *    An array of role objects.
   */
  public function createOgMembership(EntityInterface $group, AccountInterface $user, array $roles = []) {
    $membership = $this->ogMembershipManager->createMembership($group, $user)->setRoles($roles);
    $membership->save();
    $loaded = $this->ogMembershipManager->getMembership($group, $user);
    $this->assertInstanceOf(OgMembership::class, $loaded, t("A membership was successfully created."));
  }

  /**
   * Provides data for access check.
   */
  public function rudAccessProvider() {
    return [
      // Unpublished parent.
      'draft' => [
        'draft' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAuthenticated', TRUE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', TRUE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'in_assessment' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
      ],
      // Published parent.
      'validated' => [
        'draft' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'validated' => [
          ['view', 'userAuthenticated', TRUE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', TRUE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
        'in_assessment' => [
          ['view', 'userAuthenticated', FALSE],
          ['view', 'userModerator', TRUE],
          ['view', 'userOgFacilitator', TRUE],
          ['view', 'userOgAdministrator', FALSE],
          ['update', 'userAuthenticated', FALSE],
          ['update', 'userModerator', TRUE],
          ['update', 'userOgFacilitator', TRUE],
          ['update', 'userOgAdministrator', FALSE],
          ['delete', 'userAuthenticated', FALSE],
          ['delete', 'userModerator', TRUE],
          ['delete', 'userOgFacilitator', FALSE],
          ['delete', 'userOgAdministrator', FALSE],
        ],
      ],
    ];
  }

}
