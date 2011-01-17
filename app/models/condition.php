<?php
/**
 * Condition Model
 *
 * This model handles the data for conditions.  Conditions are met when the model and sub conditions are a match.  When the conditions are met, the bind_model field will trigger an event in the dynamically binded model called "triggered".  Using the triggered method in a model, will allow you to create any number of actions from a single action which meets the condition. 
 *
 * PHP versions 5
 *
 * Zuha(tm) : Business Management Applications (http://zuha.com)
 * Copyright 2009-2010, Zuha Foundation Inc. (http://zuhafoundation.org)
 *
 * Licensed under GPL v3 License
 * Must retain the above copyright notice and release modifications publicly.
 *
 * @copyright     Copyright 2009-2010, Zuha Foundation Inc. (http://zuha.com)
 * @link          http://zuha.com Zuha� Project
 * @package       zuha
 * @subpackage    zuha.app.models
 * @since         Zuha(tm) v 0.0.1
 * @license       GPL v3 License (http://www.gnu.org/licenses/gpl.html) and Future Versions
 */
class Condition extends AppModel {

	var $name = 'Condition';
	var $validate = array(
		'name' => array('notempty'),
		'bind_model' => array('notempty'),
		'creator_id' => array('numeric'),
		'modifier_id' => array('numeric')
	);
	
	//The Associations below have been created with all possible keys, those that are not needed can be removed
	var $belongsTo = array(
		'Creator' => array(
			'className' => 'Users.User',
			'foreignKey' => 'creator_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
		'Modifier' => array(
			'className' => 'Users.User',
			'foreignKey' => 'modifier_id',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		)
	);
	
	
/** 
 * Check the conditions table to see if the record being created, read, updated, or deleted matches any conditions. And if it is then will fire a sub condition check, and finally fire the bindModel if condtions are met.
 *
 * @param {type} 		Valid values are, "is_create", "is_read", "is_update", "is_delete".
 * @param {lookups}		An array with either model, or plugin, controller, action, values indexes defined.
 * @param {data}		The data that we're checking against and saving if a match is made.
 * @return {array}		returns an array of ids and the models to bind those to, when the conditions are met.
 */
	function checkAndFire($type, $lookups, $data) {
		
		# first check a condtion for plugin, controller, model, action, extra values and type matches
		if ($conditions = $this->checkConditions($type, $lookups)) {
			# if those are matched traverse this data with the sub condtion to see if its a 100% match
			$i = 0;
			foreach ($conditions as $condition) {
				if ($this->_checkSubConditions($condition, $data)) {
					$triggers[$i]['id'] = $condition['Condition']['id'];
					$triggers[$i]['model'] = $condition['Condition']['bind_model'];
				}
				$i++;
			}
			
			if (!empty($triggers)) {
				#if it is then fire all of the actions that are a 100% match
				foreach ($triggers as $trigger) {
					# fire the triggered action in the model condition is binded to
					$this->fireAction($trigger['id'], $trigger['model'], $data);
				}
			}
		}
	}
	
	
	
/** 
 * This function does a find to see if the any conditions exist which match the current create, read, update or delete action.
 *
 * @param {type} 		Four valid values, 'is_create', 'is_read', 'is_update', 'is_delete'.
 * @param {lookups}		An array with possible indexes of model, plugin, controller action, or extra_values.
 * @return {array}		Returns all an array including of matched conditions information
 */	
	function checkConditions($type, $lookups) {
		$fields = $this->_conditionConditions($lookups);
		$conditions = $this->find('all', array(
			'conditions' => array(
				$fields,
				'Condition.'.$type => 1,
				),
			'fields' => array(
				'id',
				'bind_model',
				'condition',
				),
			));
		return $conditions;
	}
	
	
	
/** 
 * This does a double check on the conditions if the first conditions were matched.  For example, you may save a project, and if the assignee_id is 3, have a different condition OR no condition met.  This function allows sub conditions to exist.
 *
 * @param {condition} 	A string of conditions in this format : Model.field.operator.value,Model.field.operator.value (An example : ContactPerson.email.!=.null,ContactPerson.contact_id.>.0)
 * @param {data}		This is all of the form submitted data, to read and see if the sub condition was met.
 * @return {bool}		Returns true if the sub conditions were met or don't exist, and false if they were not met.
 * @todo 				Its pretty urgent that we turn on sub condition checks and there is a precursor written already in app_model.  We cannot publish live until this is done. 
 */	
	function _checkSubConditions($condition, $data) {
		if (!empty($condition['Condition']['condtion'])) {
			# check the sub condition code goes here. 
			# if the sub condition does match return true if not return false.
		} else {
			return true;
		}
	}
	
	
	
	
/** 
 * There are four possible condition types.  Create, Read, Update, Delete (CRUD).  If it is the read type then we would use the controller information that is currently being viewed to trigger the event.  Otherwise, we would use the model that is being effected during the create, update, or delete event.  This function returns the conditions necessary to match against.
 *
 * @param {lookups} 	An array with possible indexes of model, plugin, controller action, or extra_values.
 * @return {array}		Returns an array of conditions.
 */	
	function _conditionConditions($lookups) {
		$model =(!empty($lookups['model']) ? $lookups['model'] : null);
		$plugin =(!empty($lookups['plugin']) ? $lookups['plugin'] : null);
		$controller =(!empty($lookups['controller']) ? $lookups['controller'] : null);
		$action =(!empty($lookups['action']) ? $lookups['action'] : null);
		$extraValues =(!empty($lookups['extra_values']) ? $lookups['extra_values'] : null);
		
		if (!empty($model)) {
			$condition = array('Condition.model' => $model);
		} else {
			$condition = array(
				'Condition.plugin' => $plugin,
				'Condition.controller' => $controller,
				'Condition.action' => $action,
				'Condition.extra_values' => $extraValues,
				);
		}
		return $condition;
	}
	
	
/** 
 * The final step in a condition being matched.  It calls the model using the bind_model field, and fires an action called "triggered".  This is where the initial event is transferred to an outside plugin or model.  If the triggered event is going to be database driven or a future event, then model you are binding should belongTo Condition, and have a "condition_id" in the table to properly match the action taking place to the condition triggering it.  Otherwise it is possible to just have a triggered action which does all of the work direction from the triggered method in that model. 
 *
 * @param {id} 			The id of the condition that was met.
 * @param {model}		The model which belongsTo condition. If a plugin, this should be in Plugin.Model format.
 * @param {data}		An array of data that was originally entered into the form. Use it for creating new actions from the single action that triggered this. 
 * @return {}			Does not return anything. This is a silent operation, and gives no feedback unless a fatal error occurs.
 */
	function fireAction($id, $model, $data) {
		# import the bind model and fire a model action called triggered.
		App::import('Model', $model);
		$thisModel = explode('.', $model);
		
		if (!empty($thisModel[1])) {
			# this supports a plugin
			$this->$thisModel[1] = new $thisModel[1];
			$this->$thisModel[1]->triggered($id, $data);
		} else {
			# this is if it is not a plugin
			$this->$thisModel[0] = new $thisModel[0];
			$this->$thisModel[0]->triggered($id, $data);
		}
	}

}
?>