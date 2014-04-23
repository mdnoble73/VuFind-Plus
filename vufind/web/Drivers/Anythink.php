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
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 */

require_once 'Interface.php';
require_once 'sys/SIP2.php';
require_once 'Drivers/Horizon.php';

class Anythink extends Horizon {
	function __construct(){
		parent::__construct();
	}

	function translateFineMessageType($code){
		switch ($code){
			case "abs":       return "Automatic Bill Sent";
			case "acr":       return "Address Correction Requested";
			case "adjcr":     return "Adjustment credit, for changed";
			case "adjdbt":    return "Adjustment debit, for changed";
			case "balance":   return "Balancing Entry";
			case "bcbr":      return "Booking Cancelled by Borrower";
			case "bce":       return "Booking Cancelled - Expired";
			case "bcl":       return "Booking Cancelled by Library";
			case "bcsp":      return "Booking Cancelled by Suspension";
			case "bct":       return "Booking Cancelled - Tardy";
			case "bn":        return "Billing Notice";
			case "chgs":      return "Charges Misc. Fees";
			case "cr":        return "Claimed Return";
			case "credit":    return "Credit";
			case "damage":    return "Damaged";
			case "dc":        return "Debt Collection";
			case "dynbhm":    return "Dynix Being Held Mail";
			case "dynbhp":    return "Dynix Being Held Phone";
			case "dynfnl":    return "Dynix Final Overdue Notice";
			case "dynhc":     return "Dynix Hold Cancelled";
			case "dynhexp":   return "Dynix Hold Expired";
			case "dynhns":    return "Dynix Hold Notice Sent";
			case "dynnot1":   return "Dynix First Overdue Notice";
			case "dynnot2":   return "Dynix Second Overdue Notice";
			case "edc":       return "Exempt from Debt Collection";
			case "fdc":       return "Force to Debt Collection";
			case "fee":       return "ILL fees/Postage";
			case "final":     return "Final Overdue Notice";
			case "finalr":    return "Final Recall Notice";
			case "fine":      return "Fine";
			case "hcb":       return "Hold Cancelled by Borrower";
			case "hcl":       return "Hold Cancelled by Library";
			case "hclr":      return "Hold Cancelled & Reinserted in";
			case "he":        return "Hold Expired";
			case "hncko":     return "Hold Notification - Deliver";
			case "hncsa":     return "Hold - from closed stack";
			case "hnmail":    return "Hold Notification - Mail";
			case "hnphone":   return "Hold Notification - Phone";
			case "ill":       return "Interlibrary Loan Notification";
			case "in":        return "Invoice";
			case "infocil":   return "Checkin Location";
			case "infocki":   return "Checkin date";
			case "infocko":   return "Checkout date";
			case "infodue":   return "Due date";
			case "inforen":   return "Renewal date";
			case "l":         return "Lost";
			case "ld":        return "Lost on Dynix";
			case "lf":        return "Found";
			case "LostPro":   return "Lost Processing Fee";
			case "lr":        return "Lost Recall";
			case "msg":       return "Message to Borrower";
			case "nocko":     return "No Checkout";
			case "Note":      return "Comment";
			case "notice1":   return "First Overdue Notice";
			case "notice2":   return "Second Overdue Notice";
			case "notice3":   return "Third Overdue Notice";
			case "noticr1":   return "First Recall Notice";
			case "noticr2":   return "Second Recall Notice";
			case "noticr3":   return "Third Recall Notice";
			case "noticr4":   return "Fourth Recall Notice";
			case "noticr5":   return "Fifth Recall Notice";
			case "nsn":       return "Never Send Notices";
			case "od":        return "Overdue Still Out";
			case "odd":       return "Overdue Still Out on Dynix";
			case "odr":       return "Recalled and Overdue Still Out";
			case "onlin":     return "Online Registration";
			case "payment":   return "Fine Payment";
			case "pcr":       return "Phone Correction Requested";
			case "priv":      return "Privacy - Family permission";
			case "rd":        return "Request Deleted";
			case "re":        return "Request Expired";
			case "recall":    return "Item is recalled before due date";
			case "refund":    return "Refund of Payment";
			case "ri":        return "Reminder Invoice";
			case "rl":        return "Requested item lost";
			case "rn":        return "Reminder Billing Notice";
			case "spec":      return "Special Message";
			case "supv":      return "See Supervisor";
			case "suspend":   return "Suspension until ...";
			case "unpd":      return "Damaged Material Replacement";
			case "waiver":    return "Waiver of Fine";
			default:
				return $code;
		}
	}

	public function translateLocation($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        'acpl' => 'Administration',
        'be' => 'Bennett',
        'br'  => 'Brighton',
        'cc'  => 'Commerce City',
        'ext' => 'In Motion Bookmobile',
        'ng'  => 'Huron Street',
        'pm' => 'Perl Mack',
        'th'  => 'York Street',
        'wf' => 'Wright Farms',
        );
        return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : 'Unknown' ;
	}

	public function translateCollection($collectionCode){
		$collectionCode = strtolower($collectionCode);
		$collectionMap = array(
                  	'ajob'  => 'Adult Nonfiction',		
                  	'at'    => 'Audio Tapes',		
                  	'atls'  => 'Reference',		
                  	'b'     => 'Biographies',		
                  	'bb'    => "Children's Book & CD kit",		
                  	'board' => 'Board Books',		
                  	'cd'    => 'Adult CD Audiobooks',		
                  	'cdd'   => 'Data CDs',		
                  	'cde'   => 'Easy CD Audiobooks',		
                  	'cdi'   => 'Juvenile CD Audiobooks',		
                  	'cdj'   => 'Juvenile CD Audiobooks',		
                  	'cdt'   => 'Teen CD Audiobooks',		
                  	'co'    => 'Adult Nonfiction',		
                  	'coe'   => 'Easy Nonfiction',		
                  	'coi'   => 'Juvenile Nonfiction',		
                  	'coj'   => 'Juvenile Nonfiction',		
                  	'cot'   => 'Teen Nonfiction',		
                  	'dvd'   => 'Adult DVDs',
                 	'dvdbox' => 'Adult DVDs',		
                  	'e'      => 'Easy Nonfiction',		
                  	'ebook'  => 'E-Books / E-Audiobooks',		
                  	'ecap'   => 'Easy Nonfiction',		
                  	'ef'     => 'Picture Books',		
                  	'ency'   => 'Reference',		
                  	'eqx'    => 'Equipment',		
                  	'er'     => 'Easy Reader',		
                  	'f'      => 'Adult Fiction',		
                  	'FA'     => 'Fast Add',		
                 	'FA-BI'  => 'Fast Add',		
                  	'FA-I'   => 'Fast Add',		
                  	'fan'    => 'Adult Fantasy',		
                  	'gn'     => 'Adult Graphic Novels',		
                  	'gw'     => 'Genealogy & Western History',		
                  	'hilo'   => 'HI LO',		
                 	'hol'    => 'Adult Nonfiction',		
                 	'hole'   => 'Easy Holiday',		
                 	'holj'   => 'Juvenile Holiday',		
                 	'hrl'    => 'Adult Nonfiction',		
                 	'i'      => 'Juvenile Nonfiction',		
                  	'if'     => 'Chapter Books',		
                  	'if1'    => 'Chapter Books',		
                 	'ill'    => 'Item Not Available for Lending',		
                 	'indx'   => 'Reference',		
                 	'j'      => 'Juvenile Nonfiction',		
                 	'jb'     => 'Juvenile Biography',		
                  	'jdvd'   => "Children's DVDs",		
                  	'jf'     => 'Chapter Books',		
                  	'jf1'    => 'Chapter Books',		
                  	'jfan'   => 'Chapter Books-Fantasy',		
                  	'jgn'    => "Children's Graphic Novels",		
                  	'jrcs'   => 'Juvenile Reading Club',		
                  	'kit'    => "Children's Book & CD kit",		
                 	'lit'    => 'Literacy',		
                 	'lp'     => 'Large Print - Fiction',		
                 	'lpnf'   => 'Large Print - Nonfiction',		
                  	'lsta'   => 'Adult Spanish',
			'mcd'    => 'Music CDs',		
                  	'mcdj'   => "Children's Music CDs",		
                 	'mcdpop' => 'CD Pop / Rock / Rap',		
                 	'mcf'    => 'Adult Fiction',		
                 	'mcn'    => 'Adult Nonfiction',		
                 	'mpa'    => 'Adult Playaways - Fiction',		
                  	'mpan'   => 'Adult Playaways - Nonfiction',		
                  	'mpj'    => "Children's Playaways - Fiction",		
                  	'mpjn'   => "Children's Playaways - Nonfiction",		
                  	'mpt'    => "Children's Playaways - Fiction",		
                  	'mptn'   => "Children's Playaways - Nonfiction",		
                  	'mxblu'  => 'Adult Music CDs - Spanish / World',		
                  	'mxchl'  => "Children's Music CDs",		
                  	'mxcls'  => 'Adult Music CDs - Classical',		
                  	'mxctr'  => 'Adult Music CDs - Country',		
                  	'mxgos'  => 'Adult Music CDs - Inspirational',		
                  	'mxhol'  => 'Adult Music CDs - Holiday',		
                  	'mxjaz'  => 'Adult Music CDs - Jazz / R&B',		
                  	'mxopr'  => 'Adult Music CDs - Opera',		
                  	'mxpop'  => 'Adult Music CDs - Pop / Rock / Rap',		
                  	'mxstr'  => 'Adult Music CDs - Soundtracks / Musicals',		
                  	'mys'    => 'Adult Mysteries',		
                  	'new'    => 'New & Notable Fiction',		
                  	'newco'  => 'New & Notable Nonfiction',		
                  	'newmy'  => 'New & Notable Fiction',		
                  	'newnf'  => 'New & Notable Nonfiction',		
                  	'newsf'  => 'New & Notable Fiction',		
                  	'newwe'  => 'New & Notable Fiction',		
                  	'nf'     => 'Adult Nonfiction',		
                  	'ol'     => 'Overload',		
                  	'per'    => 'Periodicals',		
                  	'pn'     => 'Fotonovelas',		
                  	'puz'    => 'Puzzles & Games',		
                  	'rcs'    => 'Book Club',		
                  	'read'   => 'Easy Reader',		
                  	'ref'    => 'Reference',		
                  	'ref7d'  => 'Reference',		
                  	'refa'   => "Children's Reference",		
                  	'refb'   => 'Reference',		
                  	'refc'   => 'Reference',		
                  	'refco'  => 'Reference',		
                  	'refe'   => "Children's Reference",		
                  	'refi'   => "Children's Reference",		
                  	'refj'   => "Children's Reference",		
                  	'refr'   => 'Ready Reference',		
                  	'refx'   => 'Reference',		
                  	'rom'    => 'Adult Romance',		
                  	'sf'     => 'Adult Science Fiction',		
                  	'span'   => 'Adult Spanish',		
                  	'spane'  => "Children's Spanish",		
                	'spaner' => "Children's Spanish",		
                	'spani ' => "Children's Spanish",		
               		'spanj'  => "Children's Spanish",		
                 	'spant'  => 'Teen Spanish Collection',		
                  	't'      => 'Teen Nonfiction',		
                  	'tco'    => 'Teen Nonfiction',		
                  	'tf'     => 'Teen Fiction',		
                  	'tfan'   => 'Teen Fantasy',		
                  	'tgn'    => 'Teen Graphic Novels',		
                  	'tmys'   => 'Teen Mystery',		
                  	'trcs'   => 'Teen Reading Club',		
                  	'trom'   => 'Teen Romance',		
                  	'u'      => 'Fast Add',		
                  	'used'   => 'Temp Item',		
                  	'vf'     => 'Vertical File',		
                  	'vtx'    => 'Video Tapes',		
                  	'west'   => 'Adult Westerns',		
                  	'xbconly'  => 'On order',		
                  	'xparpro'  => 'On order',		
                  	'xunpro'   => 'On order',		
                  	'jconc'	=> "Children's Nonfiction",		
                  	'janim'	=> "Children's Nonfiction",		
                  	'jart'	=> "Children's Nonfiction",		
                  	'jbiog'	=> "Children's Nonfiction",		
                  	'jcare'	=> "Children's Nonfiction",		
                  	'jcook'	=> "Children's Nonfiction",		
                  	'jcraf'	=> "Children's Nonfiction",		
                  	'jcuri'	=> "Children's Nonfiction",		
                  	'jdram'	=> "Children's Nonfiction",		
                  	'jfami'	=> "Children's Nonfiction",		
                  	'jtale'	=> "Children's Nonfiction",		
                  	'jlang'	=> "Children's Nonfiction",		
                  	'jgame'	=> "Children's Nonfiction",		
                  	'jgard'	=> "Children's Nonfiction",		
                  	'jheal'	=> "Children's Nonfiction",		
                  	'jhist'	=> "Children's Nonfiction",		
                  	'holj'	=> "Children's Nonfiction",		
                  	'jhumo'	=> "Children's Nonfiction",		
                  	'jlng'	=> "Children's Nonfiction",		
                  	'jlaw'	=> "Children's Nonfiction",		
                  	'jlife'	=> "Children's Nonfiction",		
                  	'jmath'	=> "Children's Nonfiction",		
                  	'jmusi'	=> "Children's Nonfiction",		
                  	'jplac'	=> "Children's Nonfiction",		
                  	'jpets'	=> "Children's Nonfiction",		
                  	'jpoet'	=> "Children's Nonfiction",		
                  	'jgovt'	=> "Children's Nonfiction",		
                  	'jref'	=> "Children's Nonfiction",		
                  	'jreli'	=> "Children's Nonfiction",		
                  	'jscie'	=> "Children's Nonfiction",		
                  	'jspor'	=> "Children's Nonfiction",		
                  	'jtech'	=> "Children's Nonfiction",		
                  	'jtran'	=> "Children's Nonfiction",		
                  	'jfic'   => 'Chapter Books',		
                  	'jffan'  => 'Chapter Books-Fantasy',		
                  	'jfhis'  => 'Chapter Books-Historical',		
                  	'jfsca'  => 'Chapter Books-Scary',		
                  	'jfmys'  => 'Chapter Books-Mystery',		
                  	'jfsf'   => 'Chapter Books-Science Fiction',		
                  	'ef'     => 'Picture Books',		
                  	'er'     => 'Easy Readers',		
                  	'kit'	 => "Children's Book & CD Kits",		
                  	'board'   => 'Board Books',		
                  	'spef'    => 'Spanish Picture Books',		
                  	'spjnf'   => "Children's Spanish Nonfiction",		
                  	'spjf'    => 'Spanish Chapter Books',		
                  	'spboard' => 'Spanish Board Books',				
                  	'dvdnf'   => 'Adult DVDs - Nonfiction',		
                  	'dvdact'  => 'Adult DVDs - Action',		
                  	'dvdani'  => 'Adult DVDs - Animated',		
                  	'jdvd'    => "Children's DVDs",		
                  	'jdvdnf'  => "Children's DVDs - Nonfiction",		
                  	'dvdcom'  => 'Adult DVDs - Comedy',		
                  	'dvddra'  => 'Adult DVDs - Drama',		
                  	'dvdfor'  => 'Adult DVDs - Foreign',		
                  	'dvdhor'  => 'Adult DVDs - Horror',		
                  	'dvdmus'  => 'Adult DVDs - Musicals',		
                  	'dvdsci'  => 'Adult DVDs - Science Fiction',		
                  	'dvdspan' => 'Adult DVDS - Spanish',		
                  	'mxblu'   => 'Adult Music CDs - Spanish',		
                  	'acdfic'  => 'Adult CD Audiobooks - Fiction',		
                  	'acdnf'   => 'Adult CD Audiobooks - Nonfiction',		
                  	'jcdfic'  => "Children's CD Audiobooks - Fiction",		
                  	'jcdnf'   => "Children's CD Audiobooks - Nonfiction",		
                  	'tcdfic'  => 'Teen CD Audiobooks - Fiction',		
                  	'tcdnf'   => 'Teen CD Audiobooks - Nonfiction',		
                  	'mpt'     => 'Teen Playaways - Fiction',		
                  	'mptn'    => 'Teen Playaways - Nonfiction',						
                  	'eqx'     => 'Equipment',		
                  	'gw'      => 'Genealogy & Western History',		
                  	'ill'     => 'Item not available for lending',		
                  	'laptop'  => 'Anythink Laptops',		
                  	'studio'  => 'The Studio Equipment',				
                  	'tnf'     => 'Teen Nonfiction',		
                  	'tfic'    => 'Teen Fiction',		
                  	'tficfan'  => 'Teen Fiction - Fantasy',		
                  	'tficglb'  => 'Teen Fiction - GLBTQ',		
                  	'tfichis'  => 'Teen Fiction - Historical',		
                  	'tfichor'  => 'Teen Fiction - Horror',		
                  	'tficins'  => 'Teen Fiction - Inspirational',		
                  	'tficmys'  => 'Teen Fiction - Mystery',		
                  	'tficrom'  => 'Teen Fiction - Romance',		
                  	'tficsf'   => 'Teen Fiction - Science Fiction',					
                  	'afarm'    => 'Adult Nonfiction',		
                  	'aanti'    => 'Adult Nonfiction',		
                  	'aarch'    => 'Adult Nonfiction',		
                  	'aart'     => 'Adult Nonfiction',		
                  	'abiog'    => 'Adult Nonfiction',		
                  	'aspir'    => 'Adult Nonfiction',		
                  	'abusi'    => 'Adult Nonfiction',		
                  	'gn'       => 'Adult Graphic Novels',		
                  	'acomp'    => 'Adult Nonfiction',		
                  	'acook'    => 'Adult Nonfiction',		
                  	'acraf'    => 'Adult Nonfiction',		
                  	'adram'    => 'Adult Nonfiction',		
                  	'aeduc'    => 'Adult Nonfiction',		
                  	'afic'     => 'Adult Fiction',		
                  	'aficfan'  => 'Adult Fiction - Fantasy',		
                  	'aficglb'  => 'Adult Fiction - GLBTQ',		
                  	'afichis'  => 'Adult Fiction - Historical',		
                  	'afichor'  => 'Adult Fiction - Horror',		
                  	'aficins'  => 'Adult Fiction - Inspirational',		
                  	'aficmys'  => 'Adult Fiction - Mystery',		
                  	'aficrom'  => 'Adult Fiction - Romance',		
                  	'aficsf'   => 'Adult Fiction - Science Fiction',		
                  	'aficwes'  => 'Adult Fiction - Western',		
                  	'agame'    => 'Adult Nonfiction',		
                  	'agard'    => 'Adult Nonfiction',		
                  	'agene'    => 'Adult Nonfiction',		
                  	'aheal'    => 'Adult Nonfiction',		
                  	'ahist'    => 'Adult Nonfiction',		
                  	'aholi'    => 'Adult Nonfiction',		
                  	'ahome'    => 'Adult Nonfiction',		
                  	'ahumo'    => 'Adult Nonfiction',		
                  	'alng'     => 'Adult Nonfiction',		
                  	'alang'    => 'Adult Nonfiction',		
                  	'alaw'     => 'Adult Nonfiction',		
                  	'alitco'   => 'Adult Nonfiction',		
                  	'alitcr'   => 'Adult Nonfiction',		
                  	'amath'    => 'Adult Nonfiction',		
                  	'amedi'    => 'Adult Nonfiction',		
                  	'amusi'    => 'Adult Nonfiction',		
                  	'anatu'    => 'Adult Nonfiction',		
                  	'apare'    => 'Adult Nonfiction',		
                  	'apets'    => 'Adult Nonfiction',		
                  	'aphil'    => 'Adult Nonfiction',		
                  	'apoet'    => 'Adult Nonfiction',		
                  	'apoli'    => 'Adult Nonfiction',		
                  	'aread'    => 'Adult Nonfiction',		
                  	'aref'     => 'Adult Nonfiction',		
                  	'arela'    => 'Adult Nonfiction',		
                  	'areli'    => 'Adult Nonfiction',		
                  	'ascie'    => 'Adult Nonfiction',		
                  	'aself'    => 'Adult Nonfiction',		
                  	'asoc'     => 'Adult Nonfiction',		
                  	'aspor'    => 'Adult Nonfiction',		
                  	'atest'    => 'Adult Nonfiction',		
                  	'atech'    => 'Adult Nonfiction',		
                  	'acar'     => 'Adult Nonfiction',		
                  	'atech'    => 'Adult Nonfiction',		
                  	'atran'    => 'Adult Nonfiction',		
                  	'atrav'    => 'Adult Nonfiction',		
                  	'atcri'    => 'Adult Nonfiction',		
                  	'aspfic'   => 'Adult Spanish Fiction',		
                  	'aspnf'    => 'Adult Spanish Nonfiction',

		);
		return isset($collectionMap[$collectionCode]) ? $collectionMap[$collectionCode] : 'Unknown';
	}

	public function translateStatus($statusCode){
		$statusCode = trim(strtolower($statusCode));
		$statusMap = array(
        'a' => 'Archived',
        'b' => 'Bindery',
        'bst' => 'Benett Storage for new building',
        'c' => 'Claimed Returned',
        'ckod' => 'Checked out on Dynix',
        'csa' => 'Closed Stack',
        'dmg' => 'Damaged',
        'e' => 'Item hold expired',
        'ebook' => 'Online only',
        'fc' => 'forthcoming - brief record',
        'h' => 'Item being held',
        'i' => 'Checked In',
        'ini' => 'Incomplete Item',
        'l' => 'Lost',
        'lr' => 'Lost Recall',
        'm' => 'Item missing',
        'me' => 'Mending',
        'mi' => 'Missing Inventory',
        'n' => 'On Order',
        'o' => 'Checked out',
        'ov' => 'Overload',
        'r' => 'On Order',
        'rb' => 'Reserve Bookroom',
        'recall' => 'Recall',
        'rw' => 'Reserve withdrawal',
        's' => 'Recently Returned',
        'sr' => 'stored reference',
        't' => 'In Cataloging',
        'tc' => 'Transit Recall',
        'th' => 'Transit Request',
        'tr' => 'Transit',
        'trace' => 'Trace',
        'trace_r' => 'Trace Reported',
        'ts' => 'Transit Stock Rotation',
        'u' => 'Unusual',
        'ufa' => 'user fast added item',
        'wfh' => 'Wright Farms holding',
        'y' => 'On Display',
        );

		return isset($statusMap[$statusCode]) ? $statusMap[$statusCode] : 'Unknown ';
	}

	public function getLocationMapLink($locationCode){
		$locationCode = strtolower($locationCode);
		$locationMap = array(
        );
		return isset($locationMap[$locationCode]) ? $locationMap[$locationCode] : '' ;
	}

	public function getLibraryHours($locationId, $timeToCheck){
		return null;
	}

	function selfRegister(){
		global $configArray;
		global $logger;

		//Setup Curl
		$header=array();
		$header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";
		$header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";
		$header[] = "Cache-Control: max-age=0";
		$header[] = "Connection: keep-alive";
		$header[] = "Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7";
		$header[] = "Accept-Language: en-us,en;q=0.5";
		$cookie = tempnam ("/tmp", "CURLCOOKIE");

		//Start at My Account Page
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp?profile={$this->selfRegProfile}&menu=account";
		$curl_connection = curl_init($curl_url);
		curl_setopt($curl_connection, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($curl_connection, CURLOPT_HTTPHEADER, $header);
		curl_setopt($curl_connection, CURLOPT_USERAGENT,"Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
		curl_setopt($curl_connection, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_connection, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl_connection, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($curl_connection, CURLOPT_UNRESTRICTED_AUTH, true);
		curl_setopt($curl_connection, CURLOPT_COOKIEJAR, $cookie);
		curl_setopt($curl_connection, CURLOPT_COOKIESESSION, true);
		curl_setopt($curl_connection, CURLOPT_REFERER,$curl_url);
		curl_setopt($curl_connection, CURLOPT_FORBID_REUSE, false);
		curl_setopt($curl_connection, CURLOPT_HEADER, false);
		curl_setopt($curl_connection, CURLOPT_HTTPGET, true);
		$sresult = curl_exec($curl_connection);
		$logger->log("Loading Full Record $curl_url", PEAR_LOG_INFO);

		//Extract the session id from the requestcopy javascript on the page
		if (preg_match('/\\?session=(.*?)&/s', $sresult, $matches)) {
			$sessionId = $matches[1];
		} else {
			PEAR::raiseError('Could not load session information from page.');
		}

		//Login by posting username and password
		$post_data = array(
      'aspect' => 'overview',
      'button' => 'New User',
      'login_prompt' => 'true',
      'menu' => 'account',
			'newuser_prompt' => 'true',
      'profile' => $this->selfRegProfile,
      'ri' => '',
      'sec1' => '',
      'sec2' => '',
      'session' => $sessionId,
		);
		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		$curl_url = $this->hipUrl . "/ipac20/ipac.jsp";
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url);
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		$firstName = strip_tags($_REQUEST['firstName']);
		$lastName = strip_tags($_REQUEST['lastName']);
		$address1 = strip_tags($_REQUEST['address1']);
		$address2 = strip_tags($_REQUEST['address2']);
		$citySt = strip_tags($_REQUEST['citySt']);
		$zip = strip_tags($_REQUEST['zip']);
		$email = strip_tags($_REQUEST['email']);
		$sendNoticeBy = strip_tags($_REQUEST['sendNoticeBy']);
		$pin = strip_tags($_REQUEST['pin']);
		$confirmPin = strip_tags($_REQUEST['confirmPin']);
		$phone = strip_tags($_REQUEST['phone']);
		$phoneType = strip_tags($_REQUEST['phoneType']);
		$language = strip_tags($_REQUEST['language']);
		$location = strip_tags($_REQUEST['location']);
		$borrowerNote = strip_tags($_REQUEST['borrowerNote']);

		//Register the patron
		$post_data = array(
      'address1' => $address1,
		  'address2' => $address2,
			'aspect' => 'basic',
			'pin#' => $pin,
			'button' => 'I accept',
			'city_st' => $citySt,
			'confirmpin#' => $confirmPin,
			'email_address' => $email,
			'firstname' => $firstName,
			'borrower_note' => $borrowerNote,
			'ipp' => 20,
			'lastname' => $lastName,
			'language' => $language,
			'location' => $location,
			'menu' => 'account',
			'newuser_info' => 'true',
			'npp' => 30,
			'postal_code' => $zip,
      'phone_no' => $phone,
			'phone_type' => $phoneType,
      'profile' => $this->selfRegProfile,
			'ri' => '',
			'send_notice_by' => $sendNoticeBy,
			'session' => $sessionId,
			'spp' => 20
		);

		$post_items = array();
		foreach ($post_data as $key => $value) {
			$post_items[] = $key . '=' . urlencode($value);
		}
		$post_string = implode ('&', $post_items);
		curl_setopt($curl_connection, CURLOPT_POST, true);
		curl_setopt($curl_connection, CURLOPT_URL, $curl_url . '#focus');
		curl_setopt($curl_connection, CURLOPT_POSTFIELDS, $post_string);
		$sresult = curl_exec($curl_connection);

		//Get the temporary barcode from the page
		if (preg_match('/Here is your temporary barcode\\. Use it for future authentication:&nbsp;([\\d-]+)/s', $sresult, $regs)) {
			$tempBarcode = $regs[1];
			//Append the library prefix to the card number
			$barcodePrefix = $configArray['Catalog']['barcodePrefix'];
			$tempBarcode = substr($barcodePrefix, 0, 6) . $tempBarcode;
			$success = true;
		}else{
			$success = false;
		}

		unlink($cookie);

		return array(
		  'tempBarcode' => $tempBarcode,
		  'result'  => $success
		);

	}
}
?>