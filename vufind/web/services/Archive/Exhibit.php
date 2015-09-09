<?php
/**
 * Displays Information about Digital Repository (Islandora) Exhibit
 *
 * @category Pika
 * @author Mark Noble <mark@marmot.org>
 * Date: 8/7/2015
 * Time: 7:55 AM
 */

class Archive_Exhibit {
	function launch(){
		global $interface;
		global $configArray;

		//TODO: load content from someplace that isn't hardcoded!
		$title = "Mandala on the Yampa 2015";
		$interface->assign('title', $title);
		//TODO: This will really be read from Islandora & should be sized appropriately
		$interface->assign('main_image', 'FinalMandala_GeorgeFargo.jpg');
		$description = "<p>Six Tibetan Buddhist monks from Drepung Loseling Monastery take up residency in Library Hall in order to construct a Mandala Sand Painting this summer. For five days, the Library presents Steamboat Springs residents and visitors the opportunity for a first-hand experience with a rare and beautiful art form that travels here from the high Himalayas. Working throughout each day, the monks lay down millions of grains of colorful sand to form an intricate mandala image. The entire process is open for public viewing.</p>";
		$description .= "<p>Mandala Sand Painting is an ancient art form designed to purify and heal the environment and its inhabitants. From all the artistic traditions of Tantric Buddhism, painting with colored sand ranks as one of the most unique and exquisite. To date the monks have created mandala sand paintings in more than 100 museums, art centers, and colleges and universities around the United States and Europe, including a residency at the <a target=\"_blank\" href='https://mandalaontheyampa.wordpress.com/'>Bud Werner Memorial Library in 2010</a>.</p>";
		$interface->assign('description', $description);

		$relatedImages = array();
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc2_thumb.JPG',
			'image' => 'mandalaoc2.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc3_thumb.JPG',
			'image' => 'mandalaoc3.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc4_thumb.JPG',
			'image' => 'mandalaoc4.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$relatedImages[] = array(
			'thumbnail' => 'mandalaoc5_thumb.JPG',
			'image' => 'mandalaoc5.JPG',
			'title' => '',
			'shortTitle' => '',
		);
		$interface->assign('relatedImages', $relatedImages);

		// Additional Demo Variables
		$videoImage = 'mandalaoc3.JPG'; //TODO set
		$interface->assign('videoImage', $videoImage);
		$videoLink = ''; //TODO set
		$interface->assign('videoLink', $videoLink);

		// Define The Section List for the explore more
		$AdditionalSections[0] = array(
			'title' => 'Images',
			'image' => 'mandalaoc2_thumb.JPG',
			'link' => '',
		);

		$AdditionalSections[1] = array(
			'title' => 'Videos',
			'image' => 'mandalaoc3_thumb.JPG',
			'link' => '',
		);

		$AdditionalSections[2] = array(
			'title' => 'Articles',
			'image' => 'mandalaoc4_thumb.JPG',
			'link' => '',
		);

		$interface->assign('sectionList', $AdditionalSections);

		//Load related content
		//TODO: load this from someplace real (like Islandora)
		$exploreMoreMainLinks = array();
		$exploreMoreMainLinks[] = array(
			'title' => 'Day by Day',
			'url' => $configArray['Site']['path'] . '/Archive/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => 'Community Mandala',
			'url' => $configArray['Site']['path'] . '/Archive/3/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => 'In the News',
			'url' => $configArray['Site']['path'] . '/Archive/4/Exhibit'
		);
		$exploreMoreMainLinks[] = array(
			'title' => '2010 Mandala',
			'url' => $configArray['Site']['path'] . '/Archive/5/Exhibit'
		);
		$interface->assign('exploreMoreMainLinks', $exploreMoreMainLinks);

		//Load related catalog content
		$exploreMoreCatalogUrl = $configArray['Site']['path'] . '/Search/AJAX?method=GetListTitles&id=search:22622';
		$interface->assign('exploreMoreCatalogUrl', $exploreMoreCatalogUrl);

		//TODO: This should be the collapsible sidebar
		//$interface->assign('sidebar', 'Record/full-record-sidebar.tpl');
		$interface->assign('showExploreMore', true);
		$interface->setTemplate('exhibit.tpl');
		$interface->setPageTitle($title);

		// Display Page
		$interface->display('layout.tpl');
	}
}