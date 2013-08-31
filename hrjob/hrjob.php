<?php

require_once __DIR__ . DIRECTORY_SEPARATOR . 'hrjob.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function hrjob_civicrm_config(&$config) {
  _hrjob_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function hrjob_civicrm_xmlMenu(&$files) {
  _hrjob_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function hrjob_civicrm_install() {
  return _hrjob_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function hrjob_civicrm_uninstall() {
  return _hrjob_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function hrjob_civicrm_enable() {
  return _hrjob_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function hrjob_civicrm_disable() {
  return _hrjob_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function hrjob_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _hrjob_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function hrjob_civicrm_managed(&$entities) {
  return _hrjob_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_tabs
 */
function hrjob_civicrm_tabs(&$tabs, $contactID) {
  $contactType = CRM_Core_DAO::getFieldValue('CRM_Contact_DAO_Contact', $contactID, 'contact_type', 'id');
  if ($contactType != 'Individual') {
    return;
  }

  CRM_HRJob_Page_JobsTab::registerScripts();
  $tab = array(
    'id' => 'hrjob',
    'url' => CRM_Utils_System::url('civicrm/contact/view/hrjob', array(
      'cid' => $contactID,
      'snippet' => 1,
    )),
    'title' => ts('Jobs'),
    'weight' => 10,
    'count' => CRM_HRJob_BAO_HRJob::getRecordCount(array(
      'contact_id' => $contactID
    )),
  );
  $tabs[] = $tab;
  CRM_Core_Resources::singleton()->addScriptFile('org.civicrm.hrjob', 'js/hrjob.js');
  $selectedChild = CRM_Utils_Request::retrieve('selectedChild', 'String');
  CRM_Core_Resources::singleton()->addSetting(array(
    'tabs' => array(
      'selectedChild' => $selectedChild,
    ),
  ));
}

/**
 * Implementation of hook_civicrm_queryObjects
 */
function hrjob_civicrm_queryObjects(&$queryObjects, $type) {
  if ($type == 'Contact') {
    $queryObjects[] = new CRM_HRJob_BAO_Query();
  } else if ($type == 'Report') {
    $queryObjects[] = new CRM_HRJob_BAO_ReportHook();
  }
}

/**
 * Implementation of hook_civicrm_entityTypes
 */
function hrjob_civicrm_entityTypes(&$entityTypes) {
  $entityTypes[] = array(
    'name' => 'HRJob',
    'class' => 'CRM_HRJob_DAO_HRJob',
    'table' => 'civicrm_hrjob',
  );
  $entityTypes[] = array(
    'name' => 'HRJobPay',
    'class' => 'CRM_HRJob_DAO_HRJobPay',
    'table' => 'civicrm_hrjob_pay',
  );
  $entityTypes[] = array(
    'name' => 'HRJobHealth',
    'class' => 'CRM_HRJob_DAO_HRJobHealth',
    'table' => 'civicrm_hrjob_health',
  );
  $entityTypes[] = array(
    'name' => 'HRJobHour',
    'class' => 'CRM_HRJob_DAO_HRJobHour',
    'table' => 'civicrm_hrjob_hour',
  );
  $entityTypes[] = array(
    'name' => 'HRJobLeave',
    'class' => 'CRM_HRJob_DAO_HRJobLeave',
    'table' => 'civicrm_hrjob_leave',
  );
  $entityTypes[] = array(
    'name' => 'HRJobPension',
    'class' => 'CRM_HRJob_DAO_HRJobPension',
    'table' => 'civicrm_hrjob_pension',
  );
  $entityTypes[] = array(
    'name' => 'HRJobRole',
    'class' => 'CRM_HRJob_DAO_HRJobRole',
    'table' => 'civicrm_hrjob_role',
  );
}

function hrjob_civicrm_triggerInfo(&$info, $tableName) {
  $info[] = array(
    'table' => array('civicrm_hrjob'),
    'when' => 'after',
    'event' => array('insert', 'update'),
    'sql' => "
      IF NEW.contact_id IS NOT NULL THEN
        SET @hrjob_joindate = (SELECT min(period_start_date) FROM civicrm_hrjob WHERE contact_id = NEW.contact_id);
        SET @hrjob_termdate = (SELECT max(period_end_date) FROM civicrm_hrjob WHERE contact_id = NEW.contact_id);
        INSERT INTO civicrm_value_job_summary_10 (entity_id,initial_join_date_56,final_termination_date_57)
          VALUES (NEW.contact_id, @hrjob_joindate, @hrjob_termdate)
          ON DUPLICATE KEY UPDATE
          initial_join_date_56 = @hrjob_joindate,
          final_termination_date_57 = @hrjob_termdate;
      END IF;
    ",
  );
  $info[] = array(
    'table' => array('civicrm_hrjob'),
    'when' => 'before',
    'event' => array('update', 'delete'),
    'sql' => "
      IF OLD.contact_id IS NOT NULL THEN
        SET @hrjob_joindate = (SELECT min(period_start_date) FROM civicrm_hrjob WHERE contact_id = OLD.contact_id);
        SET @hrjob_termdate = (SELECT max(period_end_date) FROM civicrm_hrjob WHERE contact_id = OLD.contact_id);
        INSERT INTO civicrm_value_job_summary_10 (entity_id,initial_join_date_56,final_termination_date_57)
          VALUES (OLD.contact_id, @hrjob_joindate, @hrjob_termdate)
          ON DUPLICATE KEY UPDATE
          initial_join_date_56 = @hrjob_joindate,
          final_termination_date_57 = @hrjob_termdate;
      END IF;
    ",
  );
}

/**
 * @return array list fields keyed by stable name; each field has:
 *   - id: int
 *   - name: string
 *   - column_name: string
 *   - field: string, eg "custom_123"
 */
function hrjob_getSummaryFields($fresh = FALSE) {
  static $cache = NULL;
  if ($cache === NULL || $fresh) {
    $sql =
      "SELECT ccf.id, ccf.name, ccf.column_name, concat('custom_', ccf.id) as field
      FROM civicrm_custom_group ccg
      INNER JOIN civicrm_custom_field ccf ON ccf.custom_group_id = ccg.id
      WHERE ccg.name = 'HRJob_Summary'
    ";
    $dao = CRM_Core_DAO::executeQuery($sql);
    $cache = array();
    while ($dao->fetch()) {
      $cache[$dao->name] = $dao->toArray();
    }
  }
  return $cache;
}

/**
 * Helper function to load data into DB between iterations of the unit-test
 */
function _hrjob_phpunit_populateDB() {
  $import = new CRM_Utils_Migrate_Import();
  $import->run(
    CRM_Extension_System::singleton()->getMapper()->keyToBasePath('org.civicrm.hrjob')
      . '/xml/option_group_install.xml'
  );
  $import->run(
    CRM_Extension_System::singleton()->getMapper()->keyToBasePath('org.civicrm.hrjob')
      . '/xml/job_summary_install.xml'
  );
}