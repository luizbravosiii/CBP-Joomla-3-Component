<?php
/*----------------------------------------------------------------------------------|  www.giz.de  |----/
	Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb 
/-------------------------------------------------------------------------------------------------------/

	@version		3.4.1
	@build			14th May, 2016
	@created		15th June, 2012
	@package		Cost Benefit Projection
	@subpackage		interventions.php
	@author			Llewellyn van der Merwe <http://www.vdm.io>	
	@owner			Deutsche Gesellschaft für International Zusammenarbeit (GIZ) Gmb
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	
/-------------------------------------------------------------------------------------------------------/
	Cost Benefit Projection Tool.
/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

/**
 * Interventions Form Field class for the Costbenefitprojection component
 */
class JFormFieldInterventions extends JFormFieldList
{
	/**
	 * The interventions field type.
	 *
	 * @var		string
	 */
	public $type = 'interventions'; 
	/**
	 * Override to add new button
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.2
	 */
	protected function getInput()
	{
		// see if we should add buttons
		$setButton = $this->getAttribute('button');
		// get html
		$html = parent::getInput();
		// if true set button
		if ($setButton === 'true')
		{
			$button = array();
			$script = array();
			$buttonName = $this->getAttribute('name');
			// get the input from url
			$jinput = JFactory::getApplication()->input;
			// get the view name & id
			$values = $jinput->getArray(array(
				'id' => 'int',
				'view' => 'word'
			));
			// check if new item
			$ref = '';
			$refJ = '';
			if (!is_null($values['id']) && strlen($values['view']))
			{
				// only load referal if not new item.
				$ref = '&amp;ref=' . $values['view'] . '&amp;refid=' . $values['id'];
				$refJ = '&ref=' . $values['view'] . '&refid=' . $values['id'];
			}
			$user = JFactory::getUser();
			// only add if user allowed to create intervention
			if ($user->authorise('intervention.create', 'com_costbenefitprojection'))
			{
				// build Create button
				$button[] = '<a id="'.$buttonName.'Create" class="btn btn-small btn-success hasTooltip" title="'.JText::sprintf('COM_COSTBENEFITPROJECTION_CREATE_NEW_S', CostbenefitprojectionHelper::safeString($buttonName, 'W')).'" style="border-radius: 0px 4px 4px 0px; padding: 4px 4px 4px 7px;"
					href="index.php?option=com_costbenefitprojection&amp;view=intervention&amp;layout=edit'.$ref.'" >
					<span class="icon-new icon-white"></span></a>';
			}
			// only add if user allowed to edit intervention
			if (($buttonName == 'intervention' || $buttonName == 'interventions') && $user->authorise('intervention.edit', 'com_costbenefitprojection'))
			{
				// build edit button
				$button[] = '<a id="'.$buttonName.'Edit" class="btn btn-small hasTooltip" title="'.JText::sprintf('COM_COSTBENEFITPROJECTION_EDIT_S', CostbenefitprojectionHelper::safeString($buttonName, 'W')).'" style="display: none; padding: 4px 4px 4px 7px;" href="#" >
					<span class="icon-edit"></span></a>';
				// build script
				$script[] = "
					jQuery(document).ready(function() {
						jQuery('#adminForm').on('change', '#jform_".$buttonName."',function (e) {
							e.preventDefault();
							var ".$buttonName."Value = jQuery('#jform_".$buttonName."').val();
							".$buttonName."Button(".$buttonName."Value);
						});
						var ".$buttonName."Value = jQuery('#jform_".$buttonName."').val();
						".$buttonName."Button(".$buttonName."Value);
					});
					function ".$buttonName."Button(value) {
						if (value > 0) {
							// hide the create button
							jQuery('#".$buttonName."Create').hide();
							// show edit button
							jQuery('#".$buttonName."Edit').show();
							var url = 'index.php?option=com_costbenefitprojection&view=interventions&task=intervention.edit&id='+value+'".$refJ."';
							jQuery('#".$buttonName."Edit').attr('href', url);
						} else {
							// show the create button
							jQuery('#".$buttonName."Create').show();
							// hide edit button
							jQuery('#".$buttonName."Edit').hide();
						}
					}";
			}
			// check if button was created for intervention field.
			if (CostbenefitprojectionHelper::checkArray($button))
			{
				// Add some final script
				$script[] = "
					jQuery(document).ready(function() {
						jQuery('#jform_".$buttonName."').closest('.control-group').addClass('input-append');
					});";
				// Load the needed script.
				$document = JFactory::getDocument();
				$document->addScriptDeclaration(implode(' ',$script));
				// return the button attached to input field.
				return $html . implode('',$button);
			}
		}
		return $html;
	}

	/**
	 * Method to get a list of options for a list input.
	 *
	 * @return	array		An array of JHtml options.
	 */
	public function getOptions()
	{
		// get the input from url
		$jinput = JFactory::getApplication()->input;
		// get the view name & id
		$interId = $jinput->getInt('id', 0);
		// Get the user object.
		$user = JFactory::getUser();
		$userIs = CostbenefitprojectionHelper::userIs($user->id);
		$db = JFactory::getDBO();
		$query = $db->getQuery(true);
		$query->select($db->quoteName(array('a.id','a.name','a.company','a.share'),array('id','interventions_name','company','share')));
		$query->from($db->quoteName('#__costbenefitprojection_intervention', 'a'));
		$query->where($db->quoteName('a.published') . ' = 1');
		$query->where($db->quoteName('a.id') . ' != ' . $interId);
		if (!$user->authorise('core.admin'))
		{
			$companies = CostbenefitprojectionHelper::hisCompanies($user->id);
			if (CostbenefitprojectionHelper::checkArray($companies))
			{
				$companies = implode(',',$companies);
				// only load this users companies
				$query->where('a.company IN (' . $companies . ')');
			}
			else
			{
				// dont allow user to see any companies
				$query->where('a.company = -4');
			}
		}
		$query->order('a.name ASC');
		$db->setQuery((string)$query);
		$items = $db->loadObjectList();
		$options = array();
		if ($items)
		{
			foreach($items as $item)
			{
				if (!CostbenefitprojectionHelper::checkIntervetionAccess($item->id,$item->share,$item->company))
				{
					continue;
				}
				if (1 == $userIs)
				{
					$options[] = JHtml::_('select.option', $item->id, $item->interventions_name);
				}
				else
				{
					$compName = CostbenefitprojectionHelper::getId('company', $item->company, 'id', 'name');
					$options[] = JHtml::_('select.option', $item->id, $item->interventions_name . ' ('.$compName.')');
				}
			}
		}
		return $options;
	}
}
