<?php
/**
 *
 * @author      Mahotilo
 * @version     1.4
 * based on  https://github.com/jakeRPowell/linkChecker
 */

defined('is_running') or die('Not an entry point...');

class LinkChecker {
	
	public static $results = array();

	public static $WhiteList = array(
		'mailto:',
		'tel:','callto:','wtai:','sms:',
		'market:',
		'skype:','gtalk:','whatsapp:',
		'facebook.com/sharer',
		'twitter.com/intent',
	);


  /* 
   * Typesetter Action hook 
   */
	public static function GetHead() {
		global $page, $addonRelativeCode;
		if( \gp\tool::LoggedIn() ){
			$page->css_admin[] = $addonRelativeCode . '/LinkChecker.css';
			$page->head_js[] = '/include/thirdparty/tablesorter/tablesorter.js';
			$page->head_js[] = $addonRelativeCode . '/LinkChecker.js';
		}
	}


	public static function GetResults() {
		global $addonPathData;
		if( file_exists($addonPathData . '/results.php') ){
			include $addonPathData . '/results.php';
			self::$results = $results;

			//clear too old records	
			foreach (self::$results as $key => $result) {
				if ( time() - $result[2] > (30 * 24 * 60 * 60)) { //one month
					array_splice(self::$results, $key, 1);
					self::SaveResults();					
				}
			}
		
			return true;
		}
		return false;
	}


	public static function SaveResults() {
		global $addonPathData;
		return \gp\tool\Files::SaveData($addonPathData . '/results.php','results',self::$results);
	}


  /* 
   * Typesetter Filter hook 
   */

	public static function PageRunScript($cmd) {
		global $page;
		self::GetResults();
		if( \gp\tool::LoggedIn() ){
			$page->admin_links[] = array(
				$page->requested, 
				'<i class="fa fa-chain-broken" style="margin-left: 0; margin-right: -0.1em;"></i><i class="fa fa-search"></i>',
				'cmd=LC_Form', 
				'title="check Links" class="" data-cmd="gpabox"'
			);

			switch( $cmd ){
				case 'LC_Form':
					ob_start();
					echo '<div class="inline_box">';
					echo '<h3>Link Checker</h3>';
					echo '<hr>';
					
					if ( $page->visibility || \gp\tool\Files::Exists(dirname($page->file).'/draft.php') ) {
						echo 'Checking is not available for drafts or private pages';
						echo '</div>';
						$page->contentBuffer = ob_get_clean();
						return 'return';
					}

					echo self::LinkCheckerForm();
					echo '<br>';
					$result = self::AddLinkCheckerTable();
					if ($result !== false) {
						echo $result; 
					} else {
						echo 'No actual data available. Run check';
					}
					echo '</div>';
					$page->contentBuffer = ob_get_clean();
					return 'return';
					break;
				
				case 'LC_Check':
					ob_start();
					echo '<div class="inline_box">';
					echo '<h3>Link Checker</h3>';
					echo '<hr>';
					echo self::LinkCheckerForm();
					echo '<br>';
					echo self::GetLinkCheckerTable();
					echo '</div>';
					$page->contentBuffer = ob_get_clean();
					return 'return';
					break;
			}
		}
		return $cmd;
	}


  /* 
   * Auxilary
   */


	public static function LinkCheckerForm() {
		global $page, $langmessage, $config;
		$form_action = \gp\tool::GetUrl($page->requested);
		$data_cmd = $page->gp_index ? ' data-cmd="gpabox" ' : '';
		$admin_box_close = $page->gp_index ? 'admin_box_close ' : '';


		$result  = '<form method="post" action="' . $form_action . '" id="LCform">';
		$result .= '<input type="hidden" name="cmd" value="LC_Check"/> ';
		$result .= '<input type="submit" name="" value="' . $langmessage['Checking'] . '" class="gpsubmit"' . $data_cmd . '/>';
		$result .= '<input type="button" class="' . $admin_box_close . 'gpcancel" name="" value="' . $langmessage['cancel'] . '" />';
		$result .= '</form>';
		return $result;
	}


	public static function AddLinkCheckerTable() {
		global $page;
		if ( !empty(self::$results) ) {
			$key = array_search($page->title, array_column(self::$results, 0));
			if ( $key !== false ) {
				if (self::$results[$key][1] == $page->fileModTime) {
					return self::$results[$key][3];
				} else {
					array_splice(self::$results, $key, 1);
					self::SaveResults();
				}
			}
		}
		return false;		
	}


	public static function GetLinkCheckerTable() {
		global $page;
		$website = \gp\tool::AbsoluteUrl($page->title,'',true,true,true);
		$html = file_get_contents($website);
		$website = preg_replace('#((?:https?://)?[^/]*)(?:/.*)?$#', '$1', $website);
		$dom = new DOMDocument();
		@$dom->loadHTML($html);
		$xpath = new DOMXPath($dom);
		$elems = $xpath->evaluate("/html/body//a");
//		$elems = $xpath->evaluate("/html/body//a | /html/body//img"); //also check images
		$rows = '';
		$errors = 0;
		$oks = 0;
		$warnings = 0;
		for ($i = 0; $i < $elems->length; $i++) {
			$elem = $elems->item($i);
			$url = $elem->getAttribute('href');
/*
			if ($url == '') { // it is the image
				$url = $elem->getAttribute('src');
			}
*/			
			
			$p = strpos($url, '#');
			if ($p !== false) {
				$url = substr($url, 0, $p);
			}

			$url = ltrim($url, '/');

			foreach(self::$WhiteList as $el) {
				if (strpos($url, $el) !== false) {
					$url = '';
					break;
				}	
			}
			
			if ($url) {
				$src_url = $url;
				$isAbsoluteUrl = strpos($url, 'http://') !== false || strpos($url, 'https://') !== false;
				$url = $isAbsoluteUrl ? $url : $website.'/'.$url;
				$httpCode = self::get_http_response_code($url);

				$rows .= '<tr>';
				$rows .= '<td><a onclick="ShowWhereLinkIs(\''.$src_url.'\');">'.$url.'</a></td>';
				if($httpCode == 200) {
					$rows .= '<td class="normal">ok</td>';
					$oks += 1;
				} else if($httpCode == 301 || $httpCode == 302) {
					$rows .= '<td class="warning">Redirected</td>';
					$warnings += 1;
				} else {
					$rows .= '<td class="error">Not working</td>';
					$errors += 1;
				}
				$rows .= "</tr>";
			}
		}

		$result  = '';
		$result .= '<table id="LCStatTable">';
		$result .= '<thead>';
		$result .= '<tr>';
		$result .= '<th>Ok</th>';
		$result .= '<th>Error</th>';
		$result .= '<th>Warning</th>';
		$result .= '</tr>';
		$result .= '</thead>';
		$result .= '<tbody>';
		$result .= '<tr>';
		$result .= '<td>'.$oks.'</td>';
		$result .= '<td>'.$errors.'</td>';
		$result .= '<td>'.$warnings.'</td>';
		$result .= '</tr>';
		$result .= '</tbody>';
		$result .= '</table>';
		$result .= '<table id="LCFormTable" class="tablesorter full_width table-striped bordered">';
		$result .= '<thead>';
		$result .= '<tr>';
		$result .= '<th>URL</th>';
		$result .= '<th>Status</th>';
		$result .= '</tr>';
		$result .= '</thead>';
		$result .= '<tbody>';
		$result .= $rows;
		$result .= '</tbody>';
		$result .= '</table>';
		$result .= '</div>';
		$result .= '<script>';
		$result .= '	$("#LCFormTable").tablesorter({
							sortList: [[1,0]],
							cssHeader : "gp_header -full_width",
							cssAsc : "gp_header_asc",
							cssDesc : "gp_header_desc",
						});';
		$result .= '</script>';

		$results_item = array();
		$results_item[] = $page->title;
		$results_item[] = $page->fileModTime;
		$results_item[] = time();
		$results_item[] = $result;

		do {
			$key = array_search($page->title, array_column(self::$results, 0));
			if ($key !== false) {
				array_splice(self::$results, $key, 1);
			}
		} while ($key !== false);
		self::$results[] = $results_item;
		self::SaveResults();

		return $result;
	}

	public static function get_http_response_code($url) {
		$handle = curl_init($url);
		curl_setopt($handle, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.2 (KHTML, like Gecko) Chrome/22.0.1216.0 Safari/537.2");
		curl_setopt($handle, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($handle, CURLOPT_HEADER, 0);
		curl_setopt($handle, CURLOPT_TIMEOUT, 10);
		curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);

		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

		curl_close($handle);
		return $httpCode;
	}
  
}