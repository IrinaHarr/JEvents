<?php
/**
 * JEvents Component for Joomla! 3.x
 *
 * @version     $Id: Category.php 3542 2012-04-20 08:17:05Z geraintedwards $
 * @package     JEvents
 * @copyright   Copyright (C) 2008-2018 GWE Systems Ltd
 * @license     GNU/GPLv2, see http://www.gnu.org/licenses/gpl-2.0.html
 * @link        http://www.jevents.net
 */

// ensure this file is being included by a parent file
defined('_JEXEC') or die('Direct Access to this location is not allowed.');

class jevCategoryFilter extends jevFilter
{
	const filterType = "category";

	function __construct($tablename, $filterfield, $isstring = true)
	{

		$this->filterType = self::filterType;
		// setup for all required function and classes
		$file = JPATH_SITE . '/components/com_jevents/mod.defines.php';
		if (file_exists($file))
		{
			include_once($file);
		}
		$reg             = JevRegistry::getInstance("jevents");
		$this->datamodel = $reg->getReference("jevents.datamodel", false);
		if (!$this->datamodel)
		{
			$this->datamodel = new JEventsDataModel();
			$this->datamodel->setupComponentCatids();
		}

		$this->filterLabel     = JText::_('CATEGORY');
		$this->filterNullValue = "0";
		parent::__construct($tablename, "catid", true);

		$catid = $this->filter_value;
		// NO filtering of the list att all
		$this->allAccessibleCategories = $this->datamodel->accessibleCategoryList(null, $this->datamodel->mmcatids, $this->datamodel->mmcatidList);
		if ($this->filter_value == $this->filterNullValue || $this->filter_value == "")
		{
			$this->accessibleCategories = $this->allAccessibleCategories;
		}
		else
		{
			$this->accessibleCategories = $this->datamodel->accessibleCategoryList(null, array($catid), $catid);
		}
	}

	function _createFilter($prefix = "")
	{

		if (!$this->filterField) return "";
		if ($this->filter_value == $this->filterNullValue || $this->filter_value == "")
		{
			return "";
		}

		/*
		 * code to allow filter to force events to be in ALL selected categories
		 */
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);

		if ($this->filter_value == $this->filterNullValue || $this->filter_value == "")
		{
			$catidsIn = JRequest::getVar('catids', 'NONE');
			if ($catidsIn == "NONE" || $catidsIn == 0)
			{
				$catidsIn = JRequest::getVar('category_fv', 'NONE');
			}

			$separator = $params->get("catseparator", "|");
			$catids    = explode($separator, $catidsIn);

			//$catids=  $this->datamodel->catids;
			if (count($catids) && $params->get("multicategory", 0))
			{
				// Ths is 'Relational Division'
				$filter = " ev.ev_id in ( "
					. " SELECT catmaprd.evid "
					. " FROM #__jevents_catmap as catmaprd "
					. " WHERE catmaprd.catid IN(" . implode(",", $catids) . ") "
					. " AND catmaprd.catid IN(" . $this->accessibleCategories . ")"
					. " GROUP BY catmaprd.evid "
					. " HAVING COUNT(catmaprd.catid) = " . count($catids) . ")";

			}
			else
			{
				$filter = " ev.catid IN (" . $this->accessibleCategories . ")";
			}

			return $filter;

		}

		/*
		$sectionname = JEV_COM_COMPONENT;
		
		$db = JFactory::getDbo();
		$q_published = JFactory::getApplication()->isAdmin() ? "\n WHERE c.published >= 0" : "\n WHERE c.published = 1";
		$where = ' AND (c.id =' . $this->filter_value .' OR p.id =' . $this->filter_value .' OR gp.id =' . $this->filter_value .' OR ggp.id =' . $this->filter_value .')';		
		$query = "SELECT c.id"
			. "\n FROM #__categories AS c"
			. ' LEFT JOIN #__categories AS p ON p.id=c.parent_id' 
			. ' LEFT JOIN #__categories AS gp ON gp.id=p.parent_id ' 
			. ' LEFT JOIN #__categories AS ggp ON ggp.id=gp.parent_id ' 
			. $q_published
			. "\n AND c.section = '".$sectionname."'"
			. "\n " . $where;
			;
			
			$db->setQuery($query);
			$catlist =  $db->loadColumn();
			array_unshift($catlist,-1);
		
		$filter = " ev.catid IN (".implode(",",$catlist).")";
		*/
		$filter = " ev.catid IN (" . $this->accessibleCategories . ")";

		$user = JFactory::getUser();
		if ($params->get("multicategory", 0))
		{
			// access will already be checked
			$filter             = " catmap.catid IN(" . $this->accessibleCategories . ")";
			$this->needsgroupby = true;
		}

		return $filter;
	}

	// Ths join to catmap tables should already be done!
	/*
	function _createJoinFilter($prefix=""){
		if (!$this->filterField ) return "";
		if (intval($this->filter_value)==$this->filterNullValue) return "";

		$filter = "";
		$params = JComponentHelper::getParams(JEV_COM_COMPONENT);
		if ($params->get("multicategory",0)){
			$filter .= "\n #__jevents_catmap as catmap ON catmap.evid = rpt.eventid";
			$filter .=  "\n LEFT JOIN #__categories AS catmapcat ON catmap.catid = catmapcat.id";
		}
		return $filter;	
	}
	*/

	function _createfilterHTML()
	{

		return $this->createfilterHTML(true);
	}

	function createfilterHTML($allowAutoSubmit = true)
	{

		if (!$this->filterField) return "";

		$filter_value = $this->filter_value;
		// if catids come from the URL then use this if filter is blank
		if ($filter_value == $this->filterNullValue || $filter_value == "")
		{
			if (JRequest::getInt("catids", 0) > 0)
			{
				$filter_value = JRequest::getInt("catids", 0);
			}
		}

		$filterList          = array();
		$filterList["title"] = "<label class='evcategory_label' for='" . $this->filterType . "_fv'>" . JText::_("SELECT_CATEGORY") . "</label>";

		if ($allowAutoSubmit)
		{
			$filterList["html"] = JEventsHTML::buildCategorySelect($filter_value, 'onchange="if (document.getElementById(\'catidsfv\')) document.getElementById(\'catidsfv\').value=this.value;submit(this.form)" ', $this->allAccessibleCategories, false, false, 0, $this->filterType . '_fv');
		}
		else
		{
			$filterList["html"] = JEventsHTML::buildCategorySelect($filter_value, 'onchange="if (document.getElementById(\'catidsfv\')) document.getElementById(\'catidsfv\').value=this.value;" ', $this->allAccessibleCategories, false, false, 0, $this->filterType . '_fv');
		}

		// if there is only one category then do not show the filter
		if (strpos($filterList["html"], "<select") === false)
		{
			return "";
		}

		// try/catch  incase this is called without a filter module!
		$script = <<<SCRIPT
try {
	JeventsFilters.filters.push(
		{
			id:'{$this->filterType}_fv',
			value:0
		}
	);
}
catch (e) {}
function reset{$this->filterType}_fvs(){
	if (document.getElementById('catidsfv')) {
		document.getElementById('catidsfv').value=0;
	}
	jQuery('#{$this->filterType}_fv option').each(function(idx, item){
		item.selected=(item.value==0)?true:false;
	})
};
try {
	JeventsFilters.filters.push(
		{
			action:'reset{$this->filterType}_fvs()',
			id:'{$this->filterType}_fv',
			value:{$this->filterNullValue}
		}
	);
}
catch (e) {}
SCRIPT;

		$document = JFactory::getDocument();
		$document->addScriptDeclaration($script);

		return $filterList;

	}

}
