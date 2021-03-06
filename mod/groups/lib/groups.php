<?php
/**
 * Groups function library
 */

/**
 * Registers the buttons for title area of the group profile page
 *
 * @param ElggGroup $group group to register for
 * @return void
 */
function groups_register_profile_buttons($group) {

	$params = [
		'entity' => $group,
	];

	$items = elgg_trigger_plugin_hook('profile_buttons', 'group', $params, []);
	
	if (!empty($items)) {
		foreach ($items as $item) {
			elgg_register_menu_item('title', $item);
		}
	}
}

/**
 * Prepares variables for the group edit form view.
 *
 * @param mixed $group ElggGroup or null. If a group, uses values from the group.
 * @return array
 */
function groups_prepare_form_vars($group = null) {
	$values = [
		'name' => '',
		'membership' => ACCESS_PUBLIC,
		'vis' => ACCESS_PUBLIC,
		'guid' => null,
		'entity' => null,
		'owner_guid' => elgg_get_logged_in_user_guid(),
		'content_access_mode' => ElggGroup::CONTENT_ACCESS_MODE_UNRESTRICTED
	];

	// handle customizable profile fields
	$fields = elgg_get_config('group');

	if ($fields) {
		foreach ($fields as $name => $type) {
			$values[$name] = '';
		}
	}

	// get current group settings
	if ($group) {
		foreach (array_keys($values) as $field) {
			if (isset($group->$field)) {
				$values[$field] = $group->$field;
			}
		}

		if ($group->access_id != ACCESS_PUBLIC && $group->access_id != ACCESS_LOGGED_IN) {
			// group only access - this is done to handle access not created when group is created
			$values['vis'] = ACCESS_PRIVATE;
		} else {
			$values['vis'] = $group->access_id;
		}

		// The content_access_mode was introduced in 1.9. This method must be
		// used for backwards compatibility with groups created before 1.9.
		$values['content_access_mode'] = $group->getContentAccessMode();

		$values['entity'] = $group;
	}
	
	// handle tool options
	$entity = ($group instanceof \ElggGroup) ? $group : null;
	$tools = elgg_get_group_tool_options($entity);
	foreach ($tools as $group_option) {
		$option_name = $group_option->name . "_enable";
		
		$enabled = $group_option->default_on;
		if ($entity) {
			$enabled = $entity->isToolEnabled($group_option->name);
		}
		$values[$option_name] = $enabled ? 'yes' : 'no';
	}
	
	// get any sticky form settings
	if (elgg_is_sticky_form('groups')) {
		$sticky_values = elgg_get_sticky_values('groups');
		foreach ($sticky_values as $key => $value) {
			$values[$key] = $value;
		}
	}

	elgg_clear_sticky_form('groups');

	return $values;
}
