<?php
/**
 *
 * Copyright (C) Villanova University 2007.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 */

require_once 'Action.php';
require_once 'services/Admin/Admin.php';
require_once 'sys/ListWidget.php';
require_once 'sys/ListWidgetList.php';
require_once 'sys/DataObjectUtil.php';

/**
 * Provides a method of running SQL updates to the database.
 * Shows a list of updates that are available with a description of the
 *
 * @author Mark Noble
 *
 */
class CreateListWidget extends Action {
	function launch() 	{
		global $configArray;
		global $interface;
		global $user;

		$source = $_REQUEST['source'];
		$sourceId = $_REQUEST['id'];
		$existingWidget = isset($_REQUEST['widgetId']) ? $_REQUEST['widgetId'] : -1;
		$widgetName = isset($_REQUEST['widgetName']) ? $_REQUEST['widgetName'] : '';

		if ($existingWidget == -1){
			$widget = new ListWidget();
			$widget->name = $widgetName;
			if ($user->hasRole('libraryAdmin') || $user->hasRole('contentEditor')){
				//Get all widgets for the library
				$userLibrary = Library::getPatronHomeLibrary();
				$widget->libraryId = $userLibrary->libraryId;
			}else{
				$widget->libraryId = -1;
			}
			$widget->customCss = '';
			$widget->autoRotate = 0;
			$widget->description = '';
			$widget->showTitleDescriptions = 1;
			$widget->onSelectCallback = '';
			$widget->fullListLink = '';
			$widget->listDisplayType = 'tabs';
			$widget->showMultipleTitles = 1;
			$widget->insert();
		}else{
			$widget = new ListWidget();
			$widget->id = $existingWidget;
			$widget->find(true);
		}

		//Make sure to save the search
		if ($source == 'search'){
			$searchObject = new SearchEntry();
			$searchObject->id = $sourceId;
			$searchObject->find(true);
			$searchObject->saved = 1;
			$searchObject->update();
		}

		//Add the list to the widget
		$widgetList = new ListWidgetList();
		$widgetList->listWidgetId = $widget->id;
		$widgetList->displayFor = 'all';
		$widgetList->source = "$source:$sourceId";
		$widgetList->name = $widgetName;
		$widgetList->weight = 0;
		$widgetList->insert();

		//Redirect to the widget
		header("Location: $path/Admin/ListWidgets?objectAction=view&id={$widget->id}" );
	}
}