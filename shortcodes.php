<?php
/**
 * @copyright	Copyright (c) 2015 system. All rights reserved.
 * @license		http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

// no direct access
defined('_JEXEC') or die;

jimport('joomla.plugin.plugin');

/**
 * system - ShortCodes Plugin
 *
 * @package		Joomla.Plugin
 * @subpakage	system.ShortCodes
 */
class plgsystemShortCodes extends JPlugin {

	/**
	 * Constructor.
	 *
	 * @param 	$subject
	 * @param	array $config
	 */
	function __construct(&$subject, $config = array()) {
		// call parent constructor
		if (file_exists(JPATH_ADMINISTRATOR.'/components/com_zoo/config.php')) {
			require_once(JPATH_ADMINISTRATOR.'/components/com_zoo/config.php');
		}
		parent::__construct($subject, $config);
	}
	private function isAjax(){
		return isset($_SERVER['HTTP_X_REQUESTED_WITH']) and $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
	}
	public function onAfterRender() {
		$application = JFactory::getApplication();
		if($application->isAdmin() or $this->isAjax()) return false;
		$body = implode('', JResponse::getBody(true));
		$body = preg_replace_callback('#\[cols\](.*)\[\/cols\]#uUis', function ($tabs) {
			preg_match_all('#\[col\](.*)\[/col\]#Uis', $tabs[1], $lst);
			if (in_array(count($lst[0]), array(1,2,3,4,6))) {
				$tabs[1] = preg_replace_callback('#\[col\](.*)\[/col\]#Uis', function ($tab) use (&$lst){
					return '<div class="sm-col-'.(12/count($lst[0])).'">'.$tab[1].'</div>';
				}, $tabs[1]);
				return '<div class="sm-row">'.$tabs[1].'</div>';
			} else {
				$tabs[1] = preg_replace_callback('#\[col\](.*)\[/col\]#Uis', function ($tab) use (&$lst){
					return '<div class="sm-col-1" style="width:'.(100/count($lst[0])).'%;">'.$tab[1].'</div>';
				}, $tabs[1]);
				return '<div class="sm-row">'.$tabs[1].'</div>';
			}
		}, $body); 
		$body = preg_replace_callback('#\[tabs\](.*)\[\/tabs\]#uUis', function ($tabs) {
			$buttons = array();
			$data = array();
			$tabs[1] = preg_replace_callback('#\[tab[\s]+([^\]]+)\](.*)\[/tab\]#Uis', function ($tab) use (&$buttons){
				$buttons['<a href="javascript:void(0)" class="'.(count($buttons)?:'active').' sm-tabs-buttons-button" rel="descr'.count($buttons).'">'.$tab[1].'</a>'] = '<div class="sm-tabs-layers-layer '.(count($buttons)?:'active').'"  id="descr'.count($buttons).'">'.$tab[2].'</div>';
			}, $tabs[1]);
			return '<div class="sm-col-12 sm-tabs"> 
                    <div class="sm-tabs-buttons">'.implode('', array_keys($buttons)).'</div>
				<div class="sm-tabs-layers">'.implode('', array_values($buttons)).'</div>
			</div>';
		}, $body); 
		$body = preg_replace_callback('#\[alert\](.*)\[\/alert\]#uUis', function ($tabs) {
			return '<div class="alert">'.$tabs[1].'</div>';
		}, $body);
		$params = $this->params;
		$body = preg_replace_callback('#\[gallery[\s]+([0-9]+)\]#uUis', function ($gallery) use ($params){
			if (!class_exists('App')) {
				return $gallery[0];
			}
			$zoo = App::getInstance('zoo');
			$zoo->system->language->load('com_zoo');
			if (!$application = $zoo->table->application->get($params->get('application', 0))) {
				return $gallery[0];
			}
			$categories = $application->getCategoryTree(true, null, (bool) $params->get('add_count', false));
			$category = $categories[(int)$gallery[1]?:$params->get('category', 0)];
			if ($category) {
				$renderer = $zoo->renderer->create('item')->addPath(array($zoo->path->path('component.site:'), $application->getTemplate()->getPath()));
				$out = '<div class="block row photos">';
				foreach ($category->getItems() as $item) {
					$out.=$renderer->render('item.teaser', array('item'=>$item));
				}
				$out.='</div>';
				return $out;
			} else {
				return $gallery[0];
			}
		}, $body);

		JResponse::setBody($body);
	}
}