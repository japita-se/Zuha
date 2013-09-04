<?php
class WebpageMenu extends WebpagesAppModel {
    
	public $name = 'WebpageMenu';
    
	public $displayField = 'name';
    
	public $actsAs = array('Tree');
    
	public $validate = array();
    
    public $order = 'lft';


/**
 * has many items, and we use this model instead of the MenuItem model for a performance boost 
 */
	public $hasMany = array(
		'WebpageMenuItem' => array(
			'className' => 'Webpages.WebpageMenuItem',
			'foreignKey' => 'menu_id',
			'dependent' => true,
			'conditions' => '',
			'fields' => '',
			'order' => 'WebpageMenuItem.lft',
			'limit' => '',
			'offset' => '',
			'exclusive' => '',
			'finderQuery' => '',
			'counterQuery' => ''
		)
	);
	
	
	public function types() {
		return array(
			'superfish' => 'Superfish', 
			'superfish sf-horizontal' => 'Superfish Horizontal', 
			'superfish sf-vertical' => 'Superfish Verticial'
			);
	}
    
    public function beforeSave($options = array()){
        $this->data = $this->_cleanData($this->data);
        return true;
    }
	
/**
 * After find callback
 * Set user_role_id to the guest user_role_id after find (instead of during save or beforeSave)
 * because we don't want to force a user_role_id to be set.  We'd rather that null means it is
 * a menu available to, but not necessarily the one returned to everyone.
 * 
 */
	public function afterFind($results) {
		for ($i = 0; $i < count($results); $i++) {
			if ($results[$i][$this->alias]['user_role_id'] === null) {
				$results[$i][$this->alias]['user_role_id'] = __SYSTEM_GUESTS_USER_ROLE_ID;
			}
		}
		return $results;
	}
    
    public function _cleanData($data = null) {
        if (empty($data['WebpageMenu']['code']) && !empty($data['WebpageMenu']['name'])) {
            $data['WebpageMenu']['code'] = ZuhaInflector::asciify($data['WebpageMenu']['name']);
        }
        return $data;
    }

}