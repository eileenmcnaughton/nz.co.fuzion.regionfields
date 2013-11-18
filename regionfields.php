<?php

require_once 'regionfields.civix.php';

/**
 * Implementation of hook_civicrm_config
 */
function regionfields_civicrm_config(&$config) {
  _regionfields_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_xmlMenu
 *
 * @param $files array(string)
 */
function regionfields_civicrm_xmlMenu(&$files) {
  _regionfields_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_install
 */
function regionfields_civicrm_install() {
  return _regionfields_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall
 */
function regionfields_civicrm_uninstall() {
  return _regionfields_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_enable
 */
function regionfields_civicrm_enable() {
  return _regionfields_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function regionfields_civicrm_disable() {
  return _regionfields_civix_civicrm_disable();
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
function regionfields_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _regionfields_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 */
function regionfields_civicrm_managed(&$entities) {
  return _regionfields_civix_civicrm_managed($entities);
}

/**
 * hook_civicrm_triggerInfo()
 *
 * Add trigger to update custom region field based on postcode (using a lookup table)
 *
 * Note that we have hard-coded a prioritisation of location types into this (since it's customer specific code
 * and unlikely to change)
 *
 * @param array $info (reference) array of triggers to be created
 * @param string $tableName - not sure how this bit works
 *
 **/

function regionfields_civicrm_triggerInfo(&$info, $tableName) {
  $table_name = 'civicrm_value_region_13';
  $customFieldID = 45;
  $columnName = 'region_45';
  $sourceTable = 'civicrm_address';
  $locationPriorityOrder = '1, 3, 5, 2, 4, 6'; // hard coded prioritisation of addresses
  $zipTable = 'CANYRegion';
  if(civicrm_api3('custom_field', 'getcount', array('id' => $customFieldID, 'column_name' => 'region_45', 'is_active' => 1)) == 0) {
    return;
  }

  $sql = "
    REPLACE INTO `$table_name` (entity_id, $columnName)
    SELECT  * FROM (
      SELECT contact_id, b.region
      FROM
      civicrm_address a INNER JOIN $zipTable b ON a.postal_code = b.zip
      WHERE a.contact_id = NEW.contact_id
      ORDER BY FIELD(location_type_id, $locationPriorityOrder )
    ) as regionlist
    GROUP BY contact_id;
  ";
  $sql_field_parts = array();

  $info[] = array(
      'table' => $sourceTable,
      'when' => 'AFTER',
      'event' => 'INSERT',
      'sql' => $sql,
  );
  $info[] = array(
      'table' => $sourceTable,
      'when' => 'AFTER',
      'event' => 'UPDATE',
      'sql' => $sql,
  );
  // For delete, we reference OLD.contact_id instead of NEW.contact_id
  $sql = str_replace('NEW.contact_id', 'OLD.contact_id', $sql);
  $info[] = array(
      'table' => $sourceTable,
      'when' => 'AFTER',
      'event' => 'DELETE',
      'sql' => $sql,
  );
}
