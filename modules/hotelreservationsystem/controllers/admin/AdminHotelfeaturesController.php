<?php
class AdminHotelFeaturesController extends ModuleAdminController 
{
	public function __construct()
	{
		$this->bootstrap = true;
		$this->table = 'htl_branch_info';
		$this->className = 'HotelBranchInformation';
		$this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'state` s ON (s.`id_state` = a.`state_id`)';
		$this->_join .= 'LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (cl.`id_country` = a.`country_id` AND cl.`id_lang` = '.Configuration::get('PS_LANG_DEFAULT').')';
		$this->_select = 's.`name` as `state_name`, cl.`name`';
		$this->context = Context::getContext();
		$this->fields_list = array();
		$this->fields_list = array(
			'id' => array(
				'title' => $this->l('ID'),
				'align' => 'center',
			),
			'hotel_name' => array(
				'title' => $this->l('Hotel Name'),
				'align' => 'center',
			),
			'city' => array(
				'title' => $this->l('City'),
				'align' => 'center',
			),

			'state_name' => array(
				'title' => $this->l('State'),
				'align' => 'center',
			),

			'name' => array(
				'title' => $this->l('Country'),
				'align' => 'center'
			),

			'date_add' => array(
				'title' => $this->l('Date Added'),
				'align' => 'center',
				'type' => 'datetime',
				'filter_key' => 'a!date_add',
			));
		$this->identifier  = 'id';
		$this->bulk_actions = array('delete' => array('text' => $this->l('Delete selected'),
											  'icon' => 'icon-trash',
											  'confirm' => $this->l('Delete selected items?'))
									);
		parent::__construct();
	}

	public function initToolbar()
	{
		parent::initToolbar();
		$this->page_header_toolbar_btn['addfeatures'] = array(
			'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token.'&addfeatures=1',
			'desc' => $this->l('Add new Features'),
			'imgclass' => 'new'
		);
		$this->page_header_toolbar_btn['new'] = array(
			'href' => self::$currentIndex.'&add'.$this->table.'&token='.$this->token,
			'desc' => $this->l('Assign Features'),
		);
	}

	public function renderList() 
	{
		$this->addRowAction('edit');
		$this->addRowAction('delete');
		return parent::renderList();
	}

	public function renderForm() 
	{
		if (Tools::getValue('addfeatures'))
		{
			$obj_hotel_features = new HotelFeatures();
			$features_list = $obj_hotel_features->HotelAllCommonFeaturesArray();
			$this->context->smarty->assign('features_list', $features_list);
			$this->context->smarty->assign('addfeatures', 1);
		}
		if ($this->display == 'add')
		{
			$obj_hotel_features = new HotelFeatures();
			$features_list = $obj_hotel_features->HotelAllCommonFeaturesArray();
			$hotel_info_obj = new HotelBranchInformation();
			$unassigned_ftrs_hotels = $hotel_info_obj->getUnassignedFeaturesHotelIds();

			$this->context->smarty->assign('hotels', $unassigned_ftrs_hotels);
			$this->context->smarty->assign('features_list', $features_list);
		}
		elseif ($this->display == 'edit')
		{
			$id = Tools::getValue('id');
			$this->context->smarty->assign('edit', 1);
			$obj_hotel_features = new HotelFeatures();
			$hotel_info_obj = new HotelBranchInformation();
			$features_hotel = $hotel_info_obj->getFeaturesOfHotelByHotelId(Tools::getValue('id'));

			$features_list = $obj_hotel_features->HotelBranchSelectedFeaturesArray($features_hotel);
			$hotels = $hotel_info_obj->hotelsNameAndId();
			$this->context->smarty->assign('hotel_id',$id);
			$this->context->smarty->assign('hotels', $hotels);
			$this->context->smarty->assign('features_list', $features_list);
		}

		$this->fields_form = array(
				'submit' => array(
					'title' => $this->l('Save')
				)
			);
		return parent::renderForm();
	}

	public function processSave()
	{
		if (Tools::getValue('addfeatures')) //Process of assignig features
		{

		}
		else
		{
			$edit_id = Tools::getValue('edit_hotel_id');
			if ($edit_id)
				$delete_existing_vals = Db::getInstance()->delete('htl_branch_features','id_hotel='.$edit_id);

			$id_hotel = Tools::getValue('id_hotel');

			if ($id_hotel)
			{
				$hotel_features = Tools::getValue('hotel_fac');
				$obj_hotel_features = new HotelBranchFeatures();
				$assigned = $obj_hotel_features->assignFeaturesToHotel($id_hotel, $hotel_features);

				if (!$assigned)
					$this->errors[] = Tools::displayError('Some problem occure while assigning Features to the hotel.');

				if (empty($this->errors))
				{
					if (Tools::isSubmit('submitAdd'.$this->table.'AndStay'))
						Tools::redirectAdmin(self::$currentIndex.'&id='.(int)$id_hotel.'&update'.$this->table.'&conf=3&token='.$this->token);
					else
					{
						if ($edit_id)
							Tools::redirectAdmin(self::$currentIndex.'&conf=4&token='.$this->token);
						else
							Tools::redirectAdmin(self::$currentIndex.'&conf=3&token='.$this->token);
					}
				}
				else
				{
					if ($hotel_id)
						$this->display = 'edit';
					else
						$this->display = 'add';
				}
			}
			else
			{
				$this->errors[] = Tools::displayError('Please select a hotel first.');
				$this->display = 'add';
			}
		}
	}

	public function postProcess()
	{
		if (Tools::getValue('error'))
		{
			if (Tools::getValue('error') == 1)
				$msg = Tools::displayError('Parent feature name is required.');
			else if (Tools::getValue('error') == 2)
				$msg = Tools::displayError('Position is required.');
			else if (Tools::getValue('error') == 3)
				$msg = Tools::displayError('Please add atleast one Child features are required.');
			else if (Tools::getValue('error') == 4)
				$msg = Tools::displayError('Some error occured. Please try again.');
			else if (Tools::getValue('error') == 2)
				$msg = Tools::displayError('Position is required.');

			$this->errors[] = Tools::displayError($msg);
			$this->context->smarty->assign("errors", $this->errors);
		}
		if (Tools::isSubmit('submit_add_btn_feature'))
		{
			$parent_feature = Tools::getValue('parent_ftr');
			$child_features = Tools::getValue('child_featurs');
			$pos = Tools::getValue('position');
			if (!$parent_feature)
				$error = 1;
			else if (!$pos)
				$error = 2;
			else if (!$child_features)
				$error = 3;
			if (!isset($error))
			{
				$obj_feature = new HotelFeatures();
	            $obj_feature->name = $parent_feature;
	            $obj_feature->active = 1;
	            $obj_feature->position = $pos;
	            $obj_feature->parent_feature_id = 0;
	            $obj_feature->save();
	            $parent_feature_id = $obj_feature->id;
	            if ($parent_feature_id)
				{
					if ($child_features)
					{
						foreach ($child_features as $val)
			            {
			                $obj_feature = new HotelFeatures();
			                $obj_feature->name = $this->l($val);
			                $obj_feature->active = 1;
			                $obj_feature->parent_feature_id = $parent_feature_id;
			                $obj_feature->save();
			            }
			            Tools::redirectAdmin(self::$currentIndex.'&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
					}
				}
				else
					Tools::redirectAdmin(self::$currentIndex.'&error=4&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
			}
			else
				Tools::redirectAdmin(self::$currentIndex.'&error='.$error.'&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
		}

		if (Tools::isSubmit('submit_edit_btn_feature'))
		{
			$chld_features_form = Tools::getValue('child_featurs');
			$parent_ftr = Tools::getValue('parent_ftr');
			$position = Tools::getValue('position');
			$prnt_ftr_id = Tools::getValue('parent_ftr_id');
			if (!$parent_ftr)
				$error = 1;
			else if (!$position)
				$error = 2;
			else if (!$chld_features_form)
				$error = 3;
			if (!isset($error))
			{
				$update_prnt_ftr = Db::getInstance()->update('htl_features',array('name'=>$parent_ftr,'position'=>$position),'id='.$prnt_ftr_id);
				$child_features_data = Db::getInstance()->executeS('SELECT id FROM `'._DB_PREFIX_.'htl_features` WHERE parent_feature_id='.(int)$prnt_ftr_id);
				if ($child_features_data)
				{
					$i=0;
					foreach($child_features_data as $val)
					{
						$flag = 0;
						foreach ($chld_features_form as $value)
						{
							if (is_numeric($value))
							{
								if ($val['id'] == $value)
									$flag = 1;
							}
							else if($i == 0)
							{
								$obj_feature = new HotelFeatures();
				                $obj_feature->name = $value;
				                $obj_feature->active = 1;
				                $obj_feature->parent_feature_id = $prnt_ftr_id;
				                $obj_feature->save();
							}
						}
						if (!$flag)
							$del_arr[] = $val['id'];
			            $i++;
					}
					if (isset($del_arr) && $del_arr)
					{
						foreach ($del_arr as $value)
						{
							$delete_ftr = Db::getInstance()->delete('htl_features','id='.$value);
						}
					}
					Tools::redirectAdmin(self::$currentIndex.'&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
				}
				else
					Tools::redirectAdmin(self::$currentIndex.'&error=4&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
			}
			else
				Tools::redirectAdmin(self::$currentIndex.'&error='.$error.'&add'.$this->table.'&token='.$this->token.'&addfeatures=1');
		}
		parent::postProcess();
	}

	public function ajaxProcessDeleteFeature()
	{
		$dlt_id = Tools::getValue('feature_id');
		$obj_hotel_features = new HotelFeatures();
		$deleted_feature = $obj_hotel_features->deleteHotelFeatures($dlt_id);
		if ($deleted_feature)
			die('success');
		else
			echo 0;
	}

	public function setMedia()
	{
		parent::setMedia();
		$this->addJs(_MODULE_DIR_.'hotelreservationsystem/views/js/HotelReservationAdmin.js');
		$this->addCSS(_MODULE_DIR_.'hotelreservationsystem/views/css/HotelReservationAdmin.css');
	}
}	